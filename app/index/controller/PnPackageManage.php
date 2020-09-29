<?php
namespace app\index\controller;
use think\facade\Request;
use app\BaseController;
use app\GlobalFunction;
use app\ResultCodeManager;
use app\model\Package;
// 代理商等级折扣
class PnPackageManage extends ApiBase
{
    // 列表页
    public function index()
    {
        //所需参数数组
        $param = [
            'pagesize',
            'page',
            // 'name'  //套餐名
        ];
        //验证所需参数是否全部定义
        GlobalFunction::checkParams($param, 'post');
        $post = Request::post();
        // 搜索
        if(isset($post['name']) && !empty($post['name'])){
            $map[] = ['name','like','%'.$post['name'].'%'];
        }
        $map[] = ['is_delete','=',0];
        $res = Package::field('id,name,price,num')->where($map)->paginate(['list_rows'=>$post['pagesize'],'page'=>$post['page']]);
        returnJson(GlobalFunction::buildResult(ResultCodeManager::SUCCESS, ResultCodeManager::$messages[ResultCodeManager::SUCCESS],$res));
    }

    // 套餐选择
    public function class()
    {

        $res = Package::field('id,name,price,num')->where('is_delete',0)->select();
        returnJson(GlobalFunction::buildResult(ResultCodeManager::SUCCESS, ResultCodeManager::$messages[ResultCodeManager::SUCCESS],$res));
    }

    // 添加
    public function add()
    {
        //所需参数数组
        $param = [
            'name',
            'price',
            'num',
        ];
        //验证所需参数是否全部定义
        GlobalFunction::checkParams($param, 'post');
        $post = Request::post();
        $post['is_delete'] = 0;
        $model = new Package();
        $res = $model->save($post);
        if($res){
        	returnJson(GlobalFunction::buildResult(ResultCodeManager::SUCCESS, ResultCodeManager::$messages[ResultCodeManager::SUCCESS]));
        }else{
        	returnJson(GlobalFunction::buildResult(ResultCodeManager::FAIL, ResultCodeManager::$messages[ResultCodeManager::FAIL]));
        }
    }

    // 编辑
    public function edit()
    {
        //所需参数数组
        $param = [
        	'id',
            // 'name',
            // 'price',
            // 'num',
        ];
        //验证所需参数是否全部定义
        GlobalFunction::checkParams($param, 'post');
        $post = Request::post();
    	Package::update($post);
        returnJson(GlobalFunction::buildResult(ResultCodeManager::SUCCESS, '更新成功'));   
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
        $Package = Package::find($post['id']);  
        returnJson(GlobalFunction::buildResult(ResultCodeManager::SUCCESS, '成功',$Package));      
    }
    
    // 软删除
    public function delete()
    {
        //所需参数数组
        $param = [
        	'id',
        ];
        //验证所需参数是否全部定义
        GlobalFunction::checkParams($param, 'post');
        $post = Request::post();
    	$res = Package::update(['is_delete'=>1],['id'=>$post['id']]);
        returnJson(GlobalFunction::buildResult(ResultCodeManager::SUCCESS, '删除成功'));
    }
}
