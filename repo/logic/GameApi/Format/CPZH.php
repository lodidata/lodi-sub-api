<?php
/**
 * User: ny
 * Date: 2019-05-09
 * Time: 11:15
 * Des :
 */

namespace Logic\GameApi\Format;


use Model\LotteryChaseOrder;

class CPZH {

    /**
     * 过滤条件详情
     * @param bool $isShowState
     * @return array
     */
    public function filterDetail(bool $isShowState){
        return LotteryChaseOrder::filterDetail($isShowState);
    }
    
    /**
     * 统计信息 <此方法，在这里无用>
     * @param array $condition
     * @return array
     */
    public static function statistics(array $condition){
        $result = [
            'all_bet' => 0,
            'cell_score' => 0,
            'user_count' => 0,
            'total_count' =>0,
        ];
        return $result;
    }


    /**
     * 订单列表 -> <此方法，在这里无用>
     * @param $condition
     * @param int $page
     * @param int $pageSize
     * @return array
     */
    public static function recordsAdmin($condition, $page = 1, $pageSize = 20){
        return array([], 0);
    }

    /**
     * 订单列表
     * @param $condition
     * @param int $page
     * @param int $pageSize
     * @return array
     */
    public function records($condition, $page = 1, $pageSize = 20) {
        return LotteryChaseOrder::records($condition, $page, $pageSize);
    }

    /**
     * 订单详情
     * @param $condition
     * @return array
     * @throws \Exception
     */
    public function detail($condition) {
        return LotteryChaseOrder::detail($condition);
    }

}