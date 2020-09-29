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

// 企业相关信息
class AnCompanyManage extends ApiBase {
    
    //企业列表
    public function index() {

        //所需参数数组
        $param = [
            'pagesize',
            'page',
            // 'name'//代理商姓名
        ];
        //验证所需参数是否全部定义
        GlobalFunction::checkParams($param, 'post');
        $post = Request::post();
        // 搜索
        if(isset($post['name']) && !empty($post['name'])){
            $name = $post['name'];
            $map[] = ['name|company_name|phone','like',"%$name%"];
        }
        $map[] = ['is_del','=',0];
        $res = Company::where($map)
        	->order('id desc')
        	->paginate(['list_rows'=>$post['pagesize'],'page'=>$post['page']]);
        returnJson(GlobalFunction::buildResult(ResultCodeManager::SUCCESS, ResultCodeManager::$messages[ResultCodeManager::SUCCESS],$res));        
    }
    // 添加
    public function add()
    {
        //所需参数数组
        $param = [
            'company_name',
            'name',
            'phone',
            'retail_addr',
            'password'
        ];
        //验证所需参数是否全部定义
        GlobalFunction::checkParams($param, 'post');
        $post = Request::post();
        // 事务
        Db::startTrans();
        try {
        	$post['agent_id'] = Agent::where('admin_id',$this->getAdminId())->value('id');
            // 创建登录数据
            $userData['username'] = $post['phone'];
            $userData['nickname'] = $post['name'];
            $userData['power'] = 3;
            $userData['parent_id'] = $this->getAdminId();
            $userData['password'] = md5($post['password']);
            // 注册号码检测
            $Admin = SystemAdmin::where(['username'=>$post['phone'],'power'=>3,'is_del'=>0])->find();
            if($Admin){
                returnJson(GlobalFunction::buildResult(ResultCodeManager::FAIL, '用户已存在'));
            }
            // 新增代理商
            $SystemAdmin = new SystemAdmin();
            $SystemAdmin->save($userData);
            // 代理商关联信息
            $post['admin_id'] = $SystemAdmin->id;
            $company = new Company();
            $company->save($post);
            // 新增企业id
            $SystemAdmin->company_id = $company->id;
            $SystemAdmin->save();
            Db::commit();
            returnJson(GlobalFunction::buildResult(ResultCodeManager::SUCCESS, ResultCodeManager::$messages[ResultCodeManager::SUCCESS]));
        } catch (\Exception $e) {
            Db::rollback();
            returnJson(GlobalFunction::buildResult(ResultCodeManager::FAIL, ResultCodeManager::$messages[ResultCodeManager::FAIL]));
        }
    }

    // 编辑
    public function edit()
    {
        //所需参数数组
        $param = [
            'id',
            'company_name',
            'name',
            'phone',
            'retail_addr',
            'admin_id',
        ];
        //验证所需参数是否全部定义
        GlobalFunction::checkParams($param, 'post');
        $post = Request::post();
        // 事务处理
        Db::startTrans();
        try {
            $userData['username'] = $post['phone'];
            $userData['nickname'] = $post['name'];
            // 管理员
            $SystemAdmin = SystemAdmin::find($post['admin_id']);;
            if($SystemAdmin['username']!=$post['phone']){
            	$Admin = SystemAdmin::where(['username'=>$post['phone'],'power'=>3,'is_del'=>0])->find();
	            if($Admin){
	                returnJson(GlobalFunction::buildResult(ResultCodeManager::FAIL, '用户已存在'));
	            }
            }
            $SystemAdmin->save($userData);
            Company::update($post);
            Db::commit();
        	returnJson(GlobalFunction::buildResult(ResultCodeManager::SUCCESS, '更新成功'));
        } catch (\Exception $e) {
            Db::rollback();
            returnJson(GlobalFunction::buildResult(ResultCodeManager::FAIL, ResultCodeManager::$messages[ResultCodeManager::FAIL]));
        }
    }
    /**
     *  编辑查询
     */
    public function find()
    {
        //所需参数数组
        $param = [
            'id',
        ];
        //验证所需参数是否全部定义
        GlobalFunction::checkParams($param, 'post');
        $post = Request::post();
        $company = Company::find($post['id']);  
        returnJson(GlobalFunction::buildResult(ResultCodeManager::SUCCESS, '成功',$company));      
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
        // 事务处理
        Db::startTrans();
        try {
	        $company = Company::find($post['id']);
	        if($company){
	        	$company->is_del = 1;
	        	$company->save();
	        	SystemAdmin::update(['id'=>$company['admin_id'],'is_del'=>1]);
	        }
	        Db::commit();
	        returnJson(GlobalFunction::buildResult(ResultCodeManager::SUCCESS, '删除成功')); 
        } catch (\Exception $e) {
            Db::rollback();
            returnJson(GlobalFunction::buildResult(ResultCodeManager::FAIL, ResultCodeManager::$messages[ResultCodeManager::FAIL]));
        }       
    }
    /**
     *  坐席列表
     */
    public function sitTableList()
    {
        //所需参数数组
        $param = [
            'pagesize',
            'page',
            'company_id'//企业id
            // 'id' //主键搜索
        ];
        //验证所需参数是否全部定义
        GlobalFunction::checkParams($param, 'post');
        $post = Request::post();
        if(isset($post['id']) && !empty($post['id'])){
        	$res = SitTable::where('id',$post['id'])
                ->where('company_id',$post['company_id'])
        		->order('id desc')
        		->paginate(['list_rows'=>$post['pagesize'],'page'=>$post['page']])
                ->toArray();
        }else{
        	$res = SitTable::order('id desc')
                ->where('company_id',$post['company_id'])
        		->paginate(['list_rows'=>$post['pagesize'],'page'=>$post['page']])
                ->toArray();
        }
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
     *  添加坐席
     */
    public function sitTableAdd()
    {
        //所需参数数组
        $param = [
            'start_time',	//生效时间
            'expired_time', //失效时间
            'company_id',	//企业id
            // 'num'   //新增坐席数量
        ];
        //验证所需参数是否全部定义
        GlobalFunction::checkParams($param, 'post');
        $post = Request::post();
        $post['start_time'] = strtotime($post['start_time']);
        $post['expired_time'] = strtotime($post['expired_time'])+86399;
        // 事务处理
        Db::startTrans();
        try {
	        if(isset($post['num']) && !empty($post['num'])){
	        	$post['type'] = 1;
	        	$agent = Agent::where('admin_id',$this->getAdminId())->find();
	        	if(($agent['package_num']-$agent['package_use_num'])>$post['num']){
	        		for ($i=1; $i < $post['num']; $i++) { 
		        		$model = new SitTable();
		        		$model->save($post);
		        	}
		        	$agent->package_use_num = $agent['package_use_num']+$post['num'];
		        	$agent->save();
	        	}else{
	        		returnJson(GlobalFunction::buildResult(ResultCodeManager::FAIL, '剩余坐席数不足')); 
	        	}
	        }else{
	        	$model = new SitTable();
	        	$model->save($post);
	        }
	        Db::commit();
	        returnJson(GlobalFunction::buildResult(ResultCodeManager::SUCCESS, '添加成功')); 
        } catch (\Exception $e) {
            Db::rollback();
            returnJson(GlobalFunction::buildResult(ResultCodeManager::FAIL, ResultCodeManager::$messages[ResultCodeManager::FAIL]));
        }       
    }
    /**
     *  坐席续费
     */
    public function sitTableRenew()
    {
        //所需参数数组
        $param = [
            'id',
            // 'start_time',//过期的传入此字段 非过期的不传
            'expired_time'
        ];
        //验证所需参数是否全部定义
        GlobalFunction::checkParams($param, 'post');
        $post = Request::post();
        if(isset($post['start_time']) && !empty($post['start_time'])){
        	$post['start_time'] = strtotime($post['start_time']);
        }
        $post['expired_time'] = strtotime($post['expired_time'])+86399;
        $post['status'] = 1;
        SitTable::update($post);
        returnJson(GlobalFunction::buildResult(ResultCodeManager::SUCCESS, '续费成功')); 
        
    }
}
