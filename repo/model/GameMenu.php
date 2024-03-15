<?php
/**
 * User: nk
 * Date: 2019-01-14
 * Time: 15:42
 * Des :
 */

namespace Model;

use DB;
use Model\Admin\LogicModel;
use Model\Game3th;

/**
 * Class GameMenu 首页菜单
 * @package Model
 */
class GameMenu extends LogicModel {
    protected $table = 'game_menu';

    /**
     * 获取菜单
     * @param $condition
     * @param array $columns
     * @return \Illuminate\Support\Collection
     */
    public static function getMenu($condition, $columns = ['id', 'pid', 'type', 'name']) {
        if (isset($condition['info'])){
            $columns[] = 'status';
        }
        return DB::table('game_menu')
            ->where('status','enabled')
            ->where('switch','enabled')
            //->whereNotIn('id',[23,26,27])
            ->orderBy('pid')
            ->orderBy('sort')
            ->get($columns);
    }


    /**
     * 查询菜单详情
     * @param $condition
     * @return mixed
     */
    public static function getMenuInfo($condition) {
        $table = self::getMenu($condition);
        return $table->first();
    }

    /**
     * 查询菜单列表
     * @param  [type]  $condition [description]
     * @param  integer $page [description]
     * @param  integer $pageSize [description]
     * @return mixed [type]            [description]
     */
    public static function getMenus($condition, $page = 1, $pageSize = 20) {
        $table = self::getMenu($condition);
        return $table->paginate($pageSize, ['*'], 'page', $page);
    }


    /**
     * 查询菜单列表
     * @param array $condition
     * @return mixed [type]            [description]
     */
    public static function getMenuAll($condition=[]) {
        $table = self::getMenu($condition);
        return $table->toArray();
    }


    /**
     * 菜单信息 一级 + 二级
     * @return mixed [type]            [description]
     */
    public static function getMenuAllList() {
        $data = GameMenu::getMenuAll();
        $newArr = array();
        $num = 0;
        foreach ($data as $item) {
            $item = (array)$item;
            if ($item['pid'] == 0) {
                $newArr[$item['id']] = $item;
            } else {
                if (isset($newArr[$item['pid']])) {
                    $newArr[$item['pid']]['childrens'][$num] = $item;
                    $num++;
                    $newArr[$item['pid']]['childrens'] = array_values($newArr[$item['pid']]['childrens']);
                }
            }
        }

        // 过滤掉 只有二级的
        $filter = array();
        foreach ($newArr as $item){
            if (isset($item['childrens'])){
                $filter[] = $item;
            }
        }
        // 彩票比较特殊
        foreach ($filter as $pos => $item){
            if ($item['type'] == 'CP'){
                $filter[$pos]['childrens'] = [
                    [
                        'type' => 'CPZG',
                        'name' => 'lotto',
                    ],
                    /*[
                        'type' => 'CPZH',
                        'name' => '追号',
                    ]*/
                ];
            }
        }
        return $filter;
    }

    /**
     * 菜单信息 只拿二级,用于团队信息，或其他简单筛选
     */
    public static function getMenuChilds(){
        $data = GameMenu::getMenuAll();
        $newArr = array();
        foreach ($data as $item) {
            $item = (array)$item;
            if ($item['pid'] != 0) {
                if ($item['type'] == 'ZYCPSTA'){
                    $item['type'] = 'CPZG';
                    $item['name'] = 'lotto';
                }/*else if ($item['type'] == 'ZYCPCHAT'){
                    //$item['type'] = 'CPZH';
                    //$item['name'] = '彩票追号';
                }*/
                $newArr[] = $item;
            }
        }

        return $newArr;
    }

    /**
     * 删除 横版menu redis缓存
     * @param null $id
     */
    public static function delMenuRedis(){
        global $app;
        $redis = $app->getContainer()->redis;
        $redis->del(\Logic\Define\CacheKey::$perfix['menuList']);
        //今日热门系列
        $redis->del(\Logic\Define\CacheKey::$perfix['todayTopGameList'].date('Y-m-d'));
    }

    /**
     * 删除 竖版menu redis缓存
     * @param null $id
     */
    public static function delVerticalMenuRedis($id=null){
        global $app;
        $redis = $app->getContainer()->redis;
        //今日热门系列
        $redis->del(\Logic\Define\CacheKey::$perfix['todayTopGameList'].date('Y-m-d'));
        //热门系列
        $redis->del(\Logic\Define\CacheKey::$perfix['todayHotGameList'].date('Y-m-d'));
        //首页全部游戏
        $redis->del(\Logic\Define\CacheKey::$perfix['allGameList']);
        //首页热门游戏
        $redis->del(\Logic\Define\CacheKey::$perfix['hotGameList']);
        if($id){
            $game_id     = Game3th::getGameId($id);
            $redis->del(\Logic\Define\CacheKey::$perfix['verticalMenuList'].':'.$game_id);
        }else{
            $redis->del(\Logic\Define\CacheKey::$perfix['verticalMenuList']);
        }
    }

    public static function getRondomGameIdList($data, $length=10){
        $id_list     = array_column($data,'id');
        $new_id_list = [];
        shuffle($id_list);

        for($i=0; $i< $length; $i++){
            if(!$id_list) break;
            $new_id_list[] = array_shift($id_list);
        }
        return $new_id_list;
    }

    public static function getGameIdList($data){
        return array_column($data,'id');
    }

    /**
     * 格式化收藏用的菜单列表
     * @return array
     */
    public static function formatFavoriteMenu(){
        $data = self::where('status','enabled')
                    ->where('pid',0)
                    ->orderBy('sort')
                    ->get(['id','type','img'])->toArray();
        $data && $data = array_column($data,null,'type');
        return $data;
    }

    /**
     * 获取一级游戏 type
     * @return mixed
     */
    public static function getGameTypeList(){
        return self::where('pid',0)->pluck('type')->toArray();
    }

    /**
     * 获取一级类 id:type
     * @return array|mixed
     */
    public static function getParentMenuIdType(){
        global $app;
        $redis = $app->getContainer()->redis;
        $redisKey = 'menu_parent_list';
        $data = $redis->get($redisKey);
        if(is_null($data)){
            $list = self::where('pid',0)
                ->where('switch','enabled')
                ->where('status', 'enabled')
                ->get(['id','type'])->toArray();
            $data = [];
            foreach($list as $val){
                $data[$val['id']] = $val['type'];
            }
            $redis->setex($redisKey, 3600, json_encode($data));
        }else{
            $data = json_decode($data, true);
        }
        return $data;
    }
}