<?php  
namespace app\common;
// 短信
use AlibabaCloud\Client\AlibabaCloud;
use AlibabaCloud\Client\Exception\ClientException;
use AlibabaCloud\Client\Exception\ServerException;
use app\model\MerchantSystemConfig;
use app\model\SystemConfig;
// 支付（支付宝微信）集成方案
use Yansongda\Pay\Pay;
// 事件
use Yansongda\Pay\Events;
use Yansongda\Pay\Events\ApiRequested;
use app\index\controller\ApiRequestResult;
// 七牛云文件上传
use Qiniu\Storage\UploadManager;
use Qiniu\Auth;
/**
 * 公共方法类
 */
trait TraitCommom {
    /*
    * 微信支付 data array
    *   mer_id        商户id
    *   body          描述
    *   out_trade_no  订单号
    *   total_fee     付款金额（元）
    *   openid        openid （公众号小程序支付必传）
    *   auth_code     扫码参数(商户扫码必传)
    *   pay_type      支付类型 1公众号支付 2小程序支付 3扫码支付 4付款码支付
    *   pay_pattern   支付模式：普通1 服务商2
    * notify        是否支持回调：1支持 2不支持
    */
    public function wechatPay($data,$notify=1)
    {
        
        // 检查是否启用
        // $status = MerchantSystemConfig::getConfigValue($data['mer_id'],'weiStatus');
        // if($status==0){
        //     return ['code'=>0,'msg'=>'未启用'];
        // }
        // 参数
        $order = [
            'out_trade_no' => $data['out_trade_no'],
            'total_fee' => $data['total_fee']*100,
            'body' => $data['body'],
        ];
        if($data['pay_type']==1 || $data['pay_type']==2){
            if($data['pay_pattern']==1){
                $order['openid'] = $data['openid'];
            }elseif($data['pay_pattern']==2){
                $order['sub_openid'] = $data['openid'];
            }
            
        }elseif($data['pay_type']==4){
            $order['auth_code'] = $data['auth_code'];
        }
        $pay = Pay::wechat($this->config($data['mer_id'],$data['pay_type'],$data['pay_pattern'],$notify));
        // 2. 添加监听器
        Events::addListener(ApiRequested::class, [new ApiRequestResult(), 'sendEmail']);
        // 支付请求
        if($data['pay_type']==1){
            $pay = $pay->mp($order);
        }elseif ($data['pay_type']==2) {
            $pay = $pay->miniapp($order);
        }elseif ($data['pay_type']==3) {
            $pay = $pay->scan($order);
        }elseif ($data['pay_type']==4) {
            $pay = $pay->pos($order);
        }
        return json_decode($pay,true);
    }
    /*
    * 微信订单查询 
    * mer_id        商户id
    * out_trade_no  订单号
    * pay_type      支付类型 1公众号支付 2小程序支付 3扫码支付 4付款码支付
    * type          查询类型 1普通 2退款订单查询
    * pay_pattern   支付模式：普通1 服务商2
    */
    public function wechatSelect($data)
    {
        
        // 检查是否启用
        // $status = MerchantSystemConfig::getConfigValue($data['mer_id'],'weiStatus');
        // if($status==0){
        //     return ['code'=>0,'msg'=>'未启用'];
        // }
        // 参数
        $order = [
            'out_trade_no' => $data['out_trade_no'],
        ];
        if($data['type']==1){
            $pay = Pay::wechat($this->config($data['mer_id'],$data['pay_type'],$data['pay_pattern']))->find($order);
        }else{
            $pay = Pay::wechat($this->config($data['mer_id'],$data['pay_type'],$data['pay_pattern']))->find($order,'refund');
        }
        return json_decode($pay,true);
    }
    /*
    * 微信退款
    * mer_id        商户id
    * refund_desc   退单描述
    * out_trade_no  订单号
    * total_fee     付款金额（元）
    * refund_fee    退款金额（元）
    * out_refund_no 退单号
    * pay_type      支付类型 1公众号支付 2小程序支付 3扫码支付 4付款码支付
    * pay_pattern   支付模式：普通1 服务商2
    */
    public function wechatRefund($data)
    {
    
        // 检查是否启用
        // $status = MerchantSystemConfig::getConfigValue($data['mer_id'],'weiStatus');
        // if($status==0){
        //     return ['code'=>0,'msg'=>'未启用'];
        // }
        // 参数
        $order = [
            'out_trade_no' => $data['out_trade_no'],
            'out_refund_no' => $data['out_refund_no'],
            'total_fee' => $data['total_fee']*100,
            'refund_fee' => $data['refund_fee']*100,
            'refund_desc' => $data['refund_desc'],
        ];
        $pay = Pay::wechat($this->config($data['mer_id'],$data['pay_type'],$data['pay_pattern']))->refund($order);
        return json_decode($pay,true);
    }
    /*
    * 微信支付配置
    * merId   商户id
    * type    支付类型 1公众号支付 2小程序支付 3扫码支付 4付款码支付
    * pay_pattern   支付模式：普通1 服务商2
    * notify  是否支持回调：1支持 2不支持
    */
    public function config($merId=3,$type,$pay_pattern,$notify=1){
        
        // 支付模式：普通1
        if($pay_pattern==1){
            $authentication['app_id']=MerchantSystemConfig::getConfigValue($merId,'weiAppID');//服务商appid
            $authentication['mch_id']=MerchantSystemConfig::getConfigValue($merId,'weiMchID');
            $authentication['key']=MerchantSystemConfig::getConfigValue($merId,'weiKey');
            $authentication['cert_client']= $_SERVER['DOCUMENT_ROOT'].MerchantSystemConfig::getConfigValue($merId,'weiApiclientCert ');
            $authentication['cert_key']= $_SERVER['DOCUMENT_ROOT'].MerchantSystemConfig::getConfigValue($merId,'weiApiclientKey');
            // 小程序支付小程序appid
            if($type==2){
                $authentication['miniapp_id']=MerchantSystemConfig::getConfigValue($merId,'xcxAppID');//小程序appid
            }
        }else{
        // 支付模式：服务商2
            $authentication['app_id']=SystemConfig::getConfigValue('weiAppID');
            $authentication['mch_id']=SystemConfig::getConfigValue('weiMchID');
            $authentication['key']=SystemConfig::getConfigValue('weiKey');
            $authentication['cert_client']= $_SERVER['DOCUMENT_ROOT'].SystemConfig::getConfigValue('weiApiclientCert ');
            $authentication['cert_key']= $_SERVER['DOCUMENT_ROOT'].SystemConfig::getConfigValue('weiApiclientKey');
            if($type==2){
                $authentication['sub_mch_id']= MerchantSystemConfig::getConfigValue($merId,'serviceXcxMchID');
            }else{
                $authentication['sub_mch_id']= MerchantSystemConfig::getConfigValue($merId,'weChatMchID');
            }
            // 小程序支付小程序appid
            if($type==2){
                $authentication['miniapp_id']=SystemConfig::getConfigValue('weiAppID');//小程序appid
                $authentication['sub_miniapp_id']= MerchantSystemConfig::getConfigValue($merId,'serviceXcxAppID');//小程序appid
            }
            $authentication['mode']= 'service';
        }
        // 是否回调
        if($notify==1){
            $domain = SystemConfig::getConfigValue('domainName');
            $authentication['notify_url'] = 'http://'.$domain.'/index/WechatPayNotify/orderResult';
        }
        return $authentication;
    }
    /*
    * 支付宝支付 
    * data array
    *   mer_id        商户id
    *   subject       描述
    *   out_trade_no  订单号
    *   total_amount  付款金额（元）
    *   auth_code     付款码参数(商户付款码必传)
    *   pay_type      支付类型 1扫码支付 2付款码支付
    *   pay_pattern   支付模式：普通1 服务商2
    *   notify  是否支持回调：1支持 2不支持
    */
    public function alipay($data,$notify=1)
    {
        $order = [
            'out_trade_no' => $data['out_trade_no'],
            'total_amount' => $data['total_amount'],
            'subject' => $data['subject'],
        ];
        if($data['pay_type']==1){
            $alipay = Pay::alipay($this->alipayConfig($data['mer_id'],$data['pay_pattern'],$notify))->scan($order);
        }elseif ($data['pay_type']==2) {
            $order['auth_code'] = $data['auth_code'];
            $alipay = Pay::alipay($this->alipayConfig($data['mer_id'],$data['pay_pattern'],$notify))->pos($order);
        }
        return json_decode($alipay,true);
    }

    /*
    * 支付宝订单查询
    * mer_id        商户id
    * out_trade_no  订单号
    * out_request_no退单号(查询退单时需传)
    * type          查询类型 1普通查询方式 2退单查询
    * pay_pattern   支付模式：普通1 服务商2
    */
    public function alipaySelect($data)
    {
        $order = [
            'out_trade_no' => $data['out_trade_no']
        ];
        if($data['type']==1){
            $alipay = Pay::alipay($this->alipayConfig($data['mer_id'],$data['pay_pattern']))->find($order);
        }elseif ($data['type']==2) {
            $order['out_trade_no'] = $data['out_request_no'];
            $alipay = Pay::alipay($this->alipayConfig($data['mer_id'],$data['pay_pattern']))->find($order,'refund');
        }
        return json_decode($alipay,true);
    }

    /*
    * 支付宝退款
    * mer_id        商户id
    * out_trade_no  订单号
    * refund_amount 退款金额
    * pay_pattern   支付模式：普通1 服务商2
    */
    public function alipayRefund($data)
    {
        $order = [
            'out_trade_no' => $data['out_trade_no'],
            'refund_amount' => $data['refund_amount']
        ];
        
        $alipay = Pay::alipay($this->alipayConfig($data['mer_id'],$data['pay_pattern']))->refund($order);
        return json_decode($alipay,true);
        
    }
    /*
    * 支付宝支付配置
    * merId   商户id
    * method  支付模式：普通1 服务商2
    * notify  是否支持回调：1支持 2不支持
    * 注释部分为证书模式
    */
    public function alipayConfig($merId,$method=2,$notify=1){
        // 普通模式
        if($method==1){
            $authentication['app_id']= MerchantSystemConfig::getConfigValue($merId,'alipayAppID');
            $authentication['ali_public_key'] = MerchantSystemConfig::getConfigValue($merId,'alipayPublicKey ');
            // $_SERVER['DOCUMENT_ROOT'].'/uploads/index/admin/alipayCertPublicKey_RSA2.crt';//支付宝公钥证书
            $authentication['private_key'] = MerchantSystemConfig::getConfigValue($merId,'alipayPrivateKey');
            // $authentication['alipay_root_cert'] = $_SERVER['DOCUMENT_ROOT'].'/uploads/index/admin/alipayRootCert.crt';//支付宝根证书路径
            // $authentication['app_cert_public_key'] = $_SERVER['DOCUMENT_ROOT'].'/uploads/index/admin/appCertPublicKey_2021001161685735.crt'; //应用公钥证书路径
        // 服务商模式
        }else{
            $authentication['app_id']= SystemConfig::getConfigValue('alipayAppID');
            $authentication['ali_public_key'] = SystemConfig::getConfigValue('alipayPublicKey ');
            // $_SERVER['DOCUMENT_ROOT'].'/uploads/index/admin/alipayCertPublicKey_RSA2.crt';//支付宝公钥证书
            $authentication['private_key'] = SystemConfig::getConfigValue('alipayPrivateKey');
            $authentication['app_auth_token']=MerchantSystemConfig::getConfigValue($merId,'serviceAlipayToken');
            // $authentication['alipay_root_cert'] = $_SERVER['DOCUMENT_ROOT'].'/uploads/index/admin/alipayRootCert.crt';//支付宝根证书路径
            // $authentication['app_cert_public_key'] = $_SERVER['DOCUMENT_ROOT'].'/uploads/index/admin/appCertPublicKey_2021001161685735.crt'; //应用公钥证书路径
        }
        // 是否支持回调
        if($notify==1){
            $domain = SystemConfig::getConfigValue('domainName');
            $authentication['notify_url'] = 'http://'.$domain.'/index/alipaynotify/'.$merId;
        }
        return $authentication;
        
    }
    /** 
     * 新大陆接口数字签名方法
     * @param $data  签名参数
     * @param merId  商户id
     */
    public function getSign($merId,$data)
    {

        $key = '9FF13E7726C4DFEB3BED750779F59711';
        $arr = array_change_key_case($data, CASE_LOWER);
        ksort($arr);
        $str = "";
        foreach ($arr as $val) {
            $str .= trim(str_replace(" ", "", $val));
        }
        $str .= $key;
        return MD5($str);
    }

    /** 新大陆支付公共参数
     * @param merId    商户id
     * @param orderNo  订单号 
     * @param txnTime  设备端交易时间或者订单创建时间(格式：20200519161104)
     */
    public function param($merId, $orderNo,$txnTime)
    {

        $arrData['opSys'] = 0;  //操作系统
        $arrData['characterSet'] = '00';
        $arrData['orgNo'] = '11658';//机构号
        $arrData['mercId'] = '800290000007906';  //商户号
        $arrData['version'] = 'V1.0.0'; //版本号
        $arrData['trmNo'] = 'XB006439';    //设备号
        $arrData['tradeNo'] = $orderNo; //商户端订单号 
        $arrData['txnTime'] = $txnTime?$txnTime:date('YmdHis'); //设备端交易时间
        $arrData['signType'] = 'MD5'; //签名方式
        return $arrData;
    }
    /**
     *  支付模式查询：普通1 服务商2 新大陆3
     *  flat  1支付宝 2微信 3小程序
     *  merId 商户id 
    */
    public function payPattern($merId,$flat){
        // 支付宝
        if($flat==1){
            // 普通模式
            $status = MerchantSystemConfig::getConfigValue($merId,'alipayStatus');
            if($status){
                return 1;
            }else{
                // 服务商模式
                $serviceStatus = MerchantSystemConfig::getConfigValue($merId,'serviceAlipayStatus');
                if($serviceStatus){
                    return 2;
                }else{
                    // 付呗
                    $xdlStatus = MerchantSystemConfig::getConfigValue($merId,'fubeiMode');
                    if($xdlStatus){
                         return 3;
                     }else{
                        return false;
                     }
                }
            }
        // 微信
        }elseif($flat==2){
            // 普通模式
            $wechatStatus = MerchantSystemConfig::getConfigValue($merId,'weiStatus');
            if($wechatStatus){
                return 1;
            }else{
                // 服务商模式
                $wechatServiceStatus = MerchantSystemConfig::getConfigValue($merId,'weChatStatus');
                if($wechatServiceStatus){
                    return 2;
                }else{
                    // 付呗
                    $wechatXdlStatus = MerchantSystemConfig::getConfigValue($merId,'fubeiMode');
                    if($wechatXdlStatus){
                        return 3;
                    }else{
                        return false;
                    }
                }
            }
        // 小程序
        }elseif($flat==3){
            // 普通模式 pattern：1普通 2服务商  balance：1开启余额 0未开启
            $xcxWeChatStatus = MerchantSystemConfig::getConfigValue($merId,'xcxWeChatStatus');//微信支付
            // $xcxBalanceStatus = MerchantSystemConfig::getConfigValue($merId,'xcxBalanceStatus');//余额支付
            if($xcxWeChatStatus){
                return 1;
            }else{
                // 服务商模式
                $serviceXcxWeChatStatus = MerchantSystemConfig::getConfigValue($merId,'serviceXcxWeChatStatus');
                if($serviceXcxWeChatStatus){
                    return 2;
                }else{
                    // 付呗
                    $wechatXdlStatus = MerchantSystemConfig::getConfigValue($merId,'fubeiMode');
                    if($wechatXdlStatus){
                        return 3;
                    }else{
                        return false;
                    }
                }
            }
        }
    }
    /**
     *  阿里云短信处理（验证码发送、查询、签名……详情可参考阿里云短信文档）
     *  flat  int 平台(1阿里云短信,2未定) 
     *  action  针对阿里云平台使用的方法 （1短信发送 2短信查询）
     *  authentication array  身份认证（以下为阿里云短信认证参数） 
     *                  AccessKeyId 用于标识用户。
     *                  AccessKeySecret 是用来验证用户的密钥
     *  data array  详细参数
     *   短信发送参数
     *          RegionId        默认为cn-hangzhou
     *          PhoneNumbers    发送到的手机号码多个手机号用逗号隔开
     *          SignName        签名
     *          TemplateCode    发送模板名
     *          TemplateParam   发送变量参数
     *   短信查询参数
     *          RegionId        默认为cn-hangzhou
     *          PhoneNumber     发送到的手机号码
     *          SendDate        短信发送日期，支持查询最近30天的记录。格式为yyyyMMdd，例如20181225。
     *          PageSize        分页查看发送记录，指定每页显示的短信记录数量。取值范围为1~50。
     *          CurrentPage     分页查看发送记录，指定发送记录的的当前页码。
     *
    */
    public function smsApi($flat=1,$data,$authentication,$action=1){

        if($flat==1){
            // 启用不同的方法
            if($action==1){
                $action = 'SendSms';
            }elseif ($action==2) {
                $action = 'QuerySendDetails';
            }
            AlibabaCloud::accessKeyClient($authentication['AccessKeyId'],$authentication['AccessKeySecret'])
                                ->regionId('cn-hangzhou')
                                ->asDefaultClient();
            try {
                $result = AlibabaCloud::rpc()
                  ->product('Dysmsapi')
                  // ->scheme('https') // https | http
                  ->version('2017-05-25')
                  ->action($action)
                  ->method('POST')
                  ->host('dysmsapi.aliyuncs.com')
                  ->options([
                        'query' => $data
                    ])
                  ->request();
                return $result->toArray();
            } catch (ClientException $e) {
                echo $e->getErrorMessage() . PHP_EOL;
            } catch (ServerException $e) {
                echo $e->getErrorMessage() . PHP_EOL;
            }
        }else{
            return '未启用其他平台';
        }
    }
    /**
     *  文件上传
     *  fileName  string 表单的name参数 images
     *  file_method  int 单多文件上传  1是单文件 2多文件
     *  apply string 上传目录(要求定位到应用) /index/agent
     *  location int  上传位置（1本地 2七牛云）
     *  fileSize  int 图片大小（mb） 1
     *  fileSuffix string 后缀名  jpg,jpeg,png,gif
     *  excel     excel 上传   0 本地
     */
    public function upload($fileName='images',$file_method=2,$apply='/index',$location=1,$fileSize=2,$fileSuffix='jpg,jpeg,png,gif',$dir='public',$excel=1){
        $location = 0;//SystemConfig::getConfigValue('imageStorageSwitch'); //上传空间
        if($excel== 0){
            $location = 0;
        }
        $fileSize = $fileSize*1024*1024;
        $condition = 'filesize:'.$fileSize.'|fileExt:'.$fileSuffix;
        // 获取表单上传文件
        $files = request()->file();
        try {
            validate([$fileName=>$condition])->check($files);
            if($file_method==1){
                $savename = \think\facade\Filesystem::disk($dir)->putFile( $apply, $files[$fileName],'md5');
            }elseif($file_method==2){
                $savename = [];
                foreach($files[$fileName] as $file) {
                    $savename[] = \think\facade\Filesystem::disk($dir)->putFile( $apply, $file,'md5');
                }
            }
            if($location==0){
                return '/uploads/'.$savename;
            }elseif($location==1){
                // 七牛云文件上传   
                $qiniuyunAccessKey = SystemConfig::getConfigValue('qiniuyunAccessKey'); 
                $qiniuyunSecretKey = SystemConfig::getConfigValue('qiniuyunSecretKey'); 
                $qiniuyunStorageSpace = SystemConfig::getConfigValue('qiniuyunStorageSpace'); 
                $qiniuyunDomainName = SystemConfig::getConfigValue('qiniuyunDomainName');  
                $auth = new Auth($qiniuyunAccessKey, $qiniuyunSecretKey);
                // 生成上传 Token
                $token = $auth->uploadToken($qiniuyunStorageSpace);
                // 要上传文件的本地路径
                $filePath = './uploads/'.$savename;                
                // 文件名
                $fileNewName = $_FILES[$fileName]["name"];
                $fileNameImg = explode('.',$fileNewName)[1];
                // 上传到七牛后保存的文件名
                $key = substr($apply.'/'.md5('$fileNewName'.time()).'.'.$fileNameImg, 1);
                // 初始化 UploadManager 对象并进行文件的上传。
                $uploadMgr = new UploadManager();
                // 调用 UploadManager 的 putFile 方法进行文件的上传。
                list($ret, $err) = $uploadMgr->putFile($token, $key, $filePath);
                if ($err !== null) {
                    return $err;
                } else {
                    unlink($filePath);
                    return $qiniuyunDomainName.$ret['key'];
                }
            }
            
        } catch (\think\exception\ValidateException $e) {
            echo $e->getMessage();
        }
    }

    /**
     *  微信授权
     *  appID  开发者ID
     *  appsecret  开发者密码
     */
    public function weChat($appID,$appsecret){

        $url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.$appID.'&secret='.$appsecret;
        $res  = $this->http_curl($url,'get');
        return $res;   
    }

    /**
    *  author change
    *  自定义菜单修改
    *  access_token  接口调用凭证
    *  postJson  菜单数据
    */
    public function weChatDefindMenu($access_token,$postJson){

        $url = 'https://api.weixin.qq.com/cgi-bin/menu/create?access_token='.$access_token;
        $res  = $this->http_curl($url,'post',$postJson);
        if(!$res['errcode']){
            return ['code'=>1,'msg'=>'菜单修改成功'];
        }else{
            return $res;
        }
    }

    /**
    *  author change
    *  自定义菜单查询
    *  access_token  接口调用凭证
    */
    public function weChatMenuSelect($access_token){

        $url = 'https://api.weixin.qq.com/cgi-bin/get_current_selfmenu_info?access_token='.$access_token;
        $res  = $this->http_curl($url,'get','json');
        return $res; 
    }

    /**
    *  author change  
    *  通配接口调用
    */
    public function http_curl($url,$type='get',$arr='',$header_type = null,$https=1){
        //1.初始化url
        $ch = curl_init();
        //2.设置url参数
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        if($type=='post'){ 
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $arr);
        }
        // 带请求头
        if ($header_type != null && is_array($header_type)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header_type);
        }
        // 安全模式
        if ($https==1) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        }
        //3.采集
        $response = curl_exec($ch);
        //4.关闭
        curl_close($ch);
        return json_decode($response,true);
    }


    /**
     * 二维码生成
     * @param int $type 1-生成二维码不下载  2-下载二维码图片
     * @param string $data  二维码显示的数据
     * @param string $dir  下载文件的目录
     * @param string $suffix  下载文件的后缀
     * @param int $size 二维码尺寸
     * @param string $logPath   二维码中间图标的路径
     * @throws \Endroid\QrCode\Exception\InvalidPathException
     */
    public function createQrcode($type = 2, $data = 'http://www.baidu.com', $dir='html', $suffix = 'png', $size = 100, $logPath = ''){

        $qrCode = new \Endroid\QrCode\QrCode($data);
        $qrCode->setSize($size);
        if ($logPath != ''){
            $qrCode->setLogoPath($logPath);
            $qrCode->setLogoSize(30, 30);
        }

//        header('Content-Type: '.$qrCode->getContentType());
        if ($type == 1){

            echo $qrCode->writeString();
        }elseif ($type == 2){

            if(!is_dir(QRCODE_PATH.'/'.$dir)){
                mkdir(iconv("UTF-8", "GBK", QRCODE_PATH.'/'.$dir),0777,true);
            }
             $url = QRCODE_PATH.'/'.$dir.'/'.time().'.'.$suffix;
            $qrCode->writeFile($url);
              return $url;
        }
        exit();
    }
}