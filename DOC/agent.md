# 企业
## 企业列表
### 接口信息
描述 | 协议 | 请求方式 | 请求地址 | 数据返回格式
---|---|---|---|---|
企业列表 | http  | post | /index/AnCompanyManage/index | json
### 请求参数
参数 | 是否必传 | 可否为空 | 说明   | 示例
---|---|---|---|---|
pagesize | 是 | 否 | 页面大小 | 10
page | 是 | 否 | 当前页 | 1
name | 否 | 否 | 姓名、手机号、企业名称 | 1
### 数据返回
```
{
    "code": 1,
    "msg": "成功",
    "data": {
        "total": 1,
        "per_page": 10,
        "current_page": 1,
        "last_page": 1,
        "data": [
            {
                "id": 7,
                "admin_id": 11,
                "company_name": "融泽网络科技",   公司名称
                "name": "王总",                  联系人
                "phone": "18955333320",         电话
                "retail_addr": "浙江省杭州市余杭区崇杭街承安源翠府5-2-202", 地址
                "is_del": 0,
                "agent_id": 1,
                "create_time": "2020-09-08 16:51:24",
                "update_time": "2020-09-08 17:16:36"
            }
        ]
    }
}
```

---
## 企业添加
### 接口信息
描述 | 协议 | 请求方式 | 请求地址 | 数据返回格式
---|---|---|---|---|
企业添加 | http  | post | /index/AnCompanyManage/add | json
### 请求参数
参数 | 是否必传 | 可否为空 | 说明   | 示例
---|---|---|---|---|
company_name | 是 | 否 | 企业名称 | 杭州融泽网络科技
name | 是 | 否 | 企业联系人 | 陈总
phone | 是 | 否 | 企业电话 | 18955333313
retail_addr | 是 | 否 | 地址 | 绿地中央广场
password | 是 | 否 | 密码 | 123456
### 数据返回
```
{
    "code": 1,
    "msg": "成功",
    "data":
}
```

---
## 企业编辑
### 接口信息
描述 | 协议 | 请求方式 | 请求地址 | 数据返回格式
---|---|---|---|---|
企业编辑 | http  | post | /index/AnCompanyManage/edit | json
### 请求参数
参数 | 是否必传 | 可否为空 | 说明   | 示例
---|---|---|---|---|
company_name | 是 | 否 | 企业名称 | 杭州融泽网络科技
name | 是 | 否 | 企业联系人 | 陈总
phone | 是 | 否 | 企业电话 | 18955333313
retail_addr | 是 | 否 | 地址 | 绿地中央广场
id | 是 | 否 | 主键 | 1
admin_id | 是 | 否 | 关联登录id | 1
### 数据返回
```
{
    "code": 1,
    "msg": "成功",
    "data":
}
```

## 编辑查询
### 接口信息
描述 | 协议 | 请求方式 | 请求地址 | 数据返回格式
---|---|---|---|---|
编辑查询 | http  | post | /index/AnCompanyManage/find | json
### 请求参数
参数 | 是否必传 | 可否为空 | 说明   | 示例
---|---|---|---|---|
id | 是 | 否 | 主键id | 1
### 数据返回
```
{
    "code": 1,
    "msg": "成功",
    "data": {
        "id": 1,
        "admin_id": 11,
        "company_name": "融泽网络科技",   公司名称
        "name": "王总",                  联系人
        "phone": "18955333320",         电话
        "retail_addr": "浙江省杭州市余杭区崇杭街承安源翠府5-2-202", 地址
        "is_del": 0,
        "agent_id": 1,
        "create_time": "2020-09-08 16:51:24",
        "update_time": "2020-09-08 17:16:36"
    }
}
```

---
## 企业删除
### 接口信息
描述 | 协议 | 请求方式 | 请求地址 | 数据返回格式
---|---|---|---|---|
企业删除 | http  | post | /index/AnCompanyManage/delete | json
### 请求参数
参数 | 是否必传 | 可否为空 | 说明   | 示例
---|---|---|---|---|
id | 是 | 否 | 主键 | 1
### 数据返回
```
{
    "code": 1,
    "msg": "删除成功",
    "data":
}
```

---
## 坐席列表
### 接口信息
描述 | 协议 | 请求方式 | 请求地址 | 数据返回格式
---|---|---|---|---|
坐席列表 | http  | post | /index/AnCompanyManage/sitTableList | json
### 请求参数
参数 | 是否必传 | 可否为空 | 说明   | 示例
---|---|---|---|---|
pagesize | 是 | 否 | 页面大小 | 10
page | 是 | 否 | 当前页 | 1
company_id | 是 | 否 | 企业id | 1
id | 否 | 是 | 搜索 | 1
### 数据返回
```
{
    "code": 1,
    "msg": "查询成功",
    "data": {
        "total": 5,
        "per_page": 10,
        "current_page": 1,
        "last_page": 1,
        "data": [
            {
                "id": 5,
                "start_time": 1599580800,   生效时间
                "expired_time": 1612886399, 有效时间
                "type": 1,                  坐席类型：0-体验 1-正式
                "status": 1,                状态: 0-过期 1-正常
                "company_id": 7,
                "create_time": "2020-09-09 11:58:52"
            }
        ]
    }
}
```

---
## 坐席添加
### 接口信息
描述 | 协议 | 请求方式 | 请求地址 | 数据返回格式
---|---|---|---|---|
坐席添加 | http  | post | /index/AnCompanyManage/sitTableAdd | json
### 请求参数
参数 | 是否必传 | 可否为空 | 说明   | 示例
---|---|---|---|---|
start_time | 是 | 否 | 生效时间 | 2020-09-09
expired_time | 是 | 否 | 失效时间 | 2020-09-09
company_id | 是 | 否 | 企业id | 1
num | 否 | 否 | 新增坐席数量（非体验） | 1
### 数据返回
```
{
    "code": 1,
    "msg": "成功",
    "data":
}
```

---
## 坐席续费
### 接口信息
描述 | 协议 | 请求方式 | 请求地址 | 数据返回格式
---|---|---|---|---|
坐席续费 | http  | post | /index/AnCompanyManage/sitTableRenew | json
### 请求参数
参数 | 是否必传 | 可否为空 | 说明   | 示例
---|---|---|---|---|
start_time | 否 | 否 | 生效时间（未到期的不传） | 2020-09-09
expired_time | 是 | 否 | 失效时间 | 2020-09-09
id | 是 | 否 | 企业id | 1
### 数据返回
```
{
    "code": 1,
    "msg": "成功",
    "data":
}
```