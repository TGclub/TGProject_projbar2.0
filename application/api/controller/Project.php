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
        $res= Db::name('project_member') //这里用户行为id与用户id一一对应
        ->where([
            'user_id' => $this -> userInfo['user_id'],
            'status' => 1,
        ])->order('create_time','asc')->select(); //这里有个bug，就是查的时候create要写对
        return json($res);
    }
    /**
     * 查找一个项目的全部成员
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
                if($result[$id]['user_status']<=3) $is_power =1;
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
}