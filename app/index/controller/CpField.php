<?php

namespace app\index\controller;


use app\BaseController;
use app\GlobalFunction;
use app\model\Field as FieldModel;
use app\model\FieldLabel;
use app\ResultCodeManager;
use think\Exception;
use think\facade\Db;
use think\facade\Request;
use think\facade\Env;

class CpField extends BaseController
{
   
   /**
     * 字段列表
     * @throws \think\db\exception\DbException
     */
    public function fieldList(){
        //所需参数数组
        $param = [
            'company_id',//企业id
        ];
        //验证所需参数是否全部定义
        GlobalFunction::checkParams($param, 'post');
        $post = Request::post();

        $res = Db::name('field')->where(array('company_id'=>$post['company_id']))
            ->order('ord','asc')->select();
        foreach($res as $k=>$v){

            $v['update_user_name']=$v['update_user_id'];
            $v['label_list']=FieldLabel::where('field_id',$v['id'])->order('ord','asc')->select();

            if($v['is_pre']!=0){
                $v['edit_status']=1;//可编辑
                $v['delete_status']=1;//可删除
            }else{

                $v['edit_status']=0;//不可编辑
                if($v['type']==4 || $v['type']==5){
                    $v['edit_status']=1;//可编辑
                }

                $v['delete_status']=0;//不可删除

            }

            $res[$k]=$v;

        }

        returnJson(GlobalFunction::buildResult(ResultCodeManager::SUCCESS, ResultCodeManager::$messages[ResultCodeManager::SUCCESS],$res));    
        

    }

    /**
     * 添加字段
     * @throws \think\db\exception\DbException
     */
    public function addField(){
        //所需参数数组
        $param = [
            'company_id',//企业id
            'title',//字段名称
            'type',//字段类型 0系统预设 1单行文本 2多行文本 3数字 4单选标签 5多选标签 6日期 7时间 8日期+时间 9附件
        ];
        //验证所需参数是否全部定义
        GlobalFunction::checkParams($param, 'post');
        $post = Request::post();

        
        if($post['type']==4 || $post['type']==5){
            //所需参数数组
            $param = [
                'label_list',//标签信息 当type字段类型为4/5 需要传 二维数组 按照排列顺序
                    /*
                    [
                        'id'=>0,//添加标签传0
                        'label_name',//标签名称
                    ]
                    */
            ];
            GlobalFunction::checkParams($param, 'post');
        }
        

        $res = Db::name('field')->where(array('company_id'=>$post['company_id'],'title'=>$post['title']))->find();
        if ($res){
            returnJson(GlobalFunction::buildResult(ResultCodeManager::FAIL, "字段名称不能重复"));
        }
        
        Db::startTrans();
        try {
            $ordMax=Db::name('field')->where(array('company_id'=>$post['company_id']))->max('ord');
            $fieldData = [
                'company_id' => $post['company_id'],
                'title' => $post['title'],
                'type' => $post['type'],
                'create_time' => time(),
                'ord'=>(int)$ordMax+1
            ];
            $FieldModel = new FieldModel();

            $fieldId = $FieldModel->insertGetId($fieldData);

            //标签
            if($post['type']==4 || $post['type']==5){
                $label_list=json_decode($post['label_list'],true);
                foreach($label_list as $k=>$v){
                    $labelData = [
                        'field_id' => $fieldId,
                        'type' => $post['type']==4?0:1,
                        'create_time' => time(),
                        'ord'=>$k+1,
                        'label_name'=>$v['label_name'],
                    ];
                    $FieldLabel = new FieldLabel();
                    $FieldLabel->insertGetId($labelData);
                }
            }

            Db::commit();
            returnJson(GlobalFunction::buildResult(ResultCodeManager::SUCCESS, "添加成功"));
        } catch (\Exception $e) {
            Db::rollback();
            returnJson(GlobalFunction::buildResult(ResultCodeManager::FAIL, ResultCodeManager::$messages[ResultCodeManager::FAIL]));
        }

    }

    /**
     * 编辑字段
     * @throws \think\db\exception\DbException
     */
    public function editField(){
        //所需参数数组
        $param = [
            'id',//字段id
            'company_id',//企业id
            // 'login_id',//当前企业登录用户id
            'title',//字段名称
            'type',//字段类型 0系统预设 1单行文本 2多行文本 3数字 4单选标签 5多选标签 6日期 7时间 8日期+时间 9附件
        ];
        //验证所需参数是否全部定义
        GlobalFunction::checkParams($param, 'post');
        $post = Request::post();

        
        if($post['type']==4 || $post['type']==5){
            //所需参数数组
            $param = [
                'label_list',//标签信息 当type字段类型为4/5 需要传 二维数组 按照排列顺序
                    /*
                    [
                        'id'=>0,//添加标签传0 修改标签传标签id
                        'label_name',//标签名称
                        'color_info',//标签背景颜色  无颜色传空 
                    ]
                    */
                'label_id',//删除的标签id字符串,组合    
            ];
            GlobalFunction::checkParams($param, 'post');
        }
        

        $res = Db::name('field')->where(array('company_id'=>$post['company_id'],'title'=>$post['title']))->where('id','<>',$post['id'])->find();
        if ($res){
            returnJson(GlobalFunction::buildResult(ResultCodeManager::FAIL, "字段名称不能重复"));
        }
        
        Db::startTrans();
        try {
            
            $fieldData = [
                'title' => $post['title'],
                'update_time' => time(),
                'update_user_id'=>5,
            ];
            $FieldModel = new FieldModel();

            $FieldModel->where('id',$post['id'])->update($fieldData);

            //标签
            if($post['type']==4 || $post['type']==5){
                $label_list=json_decode($post['label_list'],true);
                foreach($label_list as $k=>$v){
                    $labelData = [
                        'ord'=>$k+1,
                        'label_name'=>$v['label_name'],
                        'color_info'=>$v['color_info']
                    ];
                    $FieldLabel = new FieldLabel();
                    if($v['id']){//编辑
                        $FieldLabel->where('id',$v['id'])->update($labelData);
                    }else{//添加
                        $labelData['create_time']=time();
                        $labelData['field_id']=$post['id'];
                        $labelData['type']=$post['type']==4?0:1;
                        $FieldLabel->insertGetId($labelData);
                    }
                }
                //删除标签
                FieldLabel::whereIn('id',$post['label_id'])->delete();
            }

            Db::commit();
            returnJson(GlobalFunction::buildResult(ResultCodeManager::SUCCESS, "编辑成功"));
        } catch (\Exception $e) {
            Db::rollback();
            returnJson(GlobalFunction::buildResult(ResultCodeManager::FAIL, ResultCodeManager::$messages[ResultCodeManager::FAIL]));
        }

    }

    /**
     * 改变字段状态
     * @throws \think\db\exception\DbException
     */
    public function changeFieldStatus(){
        //所需参数数组
        $param = [
            'id',//字段id
            'status',//状态 1开启 2关闭
            // 'login_id',//当前登录用户id
        ];
        //验证所需参数是否全部定义
        GlobalFunction::checkParams($param, 'post');
        $post = Request::post();
        $save_data=['status'=>$post['status']];
        $fieldInfo=FieldModel::where('id',$post['id'])->find();
        if($fieldInfo['type']!=0){
            $save_data['update_time']=time();
            $save_data['update_user_id']=5;
        }
        FieldModel::where('id',$post['id'])->update($save_data);

        if($post['status']==1){
            $msg='启用成功';
        }else{
            $msg='停用成功';
        }
        returnJson(GlobalFunction::buildResult(ResultCodeManager::SUCCESS, $msg));
       

    }
    /**
     * 删除字段
     * @throws \think\db\exception\DbException
     */
    public function delField(){
        //所需参数数组
        $param = [
            'id',//字段id
        ];
        //验证所需参数是否全部定义
        GlobalFunction::checkParams($param, 'post');
        $post = Request::post();
       
        FieldModel::where('id',$post['id'])->delete();
        FieldLabel::where('field_id',$post['id'])->delete();
        
        returnJson(GlobalFunction::buildResult(ResultCodeManager::SUCCESS, '删除成功'));
       

    }





}