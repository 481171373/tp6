<?php
namespace app\index\controller;
use think\facade\Request;
use \think\facade\Db;
use app\BaseController;
use app\GlobalFunction;
use app\ResultCodeManager;
use app\model\SystemAdmin;
use app\model\Agent;
use app\model\Package;
// 代理商相关信息
class PnAgentManage extends ApiBase {
    
    //代理商列表
    public function index() {

        //所需参数数组
        $param = [
            'pagesize',
            'page',
            // 'package_id',//套餐id
            // 'name'//代理商姓名
        ];
        //验证所需参数是否全部定义
        GlobalFunction::checkParams($param, 'post');
        $post = Request::post();
        if(isset($post['name']) && !empty($post['name'])){
            $name = $post['name'];
            $map[] = ['name','like',"%$name%"];
        }
        if(isset($post['package_id']) && !empty($post['package_id'])){
            $map[] = ['package_id','=',$post['package_id']];
        }
        $map[] = ['is_del','=',0];
        $res = Agent::where($map)
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
            'start_time',
            'expired_time',
            'package_id',
            'retail_addr',
            'password'
        ];
        //验证所需参数是否全部定义
        GlobalFunction::checkParams($param, 'post');
        $post = Request::post();
        // 事务
        Db::startTrans();
        try {
        	$package = Package::find($post['package_id']);
        	if($package){
        		$post['package_name'] = $package['name'];
        		$post['package_num'] = $package['num'];
        		$post['package_use_num'] = 0;
        	}
            $post['start_time'] = strtotime($post['start_time']);
            $post['expired_time'] = strtotime($post['expired_time']);
            // 创建登录数据
            $userData['username'] = $post['phone'];
            $userData['nickname'] = $post['name'];
            $userData['power'] = 2;
            $userData['parent_id'] = $this->getAdminId();
            $userData['password'] = md5($post['password']);
            // 注册号码检测
            $Admin = SystemAdmin::where(['username'=>$post['phone'],'power'=>2,'is_del'=>0])->find();
            if($Admin){
                returnJson(GlobalFunction::buildResult(ResultCodeManager::FAIL, '用户已存在'));
            }
            // 新增代理商
            $SystemAdmin = new SystemAdmin();
            $SystemAdmin->save($userData);
            // 代理商关联信息
            $post['admin_id'] = $SystemAdmin->id;
            $Agent = new Agent();
            $Agent->save($post);
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
            'start_time',
            'expired_time',
            'package_id',
            'retail_addr',
            'admin_id',
        ];
        //验证所需参数是否全部定义
        GlobalFunction::checkParams($param, 'post');
        $post = Request::post();
        // 事务处理
        Db::startTrans();
        try {
            $post['start_time'] = strtotime($post['start_time']);
            $post['expired_time'] = strtotime($post['expired_time']);
            $userData['username'] = $post['phone'];
            $userData['nickname'] = $post['name'];
            // 管理员
            $SystemAdmin = SystemAdmin::find($post['admin_id']);
            if($SystemAdmin['username']!=$post['phone']){
            	$Admin = SystemAdmin::where(['username'=>$post['phone'],'power'=>2,'is_del'=>0])->find();
	            if($Admin){
	                returnJson(GlobalFunction::buildResult(ResultCodeManager::FAIL, '用户已存在'));
	            }
            }
            $SystemAdmin->save($userData);
            // 代理商信息更新
            $agent = Agent::find($post['id']);
            if($agent['package_id']!=$post['package_id']){
            	$package = Package::find($post['package_id']);
            	if($package['num']>=$agent['package_use_num']){
            		$post['package_num'] = $package['num'];
            	}else{
            		returnJson(GlobalFunction::buildResult(ResultCodeManager::FAIL, '使用超限不能降低到当前等级'));
            	}
            }
            Agent::update($post);
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
        $Agent = Agent::find($post['id']);  
        returnJson(GlobalFunction::buildResult(ResultCodeManager::SUCCESS, '成功',$Agent));      
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
        $agent = Agent::find($post['id']);
        if($agent){
        	$agent->is_del = 1;
        	$agent->save();
        	SystemAdmin::update(['id'=>$agent['admin_id'],'is_del'=>1]);
        }
        returnJson(GlobalFunction::buildResult(ResultCodeManager::SUCCESS, '删除成功'));        
    }
}
