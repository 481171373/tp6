<?php
namespace app\index\controller;
use think\facade\Request;
use \think\facade\Db;
use app\BaseController;
use app\GlobalFunction;
use app\ResultCodeManager;
use app\model\SystemAdmin;
use app\model\Company;
use app\model\Agent;
use app\model\SitTable;

// 坐席设置
class CpSitTableSet extends ApiBase {    
    /**
     *  坐席列表
     */
    public function sitTableList()
    {
        //所需参数数组
        $param = [
            'pagesize',
            'page',
            'bindStatus', //绑定状态: 0-未绑定 1-绑定 2-全部
            'status' 	  //过期状态: 0-过期 1-正常 2-全部
            // 'name'		//搜索：姓名、手机号、序号
        ];
        //验证所需参数是否全部定义
        GlobalFunction::checkParams($param, 'post');
        $post = Request::post();
        // 绑定状态
        if($post['bindStatus']==0){
        	$map[] = ['a.admin_id','=',0];
        }elseif ($post['bindStatus']==1) {
        	$map[] = ['a.admin_id','<>',0];
        }
        // 过期状态
        if($post['status']!=2){
        	$map[] = ['a.status','=',$post['status']];
        }
        // 搜索
        if(isset($post['name']) && !empty($post['name'])){
        	$map[] = ['b.nickname','like','%'.$post['name'].'%'];
        }
        $map[] = ['a.is_del','=',0];
        $map[] = ['a.company_id','=',SystemAdmin::where('id',$this->getAdminId())->value('company_id')];// 企业id
    	$res = Db::name('sit_table')
    		->field('a.id,a.start_time,a.expired_time,a.type,a.company_id,a.status,a.admin_id,b.username,b.nickname')
    		->alias('a')
    		->leftjoin('system_admin b','b.id = a.admin_id')
    		->where($map)
    		->order('a.id desc')
    		->paginate(['list_rows'=>$post['pagesize'],'page'=>$post['page']])
    		->toArray();
        // 过期状态更新
        foreach ($res['data'] as &$value) {
            if($value['expired_time']<time()){
                $value['status'] = 0;
                SitTable::update(['id'=>$value['id'],'status'=>0]);
            }
        }
        returnJson(GlobalFunction::buildResult(ResultCodeManager::SUCCESS, '查询成功',$res)); 
    }
    /**
     *  坐席绑定
     */
    public function sitTableBind()
    {
        //所需参数数组
        $param = [
            'user_id',	//绑定关联账号id
            'sitTableId', //坐席id
        ];
        //验证所需参数是否全部定义
        GlobalFunction::checkParams($param, 'post');
        $post = Request::post();
        $model = SitTable::where('admin_id',$post['user_id'])->find();
        $sitTable = SitTable::find($post['sitTableId']);
    	if($model){
    		returnJson(GlobalFunction::buildResult(ResultCodeManager::FAIL, '请勿重复绑定')); 
    	}else{
    		$sitTable->admin_id = $post['user_id'];
    		$sitTable->save();
    	} 
        returnJson(GlobalFunction::buildResult(ResultCodeManager::SUCCESS, '成功'));     
    }
    /**
     *  删除
     */
    public function delete()
    {
        //所需参数数组
        $param = [
            'id',
        ];
        //验证所需参数是否全部定义
        GlobalFunction::checkParams($param, 'post');
        $post = Request::post();
        $post['is_del'] = 1;
        SitTable::update($post);
        returnJson(GlobalFunction::buildResult(ResultCodeManager::SUCCESS, '删除成功'));       
    }
}
