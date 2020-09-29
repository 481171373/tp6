<?php
namespace app\index\controller;
use think\captcha\facade\Captcha;
use think\facade\Request;
use app\BaseController;
use app\GlobalFunction;
use app\ResultCodeManager;
use app\model\SystemAdmin;
use app\model\SystemConfig;
use rsa\DBRSAUtil;
use app\model\Agent;
use app\model\SitTable;
class Admin extends BaseController {
    
    //登录处理
    public function login() {

        //所需参数数组
        $param = [
            'username',
            'password',
            // 'captcha'
            // 'geetest_challenge',
            // 'geetest_validate',
            // 'geetest_seccode'
        ];
        //验证所需参数是否全部定义
        GlobalFunction::checkParams($param, 'post');
        $post = Request::post();
        // 检测输入的验证码是否正确
        if(isset($post['captcha']) && !empty($post['captcha'])){
            $captcha = new Captcha();
            if(!captcha_check($post['captcha'])){
                returnJson(GlobalFunction::buildResult(ResultCodeManager::CAPTCHA_ERROR, ResultCodeManager::$messages[ResultCodeManager::CAPTCHA_ERROR]));
            }
        }
        // 检测极验验证是否正确
        elseif(isset($post['geetest_challenge']) && !empty($post['geetest_challenge']) && isset($post['geetest_validate']) && !empty($post['geetest_validate']) && isset($post['geetest_seccode']) && !empty($post['geetest_seccode'])){
            $captchaId =  SystemConfig::getConfigValue('verificationCodeId');
            $privateKey = SystemConfig::getConfigValue('verificationCodeKey');
            $GtSdk = new GeetestLib($captchaId, $privateKey);
            $data = array(
                    "user_id" => session('user_id'), # 网站用户id
                    "client_type" => "web", #web:电脑上的浏览器；h5:手机上的浏览器，包括移动应用内完全内置的web_view；native：通过原生SDK植入APP应用的方式
                    "ip_address" => $_SERVER["REMOTE_ADDR"], # 请在此处传输用户请求验证时所携带的IP
                );
            if (session('gtserver') == 1) {   //服务器正常
                $result = $GtSdk->success_validate($post['geetest_challenge'], $post['geetest_validate'], $post['geetest_seccode'], $data);
                if (!$result) {
                    returnJson(['code'=>0,'msg'=>'验证失败']);
                } 
            }else{  //服务器宕机,走failback模式
                if (!$GtSdk->fail_validate($post['geetest_challenge'],$post['geetest_validate'],$post['geetest_seccode'])) {
                    returnJson(['code'=>0,'msg'=>'验证失败']);
                }
            }
        }else{
            returnJson(['code'=>0,'msg'=>'请先验证']);
        }
        $SystemAdmin = SystemAdmin::where(['username'=>$post['username'],'password'=>md5($post['password']),'is_del'=>0,'is_disabled'=>0])->select()->toArray();
        if(empty($SystemAdmin)){
            returnJson(GlobalFunction::buildResult(ResultCodeManager::FAIL, '用户名或密码错误'));
        }
        // 返回加密权限
        foreach ($SystemAdmin as $key=>$val) {
            if($val['password'] == md5($post['password'])){
                $public_key = SystemConfig::getConfigValue('public');
                $power[$key]['power'] = DBRSAUtil::rsaEncrypt($val['power'], $public_key);
                $power[$key]['token'] = DBRSAUtil::rsaEncrypt($val['id'], $public_key);
                $power[$key]['id'] = $val['id'];
                $power[$key]['username'] = $val['username'];
                $power[$key]['nickname'] = $val['nickname'];
                $power[$key]['avatar'] = $val['avatar'];
                $power[$key]['company_id'] = $val['company_id'];
                $power[$key]['create_time'] = $val['create_time'];
                $power[$key]['expired_status'] = 1;
                // // 代理商过期检查  
                if($val['power']==2){
                    // 有效期检测
                    $Agent = Agent::where('admin_id',$power[$key]['id'])->find();
                    if($Agent['expired_time']<time() || $Agent['start_time']>time()){
                        $power[$key]['expired_status'] = 0; 
                    } 
                    // 删除检测 
                    if($Agent['is_delete']==1){
                        unset($power[$key]);
                    }
                }
                // // 商户过期检查
                if($val['power']==3 && $val['is_super']==0){
                    // 有效期检测
                    $SitTable = SitTable::where('admin_id',$power[$key]['id'])->find();
                    if($SitTable['expired_time']<time() || $SitTable['start_time']>time()){
                        $power[$key]['expired_status'] = 0; 
                    }  
                    // 删除检测  
                    if($SitTable['is_del']==1){
                        unset($power[$key]);
                    }                  
                }                      
            }
        }
        $power = array_merge($power);
        if(empty($power)){
            returnJson(GlobalFunction::buildResult(ResultCodeManager::NO_USER_DATA, ResultCodeManager::$messages[ResultCodeManager::NO_USER_DATA]));
        }else{
            
            returnJson(GlobalFunction::buildResult(ResultCodeManager::SUCCESS, ResultCodeManager::$messages[ResultCodeManager::SUCCESS],$power));
        }
        
    }
    //验证码生成
    public function captcha(){
        return Captcha::create(); 
    }
}
