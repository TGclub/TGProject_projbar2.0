<?php
namespace app\api\controller;

Vendor('qiniu.php-sdk.autoload.php'); //tp5是这样引入vendor包的
use think\Controller;
use think\Db;
use Qiniu\Auth;
use Qiniu\Storage\UploadManager;
use think\Request;

class User extends Common
{

    public $opendId;

    public $userId;

    public function _initialize()
    {
        parent::_initialize();
    }
    public function UploadController()
    {
        ;
    }
    /**
     * 寻找个人信息,项目内成员多一个联系方式
     * 需要有查找人id与被查找信息者id
     * 方法是取全部查找人所在项目与全部被查找人所在项目
     * 然后对比是否有存在同一个项目，如果有，那么返回的信息带联系方式
     */
    public function findPersonInfo()
    {
        $res = $this -> check_user();  //先判断这个用户是否存在
        if(!empty($res))
        return $res;
        $userFind =Db::name('project_member') 
        ->where([
            'user_id' => $this -> userInfo['user_id'],
            'status' => 1,
        ])
        ->where('user_status','<',5)
        ->select();
        $userFound =Db::name('project_member') 
        ->where([
            'user_id' => $this -> params['user_found_id'],
            'status' => 1,
        ])
        ->where('user_status','<',5)
        ->select();
        $isReturnContact = 0;
        for($id=0;$id<count($userFind,0);$id++){  //筛选出的项目已经全部有权查看联系方式了
            for($id2=0;$id2<count($userFound,0);$id2++){ 
                if($userFind[$id]['project_id'] == $userFound[$id2]['project_id']){
                    $isReturnContact = 1;
                }
            }
        }
        $userFoundInfo = Db::name('user') 
        ->where([
            'user_id' =>  $this -> params['user_found_id']
        ])
        ->find(); //这里选择要返回的信息
        $returnUserInfo['user_sex']=$userFoundInfo['user_sex'];
        $returnUserInfo['user_github_url']=$userFoundInfo['user_github_url'];
        $returnUserInfo['user_blog_url']=$userFoundInfo['user_blog_url'];
        $returnUserInfo['user_more_information']=$userFoundInfo['user_more_information'];
        $returnUserInfo['user_role']=$userFoundInfo['user_role'];
        $returnUserInfo['user_lable']=$userFoundInfo['user_lable'];
        if($isReturnContact == 1){
        $returnUserInfo['user_wx']=$userFoundInfo['user_wx'];
        $returnUserInfo['user_email']=$userFoundInfo['user_email'];
        $returnUserInfo['user_phone_number']=$userFoundInfo['user_phone_number'];
        $returnUserInfo['user_qq']=$userFoundInfo['user_qq'];
        }
        return json(['code'=> 200, 'msg' => '成功查看该成员基本信息','data'=>$returnUserInfo]);
    }
    /**
     * 判断该行为是否可以修改，30s内的行为不可以重复发生
     */
    public function checkModify($conduct)
    {
        $lastDo= Db::name('user_last_do') //这里用户行为id与用户id一一对应
        ->where([
            'user_last_do_id'=> $this -> userInfo['user_id']
        ])->find();
        if(!$lastDo)
        return $this ->_json(['error'=>'查找用户失败','code'=>500]);
        $time = strtotime($lastDo[$conduct]);
        if(30+$time>time()){ //30s内不可以再次发起同一个行为
             return $this ->_json(['error'=>'30秒内不可以重复相同操作,请歇歇再试','code'=>403]);
        }
        $update = Db::name('user_last_do')->where([ //更改上次行为信息
            'user_last_do_id' => $this -> userInfo['user_id']
        ])->update([
            $conduct => date('Y-m-d H:i:s',time())
        ]);
        if(!$update)
        return $this ->_json(['error'=>'更新操作失败','code'=>500]);
        return '';
    }
    /**
     * 对个人基本信息进行修改
     */
    public function modifyInfo()
    {
        $res = $this -> check_user();  //先判断这个用户是否存在
        if(!empty($res))
        return $res;

        if(!empty($this -> params['user_name']))//用户名
        $this -> userInfo['user_name'] = $this -> params['user_name'];
        if(!empty($this -> params['user_email']))//用户邮箱地址
        $this -> userInfo['user_email'] = $this -> params['user_email'];
        if(!empty($this -> params['user_real_name']))//用户真实姓名
        $this -> userInfo['user_real_name'] = $this -> params['user_real_name'];
        if(!empty($this -> params['user_sex']))//用户性别，0表示保密，1表示女性，2表示男性
        $this -> userInfo['user_sex'] = $this -> params['user_sex'];
        if(!empty($this -> params['user_more_information']))//用户项目介绍
        $this -> userInfo['user_more_information'] = $this -> params['user_more_information'];
        if(!empty($this -> params['user_lable']))//用户标签
        $this -> userInfo['user_lable'] = $this -> params['user_lable'];
        if(!empty($this -> params['user_wx']))//用户微信号
        $this -> userInfo['user_wx'] = $this -> params['user_wx'];
        if(!empty($this -> params['user_role_id']))//用户角色id
        $this -> userInfo['user_role_id'] = $this -> params['user_role_id'];
        if(!empty($this -> params['user_blog_url']))//用户blog地址
        $this -> userInfo['user_blog_url'] = $this -> params['user_blog_url'];
        if(!empty($this -> params['user_github_url']))//github地址
        $this -> userInfo['user_github_url'] = $this -> params['user_github_url'];
        if(!empty($this -> params['user_phone_number']))//用户电话号码
        $this -> userInfo['user_phone_number'] = $this -> params['user_phone_number'];
        
        $this -> userInfo['update_at'] = date('Y-m-d H:i:s',time());
        $checkModify = $this -> checkModify('modify_his_information');
        if(!empty($checkModify))
        return $checkModify;
        $res = Db::name('user')->where([
            'user_id' => $this -> userInfo['user_id'] 
        ])->update($this -> userInfo);
        if(!$res)
        return $this ->_json(['error'=>'用户信息更改失败','code'=>500]);
        return json(['error'=>'用户信息更改成功','code'=>200,
        'data'=>[
            'time' => $this -> userInfo['update_at'],
        ]]);
    }
    /**
     * 申请项目
     */
    public function applyProject()
    {
        $res = $this -> check_user();  //先判断这个用户是否存在
        if(!empty($res))
        return $res;
        if(empty($this -> userInfo['user_information_integrity']))//判断用户是否填了必填信息
        return $this ->_json(['error'=>'用户存在未填写信息，不能申请','code'=>403]); 
        $this -> projectInfo = Db::name('project')
        ->where([
            'project_id'=> $this -> params['project_id']
        ])->find();
        if(empty($this -> projectInfo)) //寻找项目表中是否存在这个项目
        return $this ->_json(['error'=>'未找到要申请的项目','code'=>400]);
        //判断用户是否申请过了这个项目，如果申请过了，那么短期不可以申请
        $checkModify = $this -> checkModify('apply_project');
        if(!empty($checkModify))
        return $checkModify;
        //这里为提交申请信息
        $res=Db::name('apply_project')->insert([
            'apply_user_id' =>  $this -> userInfo['user_id'],
            'apply_project_id' => $this -> projectInfo['project_id'],
            'create_at' => date('Y-m-d H:i:s',time())
            ]); 
        if(!$res)
        return $this ->_json(['error'=>'申请加入项目失败','code'=>500]);
        //到这里就申请成功了，那么需要通知项目负责人
        return json(['code' => 200,
                'msg' => '申请成功',
                'data'=>[
                'apply_user_id' => $this -> userInfo['user_id'],
                'apply_project_id' => $this -> projectInfo['project_id'],
                'create_at' => date('Y-m-d H:i:s',time())
                ]
            ]);
    }
    /**
     * 储存意见
     */
    public function saveSuggestion() //路由调用的需要public，只有内部调用才是protect
    {
        $file = request()->file('user_suggestion_image');
        if(empty($file))
        return '上传错误';
        $info = $file->rule('date')->validate(['image','size'=>1000000,'ext'=>'jpg,png,bmp,jpeg'])->move(ROOT_PATH.
    'public'.DS.'uplodas');
        if($info){
            // 成功上传后 获取上传信息
            // 输出 jpg
           $path = '/uploads/'.$info ->getSavename();
           echo $path;
          //  echo $info->getExtension(); 
            // 输出 42a79759f284b767dfcb2a0197904287.jpg
            echo $info->getFilename(); 
        }else{
            // 上传失败获取错误信息
            echo $file->getError();
        }    
    }
    /**
     *提升权限
     *promotePower
     */
    public function promotePower()
    {
        $isLogin = $this -> check_user();  //先判断这个用户是否存在
        if(!empty($isLogin))
        return $isLogin;

        $result= Db::name('project_member') //找到对应项目判定其是否为发起者
        ->where([
            'user_id' => $this -> userInfo['user_id'],
            'project_id' => $this -> params['project_id'],
            'status' => 1,
        ])
        ->where('user_status','=',1)
        ->find();
        if(!$result)
        return $this ->_json(['error'=>'权限操作失败','code'=>400]);

        $result= Db::name('project_member') //找到对应项目判定其是否为发起者
        ->where([
            'user_id' => $this -> params['user_promoted_id'],
            'project_id' => $this -> params['project_id'],
            'status' => 1,
        ])
        ->where('user_status','>',2)
        ->find();
        if(!$result)
        return $this ->_json(['error'=>'被提升者出现错误','code'=>400]);

        $result = Db::name('project_member') //找到对应用户并提升权限
        ->where([
            'user_id' => $this -> params['user_promoted_id'],
            'project_id' => $this -> params['project_id'],
            'status' => 1,
        ])
        ->update([
            'user_status' => '2',
        ]);
        if(!$result)
        return $this ->_json(['error'=>'权限提升失败','code'=>400]);
        return json(['code'=> 200, 'msg' => '权限提升成功','data'=>
        [
            'user_id' => $this -> userInfo['user_id'],
            'project_id' => $this -> params['project_id'],
            'user_promoted_id' => $this -> params['user_promoted_id'],
        ]]);
    }
    /**
     *降低权限
     *reducePower
     */
    public function reducePower()
    {
        $isLogin = $this -> check_user();  //先判断这个用户是否存在
        if(!empty($isLogin))
        return $isLogin;

        $result= Db::name('project_member') //找到对应项目判定其是否为发起者
        ->where([
            'user_id' => $this -> userInfo['user_id'],
            'project_id' => $this -> params['project_id'],
            'status' => 1,
        ])
        ->where('user_status','=',1)
        ->find();
        if(!$result)
        return $this ->_json(['error'=>'权限操作失败','code'=>400]);

        $result= Db::name('project_member') //找到要降低者查看是否已经为管理员
        ->where([
            'user_id' => $this -> params['user_reduced_id'],
            'project_id' => $this -> params['project_id'],
            'status' => 1,
        ])
        ->where('user_status','=',2)
        ->find();
        if(!$result)
        return $this ->_json(['error'=>'被降级者出现错误','code'=>400]);

        $result = Db::name('project_member') //找到对应用户并提升权限
        ->where([
            'user_id' => $this -> params['user_reduced_id'],
            'project_id' => $this -> params['project_id'],
            'status' => 1,
        ])
        ->update([
            'user_status' => '3',
        ]);
        if(!$result)
        return $this ->_json(['error'=>'权限降级失败','code'=>400]);
        return json(['code'=> 200, 'msg' => '权限降级成功','data'=>
        [
            'user_id' => $this -> userInfo['user_id'],
            'project_id' => $this -> params['project_id'],
            'user_reduced_id' => $this -> params['user_reduced_id'],
        ]]);
    }
    /**
     * 修改个人联系信息
     */
    public function modifyContactInformation()
    {
        return json($this -> params);
    }


    public function bindPhone()
    {
        ;
    }
}