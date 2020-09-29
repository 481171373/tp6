<?php

namespace app\index\controller;


use app\BaseController;
use app\GlobalFunction;
use app\model\CutomerTravel;
use app\model\CustomerInfo;
use app\model\FieldLabel;
use app\model\SystemAdmin;
use app\model\Field;
use app\ResultCodeManager;
use think\Exception;
use think\facade\Db;
use think\facade\Request;
use think\facade\Env;

class CpCustomer extends BaseController
{
   
    /**
     * 轨迹列表信息
     * @throws \think\db\exception\DbException
     */
    public function travelInfo(){
        //所需参数数组
        $param = [
            'customer_id',//客户id
        ];
        //验证所需参数是否全部定义
        GlobalFunction::checkParams($param, 'post');
        $post = Request::post();

        $res = Db::name('cutomer_travel')->alias('c')
            ->field("c.*,s.nickname as operation_name")
            ->leftJoin('system_admin s','c.operation_id = s.id')
            ->where(array('c.customer_id'=>$post['customer_id']))->order('c.id','desc')
            ->select();
        returnJson(GlobalFunction::buildResult(ResultCodeManager::SUCCESS, ResultCodeManager::$messages[ResultCodeManager::SUCCESS],$res));    
    }
    /**
     * 编辑轨迹内容
     * @throws \think\db\exception\DbException
     */
    public function editTravel(){
        //所需参数数组
        $param = [
            'travel_id',//轨迹id
            'desc',//内容
        ];
        //验证所需参数是否全部定义
        GlobalFunction::checkParams($param, 'post');
        $post = Request::post();
        CutomerTravel::where('id',$post['travel_id'])->update(['desc'=>$post['desc']]);
        
        returnJson(GlobalFunction::buildResult(ResultCodeManager::SUCCESS, ResultCodeManager::$messages[ResultCodeManager::SUCCESS]));    
    }




















    /**
     * 客户列表信息
     * @throws \think\db\exception\DbException
     */
    public function customerList(){
        //所需参数数组
        $param = [
            'company_id',//企业id
        ];
        //验证所需参数是否全部定义
        GlobalFunction::checkParams($param, 'post');
        $post = Request::post();
        $data=CustomerInfo::where('company_id',$post['company_id'])->select();

        
        foreach($data as $k=>$v){
            
            //标签信息
            $v['label_list']=[];
            if($v['label']){
                $v['label_list']=FieldLabel::whereIn('id',$v['label'])->select();
            }

            //来源信息
            $v['source_list']=[];
            if($v['source']){
                $v['source_list']=FieldLabel::whereIn('id',$v['source'])->select();
            }



            $res=json_decode($v['other_info'],true);
            foreach ($res as $key => $value) {
                $v[$key]=$value;
            }
            unset($v['other_info']);            
            $data[$k]=$v;
        }
        
        returnJson(GlobalFunction::buildResult(ResultCodeManager::SUCCESS, ResultCodeManager::$messages[ResultCodeManager::SUCCESS],$data));    
    }
    //添加、编辑客户资料页面所需字段
    public function addCustomerPage(){
        //所需参数数组
        $param = [
            'company_id',//企业id
        ];
        //验证所需参数是否全部定义
        GlobalFunction::checkParams($param, 'post');
        $post = Request::post();

        $res=Field::where(array('company_id'=>$post['company_id'],'status'=>1))->order('ord','asc')->select();
        foreach($res as $k=>$v){
            if($v['is_pre']){//后台添加的字段
                $v['field_name']='c_'.$v['id'];
            }
            $res[$k]=$v;
        }
        returnJson(GlobalFunction::buildResult(ResultCodeManager::SUCCESS, ResultCodeManager::$messages[ResultCodeManager::SUCCESS],$res));
    }
    //添加、编辑客户
    public function addCustomer(){
        //所需参数数组
        $param = [
            'company_id',//企业id
            'customer_id',//客户id 添加为0 编辑为客户id
            'follow_uid',//跟进人id 无跟进人传0
            'pre_field',//系统预设字段信息 一维数组
                /*
                    name=>
                    sex=>
                */
            'new_field',//后台添加字段信息 一维数组
                /*
                    c_id/c_1=>
                */        
        ];
        //验证所需参数是否全部定义
        GlobalFunction::checkParams($param, 'post');
        $post = Request::post();

        $data=json_decode($post['pre_field'],true);
        $data['company_id']=$post['company_id'];
        $data['other_info']=$post['new_field'];
        $data['follow_uid']=$post['follow_uid'];

        
        if($post['customer_id']){
            $data['update_time']=time();
            CustomerInfo::where('id',$post['customer_id'])->update($data);

        }else{
            $data['create_time']=time();
            $CutomerInfo = new CustomerInfo();
            $CutomerInfo = $CutomerInfo->insertGetId($data);

        }

        returnJson(GlobalFunction::buildResult(ResultCodeManager::SUCCESS, ResultCodeManager::$messages[ResultCodeManager::SUCCESS]));
    }





























    //改跟进人
    public function changeFollowPerson(){
        //self::travelLog();
    }
    //改跟进阶段
    public function changeFollowStage(){
        //self::travelLog();
    }
    //添加跟进记录
    public function changeFollowRecord(){
        //self::travelLog();
    }
    //轨迹记录公共方法
    private function travelLog($data){
        $CutomerTravel=new CutomerTravel();
        $CutomerTravel->title=$data['title'];
        $CutomerTravel->desc=$data['desc'];
        $CutomerTravel->title=time();
        $CutomerTravel->operation_id=$data['operation_id'];
        $CutomerTravel->way=$data['way'];
        $CutomerTravel->customer_id=$data['customer_id'];
        $CutomerTravel->save();
    } 
    



    





}