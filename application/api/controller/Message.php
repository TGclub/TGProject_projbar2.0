<?php
namespace app\api\controller;

Vendor('qiniu.php-sdk.autoload.php'); //tp5是这样引入vendor包的
require "SentShortMessage.php";
use think\Controller;
use think\Db;
use Qiniu\Auth;
use Qiniu\Storage\UploadManager;
use think\Request;

class Message extends Common
{

    public $opendId;

    public $userId;

    public function _initialize()
    {
        parent::_initialize();
    }
    /**
     * 群发消息
     * sentMessage
     */
    public function sentMessage()
    {
        $isLogin = $this -> check_user();  //先判断这个用户是否存在
        if(!empty($isLogin))
        return $isLogin;

        $result= Db::name('project_member') //找到其要发送的项目并判断其是否为管理员
        ->where([
            'user_id' => $this -> userInfo['user_id'],
            'project_id' => $this -> params['project_id'],
            'status' => 1,
        ])
        ->where('user_status','<',3)
        ->find();
        if(!$result)
        $this ->_json(['error'=>'该用户没有资格发送群消息','code'=>400]);
        
        $people = Db::name('project_member') //寻找该项目组全部成员
        ->where([
            'status' => 1, //这里status为0表示该信息无效
            'project_id' => $this -> params['project_id'], //这里为项目id
        ])->select();

        if(count($people,0)<2)//判断该项目的总人数
        $this ->_json(['error'=>'该项目就你一个人，发啥发','code'=>400]);
        for($id=0;$id<count($people,0);$id++){ //给每个人该项目的id打上标记1
            $people_in[$people[$id]['user_id']] = 1;
        }
        $people_id = $this -> params['people_id'];
        for($id=0;$id< $this -> params['people_number'];$id++){ //判断传入参数是否合法
            if(empty($people_id[$id]))//如果遇到传入的人员id为空，报错
            $this ->_json(['error'=>'发送消息人员数量有误','code'=>400]);

            if($people_in[$people_id[$id]] == 0) //判断该成员是否在此项目
            $this ->_json(['error'=>'存在不在此项目成员id','code'=>400]);
        }

        $people_id = implode(',',$people_id); //将要发送信息的成员id的数组的数据用","隔开变成字符串
        $sent = Db::name('sent_message')->insert([ //将发送信息的行为加入到数据表中
            'sent_message_user_id' =>  $this -> userInfo['user_id'],
            'sent_message_receiver_id' =>  $people_id,
            'sent_message_project_id' => $this -> params['project_id'],
            'sent_message_content' => $this -> params['sent_message_content'],
            'sent_message_create_type' => '0',
            'sent_message_create_at' => date('Y-m-d H:i:s',time()),
            'sent_message_create_ip' =>  $this -> request -> ip(),
        ]);
        if(!$sent) //判断记录该行为是否成功
        $this ->_json(['error'=>'发送消息错误','code'=>400]);
        $people_id = explode(',',$people_id); //将字符串重新转换为数组形式
        $lastId = Db::name('sent_message')->getLastInsID();//获取上一个添加到表的id
        //记录行为成功后，发送给每一个人对应的app消息中
        $is_bug = 0;//判断发送信息过程中是否出现了问题
        for($id=0;$id<$this -> params['people_number'];$id++){ //记录每一个收到信息的用户id
            $sent = Db::name('app_message')->insert([ //将发送信息的行为加入到数据表中
                'app_message_receiver_id' =>  $people_id[$id],
                'app_message' =>  $this -> params['sent_message_content'],
                'app_message_author_id' => $this -> userInfo['user_id'],
                'app_message_type' => '2',
                'app_message_father_type' => '5',
                'app_message_father' => $lastId,
                'app_message_status' =>  $this -> request -> ip(),
                'app_message_create_at' => date('Y-m-d H:i:s',time()),
            ]);
            if(!$sent){//判断发送信息过程中是否出现了问题
                $is_bug = 1;
            }
        }
        if($is_bug == 1)
        $this ->_json(['error'=>'发送消息中出现了未知的错误','code'=>400]);
        return json(['code'=> 200, 'msg' => '用户群发消息成功','data'=>
        [
            'user_id' => $this -> userInfo['user_id'],
            'people_number' => $this -> params['people_number'],
            'people_id' => $people_id,
            'project_id' => $this -> params['project_id'],
            'time' => date('Y-m-d H:i:s',time()),
        ]]);
    }
    /**
     * 获取用户消息
     * getAppMessage
     */
    public function getAppMessage()
    {
        $isLogin = $this -> check_user();  //先判断这个用户是否存在
        if(!empty($isLogin))
        return $isLogin;
        $result= Db::name('app_message') //找到其要发送的项目并判断其是否为管理员
        ->where([
            'app_message_receiver_id' => $this -> userInfo['user_id'],
            'app_message_status' => '0',
        ])
        ->order('app_message_create_at','desc')
        ->select();
        for($id=0;$id<count($result,0);$id++){ //将所有没必要返回的消息删除
            unset($result[$id]['app_message_id']); //删除不传回的信息
            unset($result[$id]['project_id']);
            unset($result[$id]['update_time']);
            unset($result[$id]['app_message_status']);
       }
       return json(['code'=> 200, 'msg' => '获取用户信息成功','data'=> $result]);
    }
    
    /**
     * 发送短信或者邮件
     * @param int $type 0表示手机短信，1表示邮件
     */
    public function sentInfo($type)
    {
        $isLogin = $this -> check_user();  //先判断这个用户是否存在
        if(!empty($isLogin))
        return $isLogin;
        //查60s内是否有发和1小时是否超过5次
        $result= Db::name('identify_code') //先找到其30s内是否有发送过，如果有，则定为过于频繁
        ->where([
            'identify_code_receiver_id' => $this -> userInfo['user_id'],
            'identify_code_type' => $type,
        ])
        ->order('identify_code_create_at','desc') //desc为按照时间最近的顺序给出
        ->limit(10)
        ->select();
        if(count($result,0)>=1){//有一条信息，则判其是否在30s内发送过
            if(strtotime($result[0]['identify_code_create_at'])+60>time())
            $this ->_json(['error'=>'验证码发送间隔不能低于60s','code'=>400]);
            if(count($result,0)>=5){ //有5条以上，判断其1hour内不能发送超过5条
                if(strtotime($result[4]['identify_code_create_at'])+60*60>time())
                $this ->_json(['error'=>'一小时内验证码发送次数最多为5次','code'=>400]);
            }
            if(count($result,0)==10){ //有10条，判断其1天最多10次
                if(strtotime($result[9]['identify_code_create_at'])+24*60*60>time())
                $this ->_json(['error'=>'一天内验证码发送次数最多为10次','code'=>400]);
            }
        }
        $personInfo= Db::name('user') //获取该用户原个人信息，判断其换绑的手机号或者邮件与之前是否相同
        ->where([
            'user_id' => $this -> userInfo['user_id'],
        ])->find();

        //判断结束表示为可以发送验证码,每次发送均吧上一次验证码的效果设置为失效
        if(count($result,0)>=1){
            $loss = Db::name('identify_code')
            ->where([
                'identify_code_id' => $result['0']['identify_code_id'],
                'identify_code_type' => $type, //最近一次邮件或者短信，别找错了
            ])
            ->update([
                'identify_code_status' => '0'
            ]);
            //if(!$loss)
            //$this ->_json(['error'=>'发送验证码出现错误','code'=>500]);
        }
        //验证完可以发送了
        $random = randomNum(6); //获取随机数
        if($type=='0'){
        if($this -> userInfo['user_phone_number']==$this -> params['phone'])
        return $this ->_json(['error'=>'原绑定手机号码与新绑定相同','code'=>400]);
        $res = SentShortMessage::sentShortMessage($random,$this -> params['phone']);
        if(empty($res))
        return $this ->_json(['error'=>'发送验证码出现错误了','code'=>500]);
        
        }else{
        if($this -> userInfo['user_email']==$this -> params['email'])
        return $this ->_json(['error'=>'原绑定邮件与新绑定邮件相同','code'=>400]);
        $res = SentShortMessage::sentEmail($random,$this -> params['email']);
        if(empty($res))
        return $this ->_json(['error'=>'发送验证码出现未知错误','code'=>500]);
        }
        if($type=='0')
        $recevier = $this -> params['phone'];
        else
        $recevier = $this -> params['email'];

        $sent = Db::name('identify_code')->insert([ //将发送信息的行为加入到数据表中
            'identify_code_content' =>  $random,
            'identify_code_receiver_id' => $this -> userInfo['user_id'],
            'identify_code_type' => $type,
            'identify_code_receiver' => $recevier,
            'identify_code_last_prove' =>  date('Y-m-d H:i:s',time()),
            'identify_code_create_at' => date('Y-m-d H:i:s',time()),
        ]);
        if(!$sent)
        $this ->_json(['error'=>'发送验证码出现未知错误','code'=>500]);

        return json(['code'=> 200, 'msg' => '发送验证码成功','data'=>
        [
            'user_id' => $this -> userInfo['user_id'],
            'type' => $type,
            'time' => date('Y-m-d H:i:s',time()),
        ]]);
    }
    /**
     * 发送邮件
     */
    public function sentEmail()
    {
        return $this ->sentInfo(1);
    }
    /**
     * 发送短信
     */
    public function shortMessage()
    {
        return $this ->sentInfo(0);
    }
    /**
     * 验证短信或者邮件是否正确
     * @param int $type 0表示手机短信，1表示邮件
     */
    public function checkInfo($type)
    {
        $isLogin = $this -> check_user();  //先判断这个用户是否存在
        if(!empty($isLogin))
        return $isLogin;
        //查其最近一起填写验证码时间距离现在时间
        $result= Db::name('identify_code') //先找到其3s内是否有验证过，如果有，则定为过于频繁
        ->where([
            'identify_code_receiver_id' => $this -> userInfo['user_id'],
            'identify_code_type' => $type,
        ])
        ->order('identify_code_create_at','desc') //desc为按照时间最近的顺序给出
        ->limit(10)
        ->select();

        if(count($result,0)>=1){//有一条信息，则判其是否在30s内发送过
            if(strtotime($result[0]['identify_code_last_prove'])+3>time())
            return $this ->_json(['error'=>'验证码验证间隔不能低于3s','code'=>400]);
            if(strtotime($result[0]['identify_code_create_at'])+5*60<time())//判断验证码是否过期
            return $this ->_json(['error'=>'你的验证码已过期','code'=>400]);
        }else{
            return $this ->_json(['error'=>'您没有可以验证的验证码','code'=>400]);
        }
        //判断结束表示为可以验证验证码,每次验证均把上一次验证码的效果设置为失效
        if(count($result,0)>=1){ //把验证时间更新为现在，也就是下一次需要在3s后
            $loss = Db::name('identify_code')
            ->where([
                'identify_code_id' => $result['0']['identify_code_id'],
                'identify_code_type' => $type, //最近一次邮件或者短信，别找错了
            ])
            ->update([
                'identify_code_last_prove' => date('Y-m-d H:i:s',time()),//把最近验证时间更新
            ]);
            if(!$loss)
            return $this ->_json(['error'=>'验证验证码出现错误','code'=>500]);
        }
        //判断完就验证
        if($result[0]['identify_code_content'] != $this -> params['identify_code'])
        return $this ->_json(['error'=>'验证码错误','code'=>400]);
        //验证成功后更新个人信息
        if($type=='0'){
            $update = Db::name('user')
            ->where([
                'user_id' => $result['0']['identify_code_receiver_id'],
            ])
            ->update([
                'user_phone_number' => $result[0]['identify_code_receiver']
            ]); 
        }else{
            $update = Db::name('user')
            ->where([
                'user_id' => $result['0']['identify_code_receiver_id'],
            ])
            ->update([
                'user_email' => $result[0]['identify_code_receiver']
            ]); 
        }
        if(!$update)
        return $this ->_json(['error'=>'验证过程出现未知错误','code'=>500]);

        $modify = Db::name('identify_code')
            ->where([
                'identify_code_id' => $result['0']['identify_code_id'],//准确找到这个验证码
            ])
            ->update([
                'identify_code_create_at' => date('Y-m-d H:i:s',time()-5*60),//把创建时间变远，直接过期
            ]);
            if(!$modify)
            return $this ->_json(['error'=>'验证出现错误','code'=>500]);

        return json(['code'=> 200, 'msg' => '更换绑定成功','data'=>
        [
            'user_id' => $this -> userInfo['user_id'],
            'type' => $type,
            'time' => date('Y-m-d H:i:s',time()),
        ]]);
        
    }
    /**
     * 确认手机验证码是否正确
     */
    public function checkShortMessage()
    {
        return $this -> checkInfo(0);
    }
     /**
      * 确认邮箱验证码是否正确
      */
    public function checkEmail()
    {
        return $this -> checkInfo(1);
    }
}