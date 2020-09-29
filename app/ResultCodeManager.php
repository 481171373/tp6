<?php

namespace app;


class ResultCodeManager {



    //请求结果
    const SUCCESS = 1;
    const FAIL = 0;

    //登录注册验证
    const ADMIN_IS_NULL =30000;
    const NO_USER_DATA = 30001;
    const CAPTCHA_ERROR = 30002;

    //缺少参数
    const MISS_TOKEN = 40001;
    const MISS_REQUIRED_PARAM = 40002;

    static public $messages = array(

        self::SUCCESS => "成功",
        self::FAIL=>'失败',
        self::MISS_TOKEN => "缺少必要的参数'token'",
        self::MISS_REQUIRED_PARAM => "缺少必要的参数 '%PARAM%'",
        self::ADMIN_IS_NULL=>"非法操作，请登录！",
        self::NO_USER_DATA=>"用户不存在",
        self::CAPTCHA_ERROR=>'验证码错误',
    );

}
