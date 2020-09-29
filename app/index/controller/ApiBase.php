<?php
/**
 * api控制器基础类
 */
namespace app\index\controller;
use app\BaseController;
use think\facade\Request;
use app\GlobalFunction;
use app\ResultCodeManager;
use app\model\SystemConfig;
use app\model\SystemAdmin;
use rsa\DBRSAUtil;

class ApiBase extends BaseController {
    private $adminId = 0;    
    public function __construct(){
        // 控制器初始化
        $info = Request::header('Authorization');
        if(request()->controller(true).'/'.request()->action(true)!='member/downloadmemeberfile'){
            if(empty($info)){
                returnJson(GlobalFunction::buildResult(ResultCodeManager::MISS_TOKEN, ResultCodeManager::$messages[ResultCodeManager::MISS_TOKEN]));
            }
            $array = explode(' ',$info);
            $private = SystemConfig::getConfigValue('private');
            
            $rsadata = DBRSAUtil::rsaDecrypt($array[1], $private);
            if($array[0] != $rsadata){
                returnJson(GlobalFunction::buildResult(ResultCodeManager::ADMIN_IS_NULL, ResultCodeManager::$messages[ResultCodeManager::ADMIN_IS_NULL]));
            }else{
                $this->adminId = $rsadata;
            }
        }
    }
    protected function getAdminId(){
        return $this->adminId;
    }
}