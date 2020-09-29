<?php
namespace app\index\controller;
use think\facade\Request;
use \think\facade\Db;
use app\BaseController;
use app\GlobalFunction;
use app\ResultCodeManager;
use app\model\SystemAdmin;
use app\model\Agent;
use app\model\Company;
// 密码修改
class CommonAdminInfo extends ApiBase {
    use \app\common\TraitCommom;//上传
    /**
     *  账号信息更新
     */
    public function update()
    {
        //所需参数数组
        $param = [
            'nickname',
            'password',
            // 'avatar'
        ];
        //验证所需参数是否全部定义
        GlobalFunction::checkParams($param, 'post');
        $post = Request::post();
        $post['password'] = md5($post['password']);
        $model = SystemAdmin::where('id',$this->getAdminId())->find();
        // 合伙人账号密码更新
        if($model['power']==1){
        	$model->save($post);
        // 代理商账号密码更新
        }elseif ($model['power']==2) {
        	if($model['nickname']!=$post['nickname']){
        		Agent::where('admin_id',$model['id'])->update(['name'=>$post['nickname']]);
        	}
        	$model->save($post);
        // 企业账号密码更新
        }elseif ($model['power']==3) {
        	if($model['nickname']!=$post['nickname']){
        		Company::where('admin_id',$model['id'])->update(['name'=>$post['nickname']]);
        	}
        	$model->save($post);
        }
        returnJson(GlobalFunction::buildResult(ResultCodeManager::SUCCESS, '更新成功'));        
    }
    /**
    *  author change
    *  图片上传
    */
    public function image(){
        $res = $this->upload('image',1,'/index/admin',1,2,'jpg,jpeg,png,gif');
        returnJson(GlobalFunction::buildResult(ResultCodeManager::SUCCESS, ResultCodeManager::$messages[ResultCodeManager::SUCCESS],$res));
    }
}
