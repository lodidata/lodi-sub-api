<?php

namespace Logic\Admin;

use Logic\Logic;
use Model\AdminLog as LogModel;
use Utils\Client;

class Log extends Logic {

    const METHOD_DESCRIPTION = [
        'GET'    => '获取',
        'POST'   => '创建',
        'PUT'    => '修改',
        'PATCH'  => '修改',
        'DELETE' => '删除'
    ];
    const MODULES = [
        '彩票',
        '注单',
        '现金',
        '用户',
        '系统',
        'APP',
        '游戏',
        '网站',
        '活动',
        '菜单'
    ];

    const MODULE_NAME = [
        '彩票管理',
        '注单查询',
        '现金管理',
        '账号管理',
        '系统管理',
        'APP管理',
        '游戏管理',
        '网站管理',
        '活动管理',
        '菜单管理',
        '客服电访',
        '渠道管理'
    ];

    const MODULE_LOTTERY = 0;

    const MODULE_ORDER = 1;

    const MODULE_CASH = 2;

    const MODULE_USER = 3;

    const MODULE_SYSTEM = 4;

    const MODULE_APP = 5;

    const MODULE_GAME = 6;

    const MODULE_WEBSITE = 7;

    const MODULE_ACTIVE = 8;

    const MODULE_MENU = 9;

    const MODULE_KEFU = 10;

    const MODULE_CHANNEL =11;

    /**
     * 新增操作记录
     *
     * @param int|null $uid2 被操作用户ID
     * @param string|null $uname2 被操作用户名
     * @param int $module 模块
     * @param string|null $module_child 子模块
     * @param string|null $fun_name 功能名称
     * @param string|null $type 操作类型
     * @param int $status 操作状态 0：失败 1：成功
     * @param string|null $remark 详情记录
     *
     * @return mixed
     */
    public function create($uid2 = null, $uname2 = null, $module, $module_child = null, $fun_name = null, $type = null, $status = 1, $remark = null) {
        global $playLoad;
        $data = [
            'ip'           => Client::getIp(),
            'uid'          => $playLoad['uid'] ?? 0,
            'uname'        => $playLoad['nick'] ?? '',
            'uid2'         => $uid2,
            'uname2'       => $uname2,
            'module'       => self::MODULE_NAME[$module],
            'module_child' => $module_child,
            'fun_name'     => $fun_name,
            'type'         => $type,
            'status'       => $status,
            'remark'       => $remark,
        ];

        $log = new LogModel();
        return $log->insertGetId($data);
    }

    /**
     * 写入log
     * @param string $method
     * @param int|null $target_uid 操作会员 可为空
     * @param string|null $target_nick 操作目标 可为空
     * @param int $module_type     子模块类型
     * @param string $module_child 子模块  如:系统设置
     * @param string $fun_name 调用方法 如：登录注册
     * @param string $remark 详细记录 如：修改xxx
     * @return int
     */
    public function log(string $method,int $target_uid = null,string  $target_nick = null,int $module_type, string $module_child, string $fun_name , string $remark){
        global $playLoad;
        $data = [
            'ip'           => Client::getIp(),
//            'uid'          => $playLoad['uid'],
//            'uname'        => $playLoad['nick'],
            'uid'          => 104,
            'uname'        => '测试',
            'uid2'         => $target_uid,
            'uname2'       => $target_nick,
            'module'       => $module_type,
            'module_child' => $module_child,
            'fun_name'     => $fun_name,
            'type'         => self::METHOD_DESCRIPTION[$method],// 根据不同方法判断
            'status'       => 1,
            'remark'       => $remark,
        ];

        $log = new LogModel();
        return $log->insertGetId($data);
    }
}