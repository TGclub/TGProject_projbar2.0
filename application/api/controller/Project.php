<?php
namespace app\api\controller;

Vendor('qiniu.php-sdk.autoload.php'); //tp5是这样引入vendor包的
use think\Controller;
use think\Db;
use Qiniu\Auth;
use Qiniu\Storage\UploadManager;
use think\Request;

class Project extends Common
{

    public $opendId;

    public $userId;

    public function _initialize()
    {
        parent::_initialize();
    }
    /**
     * 通过openid查找用户全部项目信息
     */
    public function findAllProject()
    {
        $isLogin = $this -> check_user();  //先判断这个用户是否存在
        if(!empty($isLogin))
        return $isLogin;
        $result= Db::name('project_member') //这里用户行为id与用户id一一对应
        ->where([
            'user_id' => $this -> userInfo['user_id'],
            'status' => 1,
        ])->order('create_time','asc')->select(); //这里有个bug，就是查的时候create要写对
        for($id=0;$id<count($result,0);$id++){ //将所有没必要返回的消息删除
           
            unset($result[$id]['status']); //删除不传回的信息
            unset($result[$id]['update_time']);
            unset($result[$id]['project_member_id']);
            unset($result[$id]['status']);
       }
        return json(['code'=> 200, 'msg' => '成功查看全部项目基本信息','data'=>$result]);
    }
    /**
     * 查找一个项目的全部成员
     * findAllProjectPeople
     */
    public function findAllProjectPeople()
    {
        $isLogin = $this -> check_user();  //先判断这个用户是否存在
        if(!empty($isLogin))
        return $isLogin;
        $result= Db::name('project_member') //寻找这个项目的全部成员
        ->where([
            'status' => 1, //这里status未0表示该信息无效
            'project_id' => $this -> params['project_id'],
        ])->select(); //这里有个bug，就是查的时候create要写对
        if(empty($result))
        return $this -> _json(['error' => '查找项目信息失败','code'=>404]);
        $is_power = 0;
        for($id=0;$id<count($result,0);$id++){ //将所有没必要返回的消息删除
             if($result[$id]['user_id'] == $this -> userInfo['user_id']) //判断该用户是否有权限查看所有成员信息
             {
                if($result[$id]['user_status']<=4) $is_power =1;
                else break;
             }
             unset($result[$id]['project_member_id']); //删除不传回的信息
             unset($result[$id]['project_id']);
             unset($result[$id]['update_time']);
             unset($result[$id]['status']);
        }
        if($is_power == 0)
        return $this -> _json(['error' => '用户无权限查看该项目成员信息','code'=>404]);
        return json(['code'=> 200, 'msg' => '成功查看该项目成员信息','data'=>$result]);
    }

    /**
     * 用户自愿退出该项目
     */
    public function userQuit()
    {
        $isLogin = $this -> check_user();  //先判断这个用户是否存在
        if(!empty($isLogin))
        return $isLogin;
        $result= Db::name('project_member') //寻找这个成员是否存在于这个项目
        ->where([
            'status' => 1, //这里status为0表示该信息无效
            'user_id' => $this -> userInfo['user_id'],
            'project_id' => $this -> params['project_id'], //这里为退出的项目id
        ])->find(); //这里有个bug，就是查的时候create要写对
        if(empty($result))
        return $this -> _json(['error' => '该用户不在所要退出的项目组中','code'=>400]);
        $modify = Db::name('project_member') //把这个信息的status置0
        ->where([
            'status' => 1, //之所以加这status是因为存在一个人重复进同一个项目
            'user_id' => $this -> userInfo['user_id'],
            'project_id' => $this -> params['project_id'],
        ])->update(['status'=>0]);
        if(!$modify)
        return $this -> _json(['error' => '用户退出项目失败','code'=>400]);
        if(empty($this ->request-> param('quit_reason'))) //判断是否有填退出理由，如果没有则置空
        $this -> params['quit_reason']='';
        $quit=Db::name('quit')->insert([ //将退出信息加入quit表
            'quit_user_id' =>  $this -> userInfo['user_id'],
            'quit_project_id' =>  $this -> params['project_id'],
            'quit_reason' => $this -> params['quit_reason'],
            'quit_create_ip' => $this -> request -> ip(),
            'quit_create_at' => date('Y-m-d H:i:s',time()),
        ]);
        if(!$quit)
        return $this -> _json(['error' => '用户退出错误','code'=>400]);
        $lastId = Db::name('quit')->getLastInsID();//获取上一个添加到表的id
        // 当用户退出以后，需要给所有管理员与自己发信息
        $projectManager = Db::name('project_member') //寻找这个项目的管理者
        ->where([
            'status' => 1, //这里status未0表示该信息无效
            'project_id' => $this -> params['project_id'],
        ])
        ->where('user_status','<',3) //权限小于3表示发起者与管理者
        ->select();
        for($id=0;$id<count($projectManager,0);$id++){ //对每一个管理员均发送该成员退出的信息
            $sentMessageManager = Db::name('app_message')->insert([
                'app_message_receiver_id' => $projectManager[$id]['user_id'],
                'app_message' => $this -> params['quit_reason'],
                'app_message_author_id' => $this -> userInfo['user_id'],
                'app_message_type' => '2',
                'app_message_father_type' => '2',
                'app_message_father' => $lastId,
                'app_message_create_at' => date('Y-m-d H:i:s',time()),
                ]); 
            if(!$sentMessageManager) //如果退出发生错误则报错
            {
                return $this -> _json(['error' => '退出项目发生错误','code'=>500]);
            }
        }
        return json(['code'=> 200, 'msg' => '用户退出项目成功','data'=>[
            'user_id' => $this -> userInfo['user_id'],
            'project_id' => $this -> params['project_id'],
            'quit_create_ip' => $this -> request -> ip(),
            'quit_create_at' => date('Y-m-d H:i:s',time()),
        ]]);
    }
    /**
     * 接受待定成员
     */
    public function receivePerson()
    {
        $isLogin = $this -> check_user();  //先判断这个用户是否存在
        if(!empty($isLogin))
        return $isLogin;
        $result= Db::name('project_member') //寻找该用户是否有权限做这个决定
        ->where([
            'status' => 1, //这里status为0表示该信息无效
            'user_id' => $this -> userInfo['user_id'],
            'project_id' => $this -> params['project_id'], //这里为项目id
        ])->find(); //这里有个bug，就是查的时候create要写对
        return json($result);
        if(empty($result)) //判断该用户是否可以通过这个项目群发
        $this ->_json(['error'=>'该用户没有资格群发消息','code'=>400]);
        $people = Db::name('project_member') //寻找该项目组全部成员
        ->where([
            'status' => 1, //这里status为0表示该信息无效
            'project_id' => $this -> params['project_id'], //这里为项目id
        ])->select();
        return json($people);
    }

    
}