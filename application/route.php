<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
//array('info/:id\d','Info/read_xml','','get','xml,rss')
//Route::resource('Suggestion','api/suggestion');
use think\Route;
Route::post('Suggestion/saveSuggestion','api/Suggestion/saveSuggestion');
Route::get('Suggestion/getSuggestion','api/Suggestion/getSuggestion');
Route::post('User/applyProject','api/User/applyProject');
Route::post('User/modifyInfo','api/User/modifyInfo');
Route::get('Project/find','api/Project/findAllProject');
Route::post('findAllProjectPeople','api/Project/findAllProjectPeople');
Route::post('findPersonInfo','api/User/findPersonInfo');
Route::post('userQuit','api/Project/userQuit');
Route::post('sentMessage','api/Message/sentMessage');
Route::get('getAppMessage','api/Message/getAppMessage');
Route::post('promotePower','api/User/promotePower');
Route::post('reducePower','api/User/reducePower');
Route::get('SentMessage','api/SentShortMessage/index');
Route::post('shortMessage','api/Message/shortMessage');
Route::post('sentShortMessage','api/SentShortMessage/sentShortMessage');
Route::post('bindPhone','api/User/bindPhone');
Route::post('sentEmail','api/Message/sentEmail');
Route::post('checkemail','api/Message/checkemail');
Route::post('checkShortMessage','api/Message/checkShortMessage');

Route::rule('s2','api/Common/index2');
Route::rule('Common/index','api/Common/index');
Route::rule('upload','api/Suggestion/UploadController');
Route::rule('getSuggestion','api/User/getSuggestion');
Route::rule('modifyContactInformation','api/User/modifyContactInformation');
return [
    // '__rest__'=>[
    //     // 指向index模块的blog控制器
    //     'Suggestion'=>'api/Suggestion',
    // ],
    '[hello]'     => [
        ':id'   => ['index/hello', ['method' => 'get'], ['id' => '\d+']],
        ':name' => ['index/hello', ['method' => 'post']],
    ],

];
