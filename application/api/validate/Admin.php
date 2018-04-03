<?php
namespace app\api\validate;

use think\Validate;

class Admin extends Validate
{
    protected $rule = [ //判断的时候应该输入这3个信息
     //   'username' => 'require',
     //   'password' => 'require',   //这里require是一个验证规则，其为判断是否为空
    //    'code' => 'require|captcha'   //如果一个变量有多个验证规则
        'user_suggestion_image'=>'require|image|fileSize:1000000|fileExt:jpg,png,bmp,jpeg',  
    ];
    protected $message = [
    //    'username.require' => '请输入用户名',
  //      'password.require' => '请输入密码',
    //    'code.require' => '请输入验证码',
    //    'code.captcha' => '验证码不正确',        //这里的验证规则还需要验证码有name
        'user_suggestion_image.require' => '未上传图片',
        'user_suggestion_image.image'     => '上传的非图片',
        'user_suggestion_image.fileSize'   => '年龄必须是数字',
        'user_suggestion_image.fileExt'  => '年龄只能在1-120之间',
    ];
    
    public function info($data)
    {
        //1.执行验证
        $validate = new Validate([       //因为这个类不是扩展了的Validate,任何时刻都可以用这个类来验证
            'old_password' => 'require',
            'suggestion_content' => 'require'
        ],[
            'old_password.require' => '请输入原密码',
            'email.require' => '请输入新邮箱号'
            ]
    );
    return $data;
        // if(!$validate->check($data)){    //注意这个函数的调用需要validate
        //     return ['valid'=>0,'msg'=> $validate ->getError()];
        //     dump($validate->getError());
        // }
        // //2.对比用户名密码是否正确
        // $username = session('username');
        // $res =Db::table($this ->table)->where(['username' => $username ])->find();
        // if($res['password']!=$data['old_password']){
        //     return ['valid' => 0, 'msg' => '旧密码错误'];
        // }
        // //3.修改邮箱和名字 
        // Db::table($this ->table)->where(['username' => $username])->update(['email' => $data['email'],'name' => $data['name'],'intro' => $data['intro']]);
        // return ['valid'=>1, 'msg'=> '新信息修改成功'];
    }
}
