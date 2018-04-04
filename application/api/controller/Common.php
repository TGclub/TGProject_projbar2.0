<?php 
namespace app\api\controller;

use think\Request;
use think\Controller;
use think\Validate;
use think\Db;

class Common extends Controller{
    
    protected $request; //用来处理参数
    
    protected $validater; //用来验证数据/参数
    
    public $params; //过滤后符合要求的参数

    protected $header; //获得的header信息

    public $userId;

    public $projectInfo;

    public $userInfo;

    public $aa;
    
    public $dd;

    public $result;

    public $rules = array(
        'Common' =>array(
            'index'=>array(
                'user_name' =>['require','chsDash','max'=>20],
                'user_pwd'=>['require']
            ),
        ),
        'User' =>array(
            'savesuggestion'=>array(
                'user_name' =>['require','chsDash','max'=>20],
                'user_pwd'=>['require']
               // 'user_suggestion_image'=>'require|image|fileSize:1000000|fileExt:jpg,png,bmp,jpeg'
            ),
            'applyproject'=>array(
                'project_id' =>['require','integer'],
             ),
             'modifyinfo'=>array(
                'user_name' =>['chsAlphaNum','max'=>8],
                'user_real_name' => ['max'=>5,'chs'],
                'user_email' => ['email'],
                'sex' => ['between:0,2'],
                'user_role_id' => ['integer'],
                'user_more_information' => ['max'=>'200'],
                'user_github_url' => ['max'=>'50'],
                'user_blog_url' => ['max'=>'50'],
                'user_wx' => ['max'=>'40'],
                'user_lable' => ['max'=>'50'],
                'user_phone_number' => ['number','max'=>'20']
             ),
             'findpersoninfo'=>array(
                'user_found_id' =>['require','integer']
             ),
        ),
        'Suggestion' =>array(
            'savesuggestion'=>array(
                'suggestion_content' =>['require','chsDash','max'=>120],
                'user_number' => ['chsDash','max'=>30],
                'user_name' => ['chsDash','max'=>40]
                // 'user_suggestion_image'=>'require|image|fileSize:1000000|fileExt:jpg,png,bmp,jpeg'
            ),
            'getsuggestion'=>array(
            ),
        ),
        'Project' =>array(
            'findallproject'=>array(
                'is_a' => ['max'=>20],
            ),
            'findallprojectpeople'=>array(
                'project_id'=> ['require','integer'],
            ),
            'userquit'=>array(
                'project_id'=>['require','integer'],
            ),
        ),
    );

    public function index2()
    {
        // $header=Request::instance()->header();
        // $header['id']=Request::instance()->ip(); //如果ip为0,0,0,0则为本地ip
        // $header = Request::instance()->controller();
        // $header = Request::instance()->action();
        $accessKey='WXasSv_MzF-G3qDcX5ZbTJ3m6GLIua7ipxLaQgzc';
        $secretKey='jzjNiU0bDNhoND0iSW0j33uacWzHa1MwRVliBLNv';
        $client = new QiniuClient($accessKey,$secretKey);
        $header = Request::instance()->param();
        return json($header);
         // print($this -> request);
    }

    // // protected $rules = array(
    // //     'Suggestion' => array(
    // //         'index2' => array(
    // //             'user_name' => ['require','chsDash'],
    // //             'user_pwd' => ='require'
    // //         ),
    // //     ),
    // // );

     public function _initialize(){
        parent::_initialize();
        $this -> request = Request::instance();
        $this -> header = Request::instance()->header();
        $this -> params = Request::instance()->param();
        //return json($this -> params);
        //$this -> check_token($this -> header);
        //$this -> Validater =5;
        //dump(json($this -> header));
        //$this -> Validater = 5;wesoje,e zsaesodaodisdixhdnem nis
        //$this -> Validater = $this -> request -> param();
        $this -> result = $this -> check_params($this -> params);
    }
    /**
     * 用于判断用户登录是否正常
     */
    public function check_user()
    {
        if(!$this -> request -> header('authorization'))
        return $this -> _json(['error'=>'登录出错','code'=>400]);
        $jwt = $this -> header['authorization'];
        $arr = explode(" ",$jwt);
        $openId = $arr[1];
        $this -> userInfo = Db::name('user')
        ->where([
            'user_openid'=>$openId
        ])->find();
        if(empty($this -> userInfo))
        return $this -> _json(['error'=>'别搞事情好吗','code'=>401]);
        //这里如果是select，那么拿到的是一个二维数组
        //$this -> user_id = $this -> userInfo['user_id'];
    }
    // public function check_time($code,$error){
    //     if(!isset($arr['time'])||inval($arr['time']<=1)){
    //         $this -> _json(['error'=>'时间戳不存在','code'=>'400']);
    //     }
    /**
     * 验证token
     * $params [header] [header信息]
     * $return  [是否正确]
     */
    // private function check_token($header){
    //         ;
    // }
    public function index(){
     //   $this -> params = Request::instance()->param();
        return json($this -> result);
    }

    public function _json($array,$code=0)
    {
         if($code>0 && $code!=200 && $code!=204){
         header("HTTP/1.1 {$code} {$this->_statusCodes[$code]}");
        }
        header('Content-Type:application/json;charset=utf-8');  //json 的一个头为utf8格式
        echo json_encode($array,JSON_UNESCAPED_UNICODE);
        exit(); //die=exit()不用return是不想接着下去，直接把所有脚本都终止掉
    }

    /**
     * 验证参数 参数过滤
     * @params [arr] [除了token以外的信息]
     * @reutrn [type] [合格的参数数组]
     */
     public function check_params($arr){
     //这里把数组当作数据库用，较为迅速
     //return $this -> request ->action();
     //request()->file('user_suggestion_image');
     $rule = $this -> rules[$this -> request -> controller()][$this -> request ->action()];
     $this -> validater = new Validate($rule);
     if(!$this -> validater -> check($arr)){ //传入参数不符合要求
               $this ->_json(['error'=>$this -> validater -> getError(),'code'=>400]);
           }
     return $arr;//如果正常输入,通过验证
    }
}