<?php

namespace Logic\GameApi\CKFormat;

use Logic\GameApi\CKFormat;
use Logic\GameApi\Format;

class OrderRecordCommon {

    /**
     * 不同的类型,对应的状态
     */
    const TYPE_TARGET_STATE = [
        'CPZG' => [
            ['key' => '', 'name' => '全部'],
            ['key' => 'winning', 'name' => '已中奖'],
            ['key' => 'lose', 'name' => '未中奖'],
            ['key' => 'created', 'name' => '待开奖'],
        ],
        'CPZH' => [
            ['key' => '', 'name' => '全部'],
            ['key' => 'complete', 'name' => '已结束'],
            ['key' => 'created', 'name' => '追号中'],
            ['key' => 'cancel', 'name' => '已撤单'],
        ],
        'default' => [
            ['key' => '', 'name' => '全部'],
            ['key' => 'winning', 'name' => '已中奖'],
            ['key' => 'lose', 'name' => '未中奖'],
        ],
    ];


    /**
     * 兼容筛选条件，用于兼容更多第三方
     * @param array $condition
     * @return array
     * @throws \Exception
     */
    public static function filter(array $condition) {
        // 转换 筛选 为了兼容 更多第三方
        if (isset($condition['category']) && isset($condition['key'])) {
            $params_category = explode("|", $condition['category']);
            $params_key = explode("|", $condition['key']);
            if (count($params_category) == count($params_key)) {
                foreach ($params_category as $pos => $category) {
                    $condition[$category] = $params_key[$pos];
                }
                // 删除无用
                unset($condition['category']);
                unset($condition['key']);
            } else {
                throw new \Exception("参数有误");
            }
        }
        return array_filter($condition);
    }

    /**
     * 测试方法
     * @param string $type_name
     * @return array
     * @throws \Exception
     */
    public static function test(string $type_name) {
        $result = [];
        // 过滤
        $result['filter'] = self::filterDetail($type_name);
        // admin 注单列表
        $orderConditionAdmin = [
            'info' => true,
            'admin_info' => true,
//            'state' => 'winning',
            'type_name'  => $type_name,
        ];
        // 统计
        list($re, $total,$total_all_bet,$total_cell_score,$total_user_count) = self::recordsAdmin($type_name, $orderConditionAdmin);
        $result['admin_order'] = $re;
        $result['statistics'] = [
            'total' => $total,
            'total_all_bet' => $total_all_bet,
            'total_cell_score' => $total_cell_score,
            'total_user_count' => $total_user_count,
        ];
        // 注单列表
        $orderCondition = [
            'type_name'  => $type_name,
        ];
        $result['order'] = self::records($type_name, $orderCondition);
        // 详情
        $condition = [
//            'order_number' => 17945,
            'info' => true,
        ];
        $result['detail'] = self::detail($type_name, $condition);
        return $result;
    }

    /**
     * 统计信息
     * @param string $type_name
     * @param array $condition
     * @return array
     * @throws \Exception
     */
    public static function statistics(string $type_name, array $condition) {
        $condition['type_name'] = $type_name;
        global $app;
        $is_config_close_ck = $app->getContainer()->ckdb  === false ? 1 : 0;
        $is_redis_close_ck = $app->getContainer()->redis->get('select_close_ck');
        if($is_redis_close_ck == 1 || $is_config_close_ck){
            $resolve = Format::resolve($type_name);
            if ($resolve instanceof Format){
                return [
                    'all_bet' => 0,
                    'cell_score' => 0,
                    'user_count' => 0,
                    'send_prize' => 0,
                    'total_count' => 0,
                ];
            }else{
                return $resolve->statistics($condition);
            }
        }else{
            $resolve = CKFormat::resolve($type_name);
            if ($resolve instanceof CKFormat){
                return [
                    'all_bet' => 0,
                    'cell_score' => 0,
                    'user_count' => 0,
                    'send_prize' => 0,
                    'total_count' => 0,
                ];
            }else{
                return $resolve->statistics($condition);
            }
        }
    }

    /**
     * 过滤
     * @param string $type_name
     * @param bool $isShowState
     * @return array
     * @throws \Exception
     */
    public static function filterDetail(string $type_name, bool $isShowState = false) {
        $condition['type_name'] = $type_name;
        global $app;
        $is_config_close_ck = $app->getContainer()->ckdb  === false ? 1 : 0;
        $is_redis_close_ck = $app->getContainer()->redis->get('select_close_ck');
        if($is_redis_close_ck == 1 || $is_config_close_ck) {
            $resolve = Format::resolve($type_name);
            if ($resolve instanceof Format) {
                return [];
            } else {
                return $resolve->filterDetail($isShowState);
            }
        }else{
            $resolve = CKFormat::resolve($type_name);
            if ($resolve instanceof CKFormat) {
                return [];
            } else {
                return $resolve->filterDetail($isShowState);
            }
        }
    }

    /**
     * 获取订单列表
     * @param string $type_name 游戏类型
     * @param array $condition
     * @param int $page
     * @param int $pageSize
     * @return array
     * @throws \Exception
     */
    public static function records(string $type_name, array $condition, int $page = 1, int $pageSize = 20) {
        $condition['type_name'] = $type_name;
        global $app;
        $is_config_close_ck = $app->getContainer()->ckdb  === false ? 1 : 0;
        $is_redis_close_ck = $app->getContainer()->redis->get('select_close_ck');
        if($is_redis_close_ck == 1 || $is_config_close_ck) {
            $resolve = Format::resolve($type_name);
            if ($resolve instanceof Format) {
                $result = [];
            } else {
                $result = $resolve->records($condition, $page, $pageSize);
            }
        }else{
            $resolve = CKFormat::resolve($type_name);
            if ($resolve instanceof CKFormat) {
                $result = [];
            } else {
                $result = $resolve->records($condition, $page, $pageSize);
            }
        }

        // 加入统计
        $condition['statistics'] = true;
        //统计数据添加缓存 缓存1分钟
        global $app;
        $redis = $app->getContainer()->redis;
        $redis_key = \Logic\Define\CacheKey::$perfix['orderRecord'].'_h5'.md5($type_name.json_encode($condition));
        $statistics = $redis->get($redis_key);
        if(!$statistics){
            $statistics = self::statistics($type_name, $condition);
            $redis->setex($redis_key, 60, json_encode($statistics));
        }else{
            $statistics = json_decode($statistics, true);
        }

        return array($result,$statistics['total_count'], $statistics['all_bet'],$statistics['cell_score'],$statistics['send_prize'],$statistics['user_count']);
    }

    /**
     * 获取订单列表 -> admin
     * @param string $type_name 游戏类型
     * @param array $condition
     * @param int $page
     * @param int $pageSize
     * @return array(列表，总共多少条，总下注，总有效下注,总共多少人)
     * @throws \Exception
     */
    public static function recordsAdmin(string $type_name, array $condition, int $page = 1, int $pageSize = 20) {
        $condition['type_name'] = $type_name;
        global $app;
        $is_config_close_ck = $app->getContainer()->ckdb  === false ? 1 : 0;
        $is_redis_close_ck = $app->getContainer()->redis->get('select_close_ck');
        if($is_redis_close_ck == 1 || $is_config_close_ck) {
            $resolve = Format::resolve($type_name);
            if ($resolve instanceof Format) {
                $result = [];
            } else {
                $result = $resolve->recordsAdmin($condition, $page, $pageSize);
            }
        }else{
            $resolve = CKFormat::resolve($type_name);
            if ($resolve instanceof CKFormat) {
                $result = [];
            } else {
                $result = $resolve->recordsAdmin($condition, $page, $pageSize);
            }
        }
        // 加入统计
        $condition['statistics'] = true;
        //统计数据添加缓存 缓存1分钟
        global $app;
        $redis = $app->getContainer()->redis;
        $redis_key = \Logic\Define\CacheKey::$perfix['orderRecord'].md5($type_name.json_encode($condition));
        $statistics = $redis->get($redis_key);
        if(!$statistics){
            $statistics = self::statistics($type_name, $condition);
            $redis->setex($redis_key, 60, json_encode($statistics));
        }else{
            $statistics = json_decode($statistics, true);
        }

        return array($result,$statistics['total_count'], $statistics['all_bet'],$statistics['cell_score'],$statistics['send_prize'],$statistics['user_count']);
    }


    /**
     * 获取订单详情
     * @param string $type_name 游戏类型
     * @param array $condition
     * @return array
     * @throws \Exception
     */
    public static function detail(string $type_name, array $condition) {
        $condition['type_name'] = $type_name;
        global $app;
        $is_config_close_ck = $app->getContainer()->ckdb  === false ? 1 : 0;
        $is_redis_close_ck = $app->getContainer()->redis->get('select_close_ck');
        if($is_redis_close_ck == 1 || $is_config_close_ck) {
            $resolve = Format::resolve($type_name);
            if ($resolve instanceof Format) {
                return null;
            } else {
                $res = $resolve->detail($condition);
                return $res;
            }
        }else{
            $resolve = CKFormat::resolve($type_name);
            if ($resolve instanceof CKFormat) {
                return null;
            } else {
                $res = $resolve->detail($condition);
                return $res;
            }
        }
    }


}