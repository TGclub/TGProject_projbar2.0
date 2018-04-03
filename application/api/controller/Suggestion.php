<?php
namespace app\api\controller;

Vendor('qiniu.php-sdk.autoload.php'); //tp5是这样引入vendor包的
use think\Controller;
use think\Db;
use Qiniu\Auth;
use Qiniu\Storage\UploadManager;
use think\Request;

class Suggestion extends Common
{
    protected $request;

    public $result;

    public function _initialize(){
        $this -> result = parent::_initialize();
    }
    public function UploadController(){
        ;
    }
    public function save()
    {
    $accessKey='WXasSv_MzF-G3qDcX5ZbTJ3m6GLIua7ipxLaQgzc';
    $secretKey='jzjNiU0bDNhoND0iSW0j33uacWzHa1MwRVliBLNv';
    //$auth = new Auth($accessKey, $secretKey);
    $auth = new Auth($accessKey, $secretKey);
    $bucket = 'yelengaaa';
    // 生成上传Token
    $token = $auth->uploadToken($bucket);
    $filePath ='../text.txt'; //保存文件的话要求../
    //上传到七牛后保存的文件名
    $key = 'a2.txt';
    // 初始化 UploadManager 对象并进行文件的上传。
    $uploadMgr = new UploadManager();
    // 调用 UploadManager 的 putFile 方法进行文件的上传。
    list($ret, $err) = $uploadMgr->putFile($token, $key, $filePath);
    echo "\n====> putFile result: \n";
    if ($err !== null) {
        var_dump($err);
    } else {
        var_dump($ret);
    }
    }
    /**
     * 储存用户上传意见信息
     */
    public function saveSuggestion()
    {
    if(empty($this ->request-> param('user_name')))//如果用户该栏未填，那么信息将其变为空
    $this -> params['user_name']='';
    if(empty($this ->request-> param('suggestion_content')))
    $this -> params['suggestion_content']='';
    if(empty($this ->request-> param('user_number')))
    $this -> params['user_number']='';
    $db =Db::name('suggestion');
    $res=$db->insert([
    'user_name' =>  $this -> params['user_name'],
    'suggestion_content' => $this -> params['suggestion_content'],
    'user_number' => $this -> params['user_number'],
    'creat_at' => date('Y-m-d H:i:s',time())
    ]); 
    if(!$res){
        return json(['code'=>400,'msg'=> '添加留言失败','time'=> time()]);
    }else{
        return json(['code'=>200, 'msg' => '添加意见成功','time'=> date('Y-m-d H:i:s',time())]);
    }
    }
    /**
     * 按照距离上传时间排序，获取意见信息
     */
    public function getSuggestion()
    {
        $res = Db::name('suggestion')->order('creat_at desc')->select();
        return json($res);
    }
    public function index()
    {
       // $this -> save();
        #$db = Db::name('suggestion');
        #$user_number = 132;
        return json($this -> saveSuggestion());
        //return $this -> saveSuggestion($this -> params['user_name']);
        //return '555';
        //return print($result);
        //  $db->insert([
        //       'user_name' => 'yeleng',
        //       'suggestion_content' => 'it is good',
        //       'user_number' => $user_number
        //   ]);
    }
    
    public function index2()
    {
        // $header=Request::instance()->header();
        // $header['id']=Request::instance()->ip(); //如果ip为0,0,0,0则为本地ip
        // $header = Request::instance()->controller();
        // $header = Request::instance()->action();
        return $this -> result;
        // $header = Request::instance()->param();
        // return json($header);
        // print($this -> request);
    }
}
