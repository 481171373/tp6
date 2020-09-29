<?php

namespace app\index\controller;


use app\BaseController;
use app\GlobalFunction;
use app\model\FollowStage;
use app\ResultCodeManager;
use think\Exception;
use think\facade\Db;
use think\facade\Request;
use think\facade\Env;

class CpFollowStage extends BaseController
{
   
   /**
     * 阶段信息
     * @throws \think\db\exception\DbException
     */
    public function stageInfo(){
        //所需参数数组
        $param = [
            'company_id',//企业id
        ];
        //验证所需参数是否全部定义
        GlobalFunction::checkParams($param, 'post');
        $post = Request::post();

        //跟进阶段
        $res['follow_stage'] = Db::name('follow_stage')->where(array('company_id'=>$post['company_id'],'type'=>0))->order('ord','asc')->select();
        //输单阶段
        $out = Db::name('follow_stage')->where(array('company_id'=>$post['company_id'],'type'=>1))->find();
        $out['out_reason']=FollowStage::where(array('pid'=>$out['id']))->order('ord','asc')->select();
        $res['out_stage']=$out;
        //无效阶段
        $fail = Db::name('follow_stage')->where(array('company_id'=>$post['company_id'],'type'=>2))->find();
        $fail['fail_reason']=FollowStage::where(array('pid'=>$fail['id']))->order('ord','asc')->select();
        $res['fail_stage']=$fail;

        returnJson(GlobalFunction::buildResult(ResultCodeManager::SUCCESS, ResultCodeManager::$messages[ResultCodeManager::SUCCESS],$res));    
        

    }

    /**
     * 保存阶段设置
     * @throws \think\db\exception\DbException
     */
    public function saveStage(){
        //所需参数数组
        $param = [
            'company_id',//企业id
            'follow_stage',//跟进中  二维数组 按照排列顺序
                    /*
                    [
                        'id'=>0,//添加阶段传0 编辑传阶段id
                        'title',//阶段名称
                    ]
                    */
            'out_title',//输单名称
            'out_reason',//输单理由  二维数组 按照排列顺序
                    /*
                    [
                        'id'=>0,//添加理由传0 编辑传理由id
                        'title',//理由名称
                    ]
                    */
            'fail_title',//无效名称
            'fail_reason',//无效理由  二维数组 按照排列顺序
                    /*
                    [
                        'id'=>0,//添加理由传0 编辑传理由id
                        'title',//理由名称
                    ]
                    */
            'del_stage_id',//删除跟进阶段id字符串,组合
            'del_out_reason_id',//删除输单理由id字符串,组合
            'del_fail_reason_id',//删除无效理由id字符串,组合        
        ];
        //验证所需参数是否全部定义
        GlobalFunction::checkParams($param, 'post');
        $post = Request::post();

        
        Db::startTrans();
        try {

            //跟进阶段
            $follow_stage=json_decode($post['follow_stage'],true);
            foreach($follow_stage as $k=>$v){
                $stageData = [
                    'ord'=>$k+1,
                    'title'=>$v['title'],
                    'pid'=>0,
                    'type'=>0,
                    'company_id'=>$post['company_id'],
                ];
                $FollowStage = new FollowStage();
                if($v['id']){//编辑

                    $res = Db::name('follow_stage')->where(array('company_id'=>$post['company_id'],'title'=>$v['title'],'type'=>0))->where('id','<>',$v['id'])->find();
                    if ($res){
                        returnJson(GlobalFunction::buildResult(ResultCodeManager::FAIL, "跟进阶段重复"));
                    }

                    $FollowStage->where('id',$v['id'])->update($stageData);
                }else{//添加

                    $res = Db::name('follow_stage')->where(array('company_id'=>$post['company_id'],'title'=>$v['title'],'type'=>0))->find();
                    if ($res){
                        returnJson(GlobalFunction::buildResult(ResultCodeManager::FAIL, "跟进阶段重复"));
                    }

                    $FollowStage->insertGetId($stageData);
                }
            }


            //输单阶段
            $out = Db::name('follow_stage')->where(array('company_id'=>$post['company_id'],'type'=>1))->find();
            FollowStage::where('id',$out['id'])->update(['title'=>$post['out_title']]);
            //输单理由
            $out_reason=json_decode($post['out_reason'],true);
            foreach($out_reason as $k=>$v){
                $outData = [
                    'ord'=>$k+1,
                    'title'=>$v['title'],
                    'pid'=>$out['id'],
                    'type'=>3,
                    'company_id'=>$post['company_id'],
                ];
                $FollowStage = new FollowStage();
                if($v['id']){//编辑

                    $res = Db::name('follow_stage')->where(array('company_id'=>$post['company_id'],'title'=>$v['title'],'pid'=>$out['id'],'type'=>3))->where('id','<>',$v['id'])->find();
                    if ($res){
                        returnJson(GlobalFunction::buildResult(ResultCodeManager::FAIL, "输单理由重复"));
                    }

                    $FollowStage->where('id',$v['id'])->update($outData);
                }else{//添加

                    $res = Db::name('follow_stage')->where(array('company_id'=>$post['company_id'],'title'=>$v['title'],'pid'=>$out['id'],'type'=>3))->find();
                    if ($res){
                        returnJson(GlobalFunction::buildResult(ResultCodeManager::FAIL, "输单理由重复"));
                    }

                    $FollowStage->insertGetId($outData);
                }
            }

            //无效阶段
            $fail = Db::name('follow_stage')->where(array('company_id'=>$post['company_id'],'type'=>2))->find();
            FollowStage::where('id',$fail['id'])->update(['title'=>$post['fail_title']]);
            //无效理由
            $fail_reason=json_decode($post['fail_reason'],true);
            foreach($fail_reason as $k=>$v){
                $failData = [
                    'ord'=>$k+1,
                    'title'=>$v['title'],
                    'pid'=>$fail['id'],
                    'type'=>3,
                    'company_id'=>$post['company_id'],
                ];
                $FollowStage = new FollowStage();
                if($v['id']){//编辑

                    $res = Db::name('follow_stage')->where(array('company_id'=>$post['company_id'],'title'=>$v['title'],'pid'=>$fail['id'],'type'=>3))->where('id','<>',$v['id'])->find();
                    if ($res){
                        returnJson(GlobalFunction::buildResult(ResultCodeManager::FAIL, "无效理由重复"));
                    }

                    $FollowStage->where('id',$v['id'])->update($failData);
                }else{//添加

                    $res = Db::name('follow_stage')->where(array('company_id'=>$post['company_id'],'title'=>$v['title'],'pid'=>$fail['id'],'type'=>3))->find();
                    if ($res){
                        returnJson(GlobalFunction::buildResult(ResultCodeManager::FAIL, "无效理由重复"));
                    }

                    $FollowStage->insertGetId($failData);
                }
            }

            //删除跟进阶段
            FollowStage::whereIn('id',$post['del_stage_id'])->delete();
            //删除输单理由
            FollowStage::whereIn('id',$post['del_out_reason_id'])->delete();
            //删除无效理由
            FollowStage::whereIn('id',$post['del_fail_reason_id'])->delete();
            

            Db::commit();
            returnJson(GlobalFunction::buildResult(ResultCodeManager::SUCCESS, "保存成功"));
        } catch (\Exception $e) {
             Db::rollback();
            returnJson(GlobalFunction::buildResult(ResultCodeManager::FAIL, ResultCodeManager::$messages[ResultCodeManager::FAIL]));
        }

    }










}