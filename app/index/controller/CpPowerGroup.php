<?php
namespace app\index\controller;
use think\facade\Request;
use app\BaseController;
use app\GlobalFunction;
use app\ResultCodeManager;
use app\model\PowerGroup;
use app\model\SystemAdmin;
// 权限组
class CpPowerGroup extends ApiBase {
    
    /**
     * 权限组添加
     */
    public function add() {

        //所需参数数组
        $param = [
            'name',//权限组名称
            'relevanceId',//关联id
            'data',//权限详细
            'remark'//描述
        ];
        //验证所需参数是否全部定义
        GlobalFunction::checkParams($param, 'post');
        $post = Request::post();
        $data = [
            'name'=> $post['name'],
            'relevance_id'=> $post['relevanceId'],
            'data'=> $post['data']
        ];
        if(isset($post['remark']) && !empty($post['remark'])){
            $data['remark'] = $post['remark'];
        }
        try{
            $model = new PowerGroup();
            $model->save($data);
            returnJson(GlobalFunction::buildResult(ResultCodeManager::SUCCESS,'添加成功'));
        } catch (\Exception $e) {
            returnJson(GlobalFunction::buildResult(ResultCodeManager::FAIL, '系统异常'));
        }
    }
    /**
     * 权限组编辑
     */
    public function edit() {

        //所需参数数组
        $param = [
            'id',
            'name',//权限组名称
            'relevanceId',//关联id
            'data',//权限详细
            'remark'//描述
        ];
        //验证所需参数是否全部定义
        GlobalFunction::checkParams($param, 'post');
        $post = Request::post();
        $data = [
            'id'=>$post['id'],
            'name'=> $post['name'],
            'relevance_id'=> $post['relevanceId'],
            'data'=> $post['data'],
            'remark'=> $post['remark']
        ];
        try{
            PowerGroup::update($data);
            returnJson(GlobalFunction::buildResult(ResultCodeManager::SUCCESS,'更新成功'));
        } catch (\Exception $e) {
            returnJson(GlobalFunction::buildResult(ResultCodeManager::FAIL, '系统异常'));
        }
    }
    /**
     * 编辑查询
     */
    public function find() {

        //所需参数数组
        $param = [
            'id',
        ];
        //验证所需参数是否全部定义
        GlobalFunction::checkParams($param, 'post');
        $post = Request::post();
        try{
            $model = PowerGroup::field('name,relevance_id,data,remark')->find($post);
            $model['data'] = json_decode($model['data']);
            returnJson(GlobalFunction::buildResult(ResultCodeManager::SUCCESS,'查询成功',$model));
        } catch (\Exception $e) {
            returnJson(GlobalFunction::buildResult(ResultCodeManager::FAIL, '系统异常'));
        }
    }
    /**
     * 权限组软删除
     */
    public function del() {

        //所需参数数组
        $param = [
            'id'
        ];
        //验证所需参数是否全部定义
        GlobalFunction::checkParams($param, 'post');
        $post = Request::post();
        $post['is_del'] = 1;
        try{
            PowerGroup::update($post);
            returnJson(GlobalFunction::buildResult(ResultCodeManager::SUCCESS,'删除成功'));
        } catch (\Exception $e) {
            returnJson(GlobalFunction::buildResult(ResultCodeManager::FAIL, '系统异常'));
        }
    }
    /**
     * 权限组列表
     */
    public function list() {

        //所需参数数组
        $param = [
            'relevanceId',//关联id
            'pagesize',
            'page'
        ];
        //验证所需参数是否全部定义
        GlobalFunction::checkParams($param, 'post');
        $post = Request::post();
        $map = [
            'relevance_id'=> $post['relevanceId'],
            'is_del'=>0
        ];
        try{
            $model = PowerGroup::field('id,name,relevance_id,data,remark')->where($map)->paginate(['list_rows'=>$post['pagesize'],'page'=>$post['page']]);
            foreach ($model as $key => &$value) {
                $value['data'] = json_decode($value['data']);
            }
            returnJson(GlobalFunction::buildResult(ResultCodeManager::SUCCESS,'查询成功',$model));
        } catch (\Exception $e) {
            returnJson(GlobalFunction::buildResult(ResultCodeManager::FAIL, '系统异常'));
        }
    }

    /**
     * 权限组下拉列表
     */
    public function selectList() {
        $map = [
            'relevance_id'=> SystemAdmin::where('id',$this->getAdminId())->value('company_id'),
            'is_del'=>0
        ];
        try{
            $model = PowerGroup::field('id,name')->where($map)->select();
            returnJson(GlobalFunction::buildResult(ResultCodeManager::SUCCESS,'查询成功',$model));
        } catch (\Exception $e) {
            returnJson(GlobalFunction::buildResult(ResultCodeManager::FAIL, '系统异常'));
        }
    }
}
