<?php

namespace Logic\Level;

use DB;

/**
 * 用户层级模块
 */
class Level extends \Logic\Logic {

    /*
     * 层级线上支付设定
     */
    public function onlineSet($id, $data) {
        //必须先删除之前的数据
        DB::table('level_online')->where('level_id', $id)->delete();
        return DB::table('level_online')->insert($data);
    }

    /*
     * 层级线下支付设定
     */
    public function offlineSet($id, $data) {
        //必须先删除之前的数据
        DB::table('level_offline')->where('level_id', $id)->delete();
        return DB::table('level_offline')->insert($data);
    }

    /*
     * 获取线上支付设定列表
     */
    public function getOnlineSet($levelId) {
        //$data = \Logic\Recharge\Recharge::requestPaySit('onlineChannel');
        //获取到该层级对应的支付name
        $levelData = DB::table('pay_config')->where('status', 'enabled')->select(['type'])->get()->toArray();
        $levelResult = [];
        $result = [];
        foreach ($levelData ?? [] as $k => $v) {
            $result[] = [
                'name' => $v->type,
                'status' => 1
            ];
        }
        /*
        if(is_array($data)) {
            foreach ($data ?? [] as $k => $v) {
                $status = 0;
                if (in_array($v['pay_name'], $levelResult)) {
                    $status = 1;
                }
                $result[] = ['name' => $v['pay_name'], 'status' => $status];
            }
        }*/
        return array_values(array_unique($result, SORT_REGULAR));
    }

    /*
     * 获取登记支付设定列表
     */
    public function getLevelOnline($levelId) {
        //$data = \Logic\Recharge\Recharge::requestPaySit('onlineChannel');
        //获取到该层级对应的支付name
        $levelData = DB::table('level_online')->where('level_id', $levelId)->select(['pay_plat'])->get()->toArray();
        $result = [];
        foreach ($levelData ?? [] as $k => $v) {
            $result[] = $v->pay_plat;
        }
        return $result;
    }

    /*
     * 获取线上支付设定列表
     */
    public function getOfflineSet($levelId) {
        $data = DB::table('bank_account')->whereRaw("find_in_set('enabled',state)")
            ->get(["name", "type", "id"])->toArray();
        //获取到该层级对应的支付name
        $levelData = DB::table('level_offline')->where('level_id', $levelId)
            ->select(['pay_id'])->get()->toArray();
        $levelResult = [];
        foreach ($levelData ?? [] as $k => $v) {
            $levelResult[] = $v->pay_id;
        }
        $typeArr = ['1' => '网银', '2' => '支付宝', '3' => '微信', '4' => 'QQ支付', '5' => '京东支付'];
        $result = [];
        foreach ($data ?? [] as $k => $v) {
            $status = 0;
            if (in_array($v->id, $levelResult)) {
                $status = 1;
            }
            $result[] = ['name' => $v->name, 'id' => $v->id, 'type' => $typeArr[$v->type], 'status' => $status];
        }
        return $result;
    }


    /*
     * 获取层级线上支付列表
     */
    public function getLevelOnlineSet($levelId) {
        $data = DB::table('level_online')->where('level_id', $levelId)->pluck('pay_plat as name')->toArray();
        $data = array_values(array_unique($data));
        return $data;
    }

    /*
    * 获取层级线下支付列表 需连表查出名称，数据库只存了ID
    */
    public function getLevelOfflieSet($levelId) {
        $data = DB::table('level_offline as l')->where('l.level_id', $levelId)
            ->leftJoin('bank_account as b', 'l.pay_id', '=', 'b.id')
            ->whereRaw("find_in_set('enabled',state)")
            ->select(['b.name', 'b.type'])
            ->get()->toArray();
        $result = [];
        $typeArr = ['1' => '网银', '2' => '支付宝', '3' => '微信', '4' => 'QQ支付', '5' => '京东支付'];
        foreach ($data ?? [] as $k => $v) {
            $result[] = $typeArr[$v->type] . "-" . $v->name;
        }
        return $result;
    }
}