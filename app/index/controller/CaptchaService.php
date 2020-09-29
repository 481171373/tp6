<?php
namespace app\index\controller;
use think\facade\Session;
use think\facade\Request;
use app\BaseController;
use app\GlobalFunction;
use app\ResultCodeManager;
use app\model\SystemAdmin;
use app\model\SystemConfig;

class CaptchaService{
    
    //开始验证
    public function startCaptcha() {
        // 检测输入的验证码是否正确
        $captchaId =  SystemConfig::getConfigValue('verificationCodeId');
        $privateKey = SystemConfig::getConfigValue('verificationCodeKey');
        $codeConfigSwitch = SystemConfig::getConfigValue('codeConfigSwitch');
        if($codeConfigSwitch==0){
            returnJson(GlobalFunction::buildResult(ResultCodeManager::FAIL, '请使用验证码验证'));
        }
        $GtSdk = new GeetestLib($captchaId, $privateKey);
        $data = array(
                "user_id" => uniqid(),//$post['user_id'], # 网站用户id
                "client_type" => "web", #web:电脑上的浏览器；h5:手机上的浏览器，包括移动应用内完全内置的web_view；native：通过原生SDK植入APP应用的方式
                "ip_address" => $_SERVER["REMOTE_ADDR"], # 请在此处传输用户请求验证时所携带的IP
            );

        $status = $GtSdk->pre_process($data, 1);
        session('gtserver',$status);
        session('user_id',$data['user_id']);
        $data = json_decode($GtSdk->get_response_str(),true);
        if(isset($data['success']) && $data['success']==1){
            $data['code'] = 1;
            $data['msg'] = '成功';
        }else{
            $data['code'] = 0;
            $data['msg'] = '失败';
        }
        return json($data);
        
    }
    //验证码生成
    public function verifyCaptcha(){
        //所需参数数组
        $param = [
            'geetest_challenge',
            'geetest_validate',
            'geetest_seccode'
        ];
        //验证所需参数是否全部定义
        GlobalFunction::checkParams($param, 'post');
        $post = Request::post();
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
            if ($result) {
                returnJson(['code'=>1,'msg'=>'success']);
            } else{
                returnJson(['code'=>0,'msg'=>'fail']);
            }
        }else{  //服务器宕机,走failback模式
            if ($GtSdk->fail_validate($post['geetest_challenge'],$post['geetest_validate'],$post['geetest_seccode'])) {
                returnJson(['code'=>1,'msg'=>'success']);
            }else{
                returnJson(['code'=>0,'msg'=>'fail']);
            }
        }
    }
}
