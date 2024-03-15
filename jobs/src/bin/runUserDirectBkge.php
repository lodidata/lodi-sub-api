<?php

//获取直推返水比例设置
$bkgeData = \DB::table('direct_bkge')
                ->get(['register_count','recharge_count','bkge_increase'])
                ->toArray();

//比例排序
$register      = array_column($bkgeData,'register_count');
array_multisort($register,SORT_ASC,$bkgeData);
//获取规则最大返水比例
$max           = end($bkgeData);

$limit = 1000;
$offset = 0;
$data = [];
while(1) {
    $userDirectBkge = \DB::table('user_data')
                         ->offset($offset)
                         ->limit($limit)
                         ->get(['user_id','direct_deposit','direct_register'])
                         ->toArray();

    if(empty($userDirectBkge)) {
        break;
    }

    //获取当前阶级 直推返水比例
    foreach($userDirectBkge as $value) {
        $return_ratio = 0;
        foreach ($bkgeData as $item){
            //超过最大等级，按最高比例
            if ($value->direct_register > $max->register_count && $value->direct_deposit > $max->recharge_count){
                $return_ratio = $max->bkge_increase;
                break;
            }
            //注册人数
            if ($value->direct_register >= $item->register_count && $value->direct_deposit >=  $item->recharge_count){
                $return_ratio = $item->bkge_increase;
            }

            $data[$value->user_id]['user_id'] = $value->user_id;
            $data[$value->user_id]['direct_bkge_increase'] = $return_ratio;
        }
    }

    if (!empty($data)) {
        $updateColumn = ['direct_bkge_increase'];
        $updateSql = "UPDATE `user_data` SET ";
        $sets      = [];
        foreach ($updateColumn as $uColumn) {
            $setSql = "`" . $uColumn . "` = CASE ";
            foreach ($data as $key=>$val) {
                $setSql .= " WHEN `user_id` = '".$key."' THEN ".$val[$uColumn];
            }
            $setSql .= " ELSE `" . $uColumn . "` END ";
            $sets[] = $setSql;
        }
        $updateSql .= implode(', ', $sets);
        $userIdStr = implode(',', array_column($data,'user_id'));
        $updateSql = rtrim($updateSql, ", ") . " WHERE `user_id` IN (" . $userIdStr . ")";
        \DB::update($updateSql);
        $data = [];
    }

    $offset += $limit;
}