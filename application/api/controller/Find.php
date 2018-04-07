<?php
namespace app\api\controller;

Vendor('qiniu.php-sdk.autoload.php'); //tp5是这样引入vendor包的
require "SentShortMessage.php";
use think\Controller;
use think\Db;
use Qiniu\Auth;
use Qiniu\Storage\UploadManager;
use think\Request;

class Find extends Common
{

    public $opendId;

    public $userId;

    public function _initialize()
    {
        parent::_initialize();
    }
    /**
     *  普通寻找项目
     */
    public function normalFindproject()
    {
        $isLogin = $this -> check_user();  //先判断这个用户是否存在
        if(!empty($isLogin))
        return $isLogin;
    }
    /**
     *  普通寻找人才
     */
    public function normalFindpeople()
    {
        $isLogin = $this -> check_user();  //先判断这个用户是否存在
        if(!empty($isLogin))
        return $isLogin;
        
    }
}