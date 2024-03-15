<?php

namespace Model;

use DB;

class FundsTrialDealLog extends \Illuminate\Database\Eloquent\Model {
    /**
     * 交易类别：收入
     *
     * @var int
     */
    const CATEGORY_INCOME = 1;
    /**
     * 交易类别： 支出
     *
     * @var int
     */
    const CATEGORY_COST = 2;
    /**
     * 彩票派彩
     *
     * @var int
     */
    const TYPE_PAYOUT_LOTTERY = 104;
    /**
     * 手动增加余额
     *
     * @var int
     */
    const TYPE_ADDMONEY_MANUAL = 112;
    /**
     * 彩票下注
     *
     * @var int
     */
    const TYPE_LOTTERY_BETTING = 202;

    protected $table = 'funds_trial_deal_log';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'username',
        'deal_number',
        'order_number',
        'deal_type',
        'deal_category',
        'deal_money',
        'balance',
        'memo',
        'wallet_type',
        'status',
        // 'created'
        'withdraw_bet',
    ];

    public static function boot() {
        global $playLoad;

        parent::boot();

        static::creating(function ($obj) use ($playLoad) {
            $obj->created = date('Y-m-d H:i:s');
            $obj->deal_number = FundsTrialDealLog::generateDealNumber();
            $obj->status = 1;
        });
    }

    /**
     * 生成流水号
     *
     * @param int $rand
     *
     * @return string
     */
    public static function generateDealNumber($rand = 999999999, $length = 9) {
        return date('mdhis') . str_pad(mt_rand(1, $rand), $length, '0', STR_PAD_LEFT);
    }

    public static function getRecords($condition, $page = 1, $pageSize = 20) {
        global $app;
        $where = [];
        $offset = ($page - 1) * $pageSize;

        $where[] = " f.deal_type not in (400,401,402,403,405,406) ";
        isset($condition['user_id']) && $where[] = " f.user_id = '{$condition['user_id']}'";
        isset($condition['deal_category']) && $where[] = " f.deal_category = '{$condition['deal_category']}'";
        isset($condition['deal_type']) && $where[] = " f.deal_type in ({$condition['deal_type']}) ";
        isset($condition['start_time']) && $where[] = " f.created >= '{$condition['start_time']}'";
        isset($condition['pc_start_time']) && $where[] = " f.created >= FROM_UNIXTIME('{$condition['pc_start_time']}')";
        isset($condition['end_time']) && $where[] = " f.created <= '{$condition['end_time']}'";
        isset($condition['pc_end_time']) && $where[] = " f.created <= FROM_UNIXTIME('{$condition['pc_end_time']}')";

        //查询流水不需要包含打码量流水时
        if (isset($condition['without_withdraw']) && $condition['without_withdraw'] == true) {
            $where[] = ' (f.deal_money != 0 OR f.coupon_money != 0)';
        }

        //查询流水不需要包含修改可提余额的记录时
        if (isset($condition['without_free_money']) && $condition['without_free_money'] == true) {
            $where[] = ' f.deal_category != 4';
        }

        $where = count($where) ? 'WHERE ' . implode(' AND', $where) : '';
        $sql = <<<SQL
SELECT
    SQL_CALC_FOUND_ROWS
    f.id,
    f.order_number,
    f.created,
    UNIX_TIMESTAMP(f.created) pc_created,
    f.deal_type,
    f.deal_category,
    f.deal_money,
    f.balance,
    f.withdraw_bet,
    f.coupon_money,
    f.memo,
    c.sum,
    c.sum_coupon
FROM
    funds_trial_deal_log AS f,
    (
        SELECT
            SUM(f.deal_money) AS sum, SUM(f.coupon_money) AS sum_coupon
        FROM
            funds_deal_log AS f
        $where
    ) AS c
$where
order by id desc
LIMIT $offset, $pageSize;
SQL;

        return [
            $app->getContainer()->db->getConnection()
                ->select($sql),
            current($app->getContainer()->db->getConnection()
                ->selectOne('SELECT FOUND_ROWS()')),
        ];
    }
}