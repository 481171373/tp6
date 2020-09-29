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
use think\facade\Route;
// 登录编辑
Route::post('login', 'Admin/login');
Route::post('loginEdit', 'Admin/loginEdit');
Route::post('captcha', 'Admin/captcha');
Route::post('WechatMessage/:appid', 'WechatMessage/index');
Route::get('authorizationCode/:merId', 'WeChatAuthorize/authorizationCode');
Route::get('authorization/:authType', 'MerchantAuthorizeInfo/authorization');
Route::get('alipaynotify/:merId', 'AlipayNotify/orderResult');
// Route::get('authorizationCode/:merId', 'MerchantAuthorizeInfo/authorizationCode');
Route::post('WeChatAuthorize', 'WeChatAuthorize/index');
Route::post('AlipayAuthorize', 'AlipayAuthorize/index');


