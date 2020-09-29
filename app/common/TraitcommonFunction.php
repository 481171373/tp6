<?php  
namespace app\common;
use think\facade\Db;
use app\model\SystemConfig;
use app\model\WechatAuthRecord;
/**
 * 公共方法
 */
trait TraitcommonFunction {
	/*
    * 服务商获取微信访问token
    */
    public function  componentAccessToken(){
        // 读取解密后的验证票据
        $component_verify_ticket = SystemConfig::getConfigValue('ComponentVerifyTicket');
        //获取令牌
        $url = 'https://api.weixin.qq.com/cgi-bin/component/api_component_token';
        $arr = [
                'component_appid'=>self::secret()[0],
                'component_appsecret'=>self::secret()[1],
                'component_verify_ticket'=>$component_verify_ticket,
        ];
        $arr = json_encode($arr);
        $header[] = "Content-Type: application/json";
        $res = commonCurl($url,'post',$arr,$header);
        if(isset($res['component_access_token'])){
            SystemConfig::updateConfigValue('component_access_token',$res['component_access_token']);
            SystemConfig::updateConfigValue('expire_time',time());
            return $res['component_access_token'];
        }else{
            return $res;
        }
    }
    /*
    * 服务商访问令牌获取
    */
    public function accessToken(){
        $expire_time = SystemConfig::getConfigValue('expire_time');
        if(($expire_time+6600)>time()){
            $component_access_token = SystemConfig::getConfigValue('component_access_token');
        }else{
            $component_access_token = $this->componentAccessToken();
        }
        return $component_access_token;
    }
    /*
    * 配置获取
    */
    public static function secret(){
        $appSecret = SystemConfig::getConfigValue('weChatAppSecret');
        $appId = SystemConfig::getConfigValue('weChatAppID');
        return [$appId,$appSecret];
    }

    /*
    * 获取/刷新授权 商家 接口调用令牌
    * type 类型 1公众号 2小程序 3支付宝 
    * mer_id 商户id
    */
    public function authorizerRefreshToken($type,$mer_id){
        $component_access_token = $this->accessToken();      
        $url = 'https://api.weixin.qq.com/cgi-bin/component/api_authorizer_token?component_access_token='.$component_access_token;
        $model = WechatAuthRecord::where(['mer_id'=>$mer_id,'type'=>$type])->find();
        $arr = [
            'component_appid'=>self::secret()[0],
            'authorizer_appid'=> $model['authorizer_appid'],
            'authorizer_refresh_token'=>$model['authorizer_refresh_token']
        ];

        $arr = json_encode($arr);
        $header[] = "Content-Type: application/json";
        $res = commonCurl($url,'post',$arr,$header);
        if(isset($res['authorizer_access_token'])){
        	$model->save(['authorizer_access_token'=>$res['authorizer_access_token'],'expire_time'=>time()]);
        	return $res['authorizer_access_token'];
        }else{
        	returnJson($res);
        }
        
    }
}