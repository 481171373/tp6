<?php

namespace app;
use think\facade\Request;

class GlobalFunction
{
    public static function buildResult($code, $msg, $data = []) {

        $result = [];
        $result['code'] = $code;
        $result['msg'] = $msg;
        $result['data'] = $data;
        return $result;
    }

    //封装验证参数是否定义  $array 参数数组  $type 请求方式 （get or post）

    public static function checkParams($array,$type)
    {
        foreach ($array as $value){
            $data=Request::has($value,$type);
            if ($data== false){
                returnJson(GlobalFunction::buildResult(ResultCodeManager::MISS_REQUIRED_PARAM, str_replace('%PARAM%', $value,  ResultCodeManager::$messages[ResultCodeManager::MISS_REQUIRED_PARAM])));

            }
        }
    }
}
