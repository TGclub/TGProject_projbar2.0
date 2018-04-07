<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件
use Qcloud\Sms\SmsSingleSender; //调用腾讯云的短信服务

use PHPMailer\PHPMailer\PHPMailer; //调用邮箱服务
use PHPMailer\PHPMailer\Exception;

function aa(){
    return '555';
    return config('phone.appid');
}
/**
 * 生成随机数字字符串
 */
function randomNum($length) {
    $returnStr='';
    //abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLOMNOPQRSTUVWXYZ
    //patter为随机中需要产生的字符串,没有0
    $pattern = '987654321';
    for($i = 0; $i < $length; $i ++) {
    $returnStr .= $pattern {mt_rand ( 0, 8 )}; //生成php随机数
    }
    return $returnStr;
}