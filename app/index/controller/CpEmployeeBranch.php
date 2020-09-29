<?php
namespace app\index\controller;
use think\facade\Request;
use \think\facade\Db;
use app\BaseController;
use app\GlobalFunction;
use app\ResultCodeManager;
use app\model\SystemAdmin;
use app\model\Branch;
use app\model\PowerGroup;
// 员工部门管理
class CpEmployeeBranch extends ApiBase {    
    /**
     *  部门列表
     */
    public function branchList()
    {
        //所需参数数组
        $param = [
            'pagesize',
            'page',
            // 'name'		//搜索：部门名称
        ];
        //验证所需参数是否全部定义
        GlobalFunction::checkParams($param, 'post');
        $post = Request::post();
        // 搜索
        if(isset($post['name']) && !empty($post['name'])){
        	$map[] = ['name','like','%'.$post['name'].'%'];
        }
        $map[] = ['is_del','=',0];
    	$res = Branch::where($map)
    		->order('id desc')
    		->paginate(['list_rows'=>$post['pagesize'],'page'=>$post['page']]);       
        returnJson(GlobalFunction::buildResult(ResultCodeManager::SUCCESS, '查询成功',$res)); 
    }
    /**
     *  部门列表下拉框
     */
    public function selectList()
    {
        $map[] = ['is_del','=',0];
        $map[] = ['company_id','=',SystemAdmin::where('id',$this->getAdminId())->value('company_id')];
    	$res = Branch::where($map)->field('id,name')->where($map)->select();       
        returnJson(GlobalFunction::buildResult(ResultCodeManager::SUCCESS, '查询成功',$res)); 
    }
    /**
     *  部门添加
     */
    public function branchAdd()
    {
        //所需参数数组
        $param = [
            'name',	//部门名称
        ];
        //验证所需参数是否全部定义
        GlobalFunction::checkParams($param, 'post');
        $post = Request::post();
        $post['company_id'] = SystemAdmin::where('id',$this->getAdminId())->value('company_id');
        $branch = new Branch();
        $branch->save($post);
        returnJson(GlobalFunction::buildResult(ResultCodeManager::SUCCESS, '成功'));     
    }
    /**
     *  部门编辑
     */
    public function branchEdit()
    {
        //所需参数数组
        $param = [
        	'id',
            'name',	//部门名称
        ];
        //验证所需参数是否全部定义
        GlobalFunction::checkParams($param, 'post');
        $post = Request::post();
        Branch::update($post);
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
        Branch::update($post);
        returnJson(GlobalFunction::buildResult(ResultCodeManager::SUCCESS, '删除成功'));       
    }

    /**
    *  author change
    *  员工添加
    */ 
    public function addEmployee()
    {
        //所需参数数组
        $param = [
            'username',
            'password',
            'nickname',
            'powerId',
            'branchId'
        ];
        //验证所需参数是否全部定义
        GlobalFunction::checkParams($param, 'post');
        $post = Request::post();
        // 注册号码检测
        $Admin = SystemAdmin::where(['username'=>$post['username'],'power'=>3,'is_del'=>0])->find();
        if($Admin){
            returnJson(GlobalFunction::buildResult(ResultCodeManager::FAIL, '账号已存在'));
        }
        // 新增门店管理员
        $userData['username'] = $post['username'];
        $userData['password'] = md5($post['password']);
        $userData['nickname'] = $post['nickname'];
        $userData['power'] = 3;
        $userData['parent_id'] = $this->getAdminId();
        $userData['company_id'] = SystemAdmin::where('id',$this->getAdminId())->value('company_id');
        $userData['is_super'] = 0;
        $userData['power_id'] = $post['powerId'];
        $userData['branch_id'] = $post['branchId'];
        $SystemAdmin = new SystemAdmin();
        if($SystemAdmin->save($userData)){
            returnJson(GlobalFunction::buildResult(ResultCodeManager::SUCCESS, ResultCodeManager::$messages[ResultCodeManager::SUCCESS]));
        }else{
            returnJson(GlobalFunction::buildResult(ResultCodeManager::FAIL, ResultCodeManager::$messages[ResultCodeManager::FAIL]));
        }
    }

    /**
    *  author change
    *  编辑员工
    */ 
    public function editEmployee()
    {
        //所需参数数组
        $param = [
            'id',
            'username',
            'nickname',
            'powerId',
            // 'remark',
            'branchId'
        ];
        //验证所需参数是否全部定义
        GlobalFunction::checkParams($param, 'post');
        $post = Request::post();
        // 注册号码检测
        $count = SystemAdmin::where(['username'=>$post['username'],'power'=>3,'is_super'=>0,'is_del'=>0])
            ->where('id','<>',$post['id'])
            ->count();
        if($count>0){
            returnJson(GlobalFunction::buildResult(ResultCodeManager::FAIL, '账号已存在'));
        }
        // 新增门店管理员
        $userData['username'] = $post['username'];
        $userData['id'] = $post['id'];
        $userData['nickname'] = $post['nickname'];
        $userData['power_id'] = $post['powerId'];
        // $userData['remark'] = $post['remark'];
        $userData['branch_id'] = $post['branchId'];
        SystemAdmin::update($userData);
        returnJson(GlobalFunction::buildResult(ResultCodeManager::SUCCESS, ResultCodeManager::$messages[ResultCodeManager::SUCCESS]));
    }
    /**
    *  author change
    *  编辑查询
    */ 
    public function findEmployee()
    {
        //所需参数数组
        $param = [
            'id'
        ];
        //验证所需参数是否全部定义
        GlobalFunction::checkParams($param, 'post');
        $post = Request::post();
        // 注册号码检测
        $SystemAdmin = SystemAdmin::field('id,username,nickname,power_id,branch_id')->find($post['id']);
        returnJson(GlobalFunction::buildResult(ResultCodeManager::SUCCESS, ResultCodeManager::$messages[ResultCodeManager::SUCCESS],$SystemAdmin));
    }

    /**
    *  author change
    *  删除
    */ 
    public function delEmployee()
    {
        //所需参数数组
        $param = [
            'id',
        ];
        //验证所需参数是否全部定义
        GlobalFunction::checkParams($param, 'post');
        $post = Request::post();
        // 注册号码检测
        $post['is_del'] = 1 ;
        SystemAdmin::update($post);
        returnJson(GlobalFunction::buildResult(ResultCodeManager::SUCCESS, ResultCodeManager::$messages[ResultCodeManager::SUCCESS]));
    }
    /**
     * 员工列表
     */
    public function list() {

        //所需参数数组
        $param = [
            'pagesize',
            'page',
            // 'power_id', //权限id   0 或空位全部
            // 'branch_id',//部门id   0 或空位全部
            // 'name'		//搜索 手机号 姓名
        ];
        //验证所需参数是否全部定义
        GlobalFunction::checkParams($param, 'post');
        $post = Request::post();
        try{
        	if(isset($post['name']) && !empty($post['name'])){
	        	$map[] = ['username|nickname','like','%'.$post['name'].'%'];
	        }
	        if(isset($post['power_id']) && !empty($post['power_id'])){
	        	$map[] = ['power_id','=',$post['power_id']];
	        }
	        if(isset($post['branch_id']) && !empty($post['branch_id'])){
	        	$map[] = ['branch_id','=',$post['branch_id']];
	        }
	        $map[] = ['is_del','=',0];
        	$data = SystemAdmin::where('parent_id',$this->getAdminId())
        		->field('id,nickname,username,create_time,branch_id,power_id,is_del') 
                ->where($map)
                ->paginate(['list_rows'=>$post['pagesize'],'page'=>$post['page']])
                ->toArray();
            if(isset($data['data']) && !empty($data['data'])){
            	foreach ($data['data'] as &$value) {
            		$value['power_name'] = PowerGroup::where('id',$value['power_id'])->value('name');
            		$value['branch_name'] = Branch::where('id',$value['branch_id'])->value('name');
            	}
            }
            
            returnJson(GlobalFunction::buildResult(ResultCodeManager::SUCCESS,'查询成功',$data));
        } catch (\Exception $e) {
            returnJson(GlobalFunction::buildResult(ResultCodeManager::FAIL, '系统异常'));
        }
    }
}
