<?php
namespace app\common\controller;

use app\BaseController;
use think\facade\Request;
use app\GlobalFunction;
use app\ResultCodeManager;
use app\model\Citys;
// 城市联动公共方法
class City extends BaseController
{
    // 省份
    public function provice()
    {
        $res = Citys::where('parentid',0)->select();
        returnJson(GlobalFunction::buildResult(ResultCodeManager::SUCCESS, ResultCodeManager::$messages[ResultCodeManager::SUCCESS],$res));
    }

    // 城市、区域
    public function getCity()
    {
        $param = [
            'parentid',
        ];
        //验证所需参数是否全部定义
        GlobalFunction::checkParams($param, 'post');
        $post = Request::post();
        $res = Citys::where($post)->select();
        returnJson(GlobalFunction::buildResult(ResultCodeManager::SUCCESS, ResultCodeManager::$messages[ResultCodeManager::SUCCESS],$res));
    }
}
