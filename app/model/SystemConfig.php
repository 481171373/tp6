<?php
namespace app\model;

use think\Model;

class SystemConfig extends Model
{
    /**
     * 获取配置系统配置
     * @param string $key   键
     * @param bool $obj     是否返回对象，默认false
     * @return array|mixed|Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function getConfigValue(string $key,bool $obj = false){
        $sc=self::where('key_value','=',$key)->find();
        if ($sc){
            return $obj?$sc:$sc->value;
        }else{
            return null;
        }
    }

    /**
     * 获取所有配置
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function getConfigAll(){
        return self::field(['key_value','value'])->where('id','>',0)->select()->toArray();
    }

    /**
     * 更新配置，如果存在，就更新，如果没有就添加
     * @param string $key   键
     * @param string $value 值
     * @return bool 更新结果
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function updateConfigValue(string $key,string $value){
        $sc=self::getConfigValue($key,true);
        if ($sc){
            $sc->value=$value;
            return $sc->save();
        }else{
            $data = ['key_value'=>$key,'value'=>$value];
            $sc=new SystemConfig();
            return $sc->insert($data);
        }
        
    }
}