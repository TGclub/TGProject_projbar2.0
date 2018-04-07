<?php
namespace app\api\controller;
use Qcloud\Sms\SmsSingleSender;
use think\Controller;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
//use think\Config;
class SentShortMessage extends Controller{

    public function index()
    {
       // return Config::get('good');
       //return dump(config()); 
       return config('phone.appid');
    }
    /**
     * 发送邮件,只需要填写接受者即可
     */
    public static function sentEmail($random,$receiver)
    {
        $mail = new PHPMailer(true); 
        $mail->SMTPDebug = 0;                                 // Enable verbose debug output
        $mail->isSMTP();                                      // Set mailer to use SMTP
        $mail->CharSet='UTF-8';
        $mail->Host = config('email.host');  // Specify main and backup SMTP servers
        $mail->SMTPAuth = true;                               // Enable SMTP authentication
        $mail->Username = config('email.username');                 // SMTP username
        $mail->Password = config('email.password');                           // SMTP password
        $mail->SMTPSecure = 'tls';                            // Enable TLS encryption, `ssl` also accepted
        $mail->Port = config('email.port');                                    // TCP port to connectto
        //$receiver = 'w461184988@163.com';
        //Recipients
        $mail->setFrom(config('email.username'), '项慕吧'); //后面这个是发送时候的昵称
        $mail->addAddress($receiver,'xduer');     //收件人的地址和对收件人的称号
        // 这里是收件人的地址和收件人的昵称
       // $mail->addReplyTo('info@example.com', 'Information');
        //$mail->addCC('cc@example.com');
       // $mail->addBCC('bcc@example.com');
        //Attachments
        // $mail->addAttachment('/var/tmp/file.tar.gz');         // Add attachments
        // $mail->addAttachment('/tmp/image.jpg', 'new.jpg');    // Optional name
        //Content
        $subject = '[项慕吧] 用户激活保密邮箱';
        $body = '亲爱的项慕吧用户，您好：<br /><br />
                您的验证码为'.$random.',请于5分钟内填写，如非本人操作，请忽略该邮件。
        <br /><br />                          欢迎加入项慕吧大家庭，您的大神之路从此起航。';
        $mail->isHTML(true);                                  // Set email format to HTML
        $mail->Subject = $subject;
        $mail->Body    = $body;
        //$mail->AltBody = 'this body is wu,i juse test';
       // return 0;
        $res = $mail->send();
        return $res;
        //return json($res);
    }
    /**
     * 发送的随机数，失效时间，发送对象
     */
    public static function sentShortMessage($random,$phoneNumbers,$time='5'){ //使用为static才能被其他地方调用
        try {
        $ssender = new SmsSingleSender(config('phone.appid'),config('phone.appkey'));
        $params = [$random,$time];
        $result = $ssender->sendWithParam("86", $phoneNumbers, config('phone.templateId'),
        $params, config('phone.smsSign'), "", "");  // 签名参数未提供或者为空时，会使用默认签名发送短信
        $rsp = json_decode($result);
        return json($rsp);
        }catch(\Exception $e) {
            return json(['error'=>'短信发送失败','code'=>'500']);
        }
    }
}
?>