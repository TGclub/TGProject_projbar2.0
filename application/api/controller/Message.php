<?php
namespace app\api\controller;

Vendor('qiniu.php-sdk.autoload.php'); //tp5是这样引入vendor包的
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
}