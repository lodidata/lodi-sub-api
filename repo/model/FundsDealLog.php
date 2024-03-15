<?php

namespace Model;

use DB;

class FundsDealLog extends \Illuminate\Database\Eloquent\Model {

    const WALLET_TYPE_PRIMARY = 1;  //主钱包
    const WALLET_TYPE_SUB = 2; //子钱包
    const CATEGORY_INCOME = 1;  //交易类别：收入
    const CATEGORY_COST = 2;  //交易类别： 支出
    const CATEGORY_TRANS = 3; //交易类别： 额度转换
    const CATEGORY_FREEMONEY = 4;  //交易类别： 修改可提余
    const TYPE_INCOME_ONLINE = 101; //线上入款
    const TYPE_INCOME_OFFLINE = 102;  //线下入款
    const TYPE_PAYOUT_LOTTERY = 104;  //彩票派彩
    const TYPE_ACTIVITY = 105; //优惠活动
    const TYPE_INCOME_MANUAL = 106;  //手动存款
    const TYPE_REBET = 107; //返水优惠
    const TYPE_AGENT_CHARGES = 108; //代理退佣
    const TYPE_SALES = 109;  //销售返点
    const TYPE_CANCEL_ORDER = 110; //彩票撤单
    const TYPE_ADDMONEY_MANUAL = 112;  //手动增加余额
    const TYPE_REBET_MANUAL = 113; //手动发放返水
    const TYPE_ACTIVITY_MANUAL = 114; //手动发放优惠
    const TYPE_WIRTDRAW_REFUSE   = 118; //拒绝出款
    const TYPE_INCREASE_FREEMONEY_MANUAL = 120; //手动增加可提余额
    const TYPE_DECREASE_FREEMONEY_MANUAL = 119; //手动减少可提余额
    const TYPE_WITHDRAW = 201;  //会员提款
    const TYPE_LOTTERY_BETTING = 202; //彩票下注
    const TYPE_WITHDRAW_MANUAL = 204; //手动提款
    const TYPE_REDUCE_MANUAL = 207;  //手动减少余额
    const TYPE_CHASE_MANUAL = 209;  //追号冻结
    const TYPE_WITHDRAW_ONFREEZE = 208;  //提款审核中
    const TYPE_WIRTDRAW_CUT = 210;  //提现扣款
    const TYPE_REDUCE_MANUAL_OTHER = 211;  //减少余额 其它
    const TYPE_ADDMONEY_MANUAL_OTHER = 212;  //减少余额 其它
    const TYPE_WITHDRAW_CONFISCATE = 213;  //提现没收

    //直推余额
    const TYPE_DIRECT_REWARD_INCOME = 220;  //直推奖励 收入
    const TYPE_DIRECT_REWARD_COST = 221;  //直推奖励 支出
    const TYPE_INCREASE_DIRECT_MANUAL = 222;  //增加直推奖励
    const TYPE_DECREASE_DIRECT_MANUAL = 223;  //扣除直推奖励
    const TYPE_INCREASE_DIRECT_DML = 224;  //直推奖励增加打码量

    //股东分红钱包
    const TYPE_WITHDRAW_SHARE = 214;  //股东分红提现审核
    const TYPE_INCREASE_SHARE_MANUAL = 215;  //股东分红增加可提余额
    const TYPE_DECREASE_SHARE_MANUAL = 216;  //股东分红减少可提余额
    const TYPE_WIRTDRAW_SHARE_REFUSE = 217;  //股东分红提现失败
    const TYPE_SHARE_WITHDRAW = 218;         //股东分红提现成功
    const TYPE_SHARE_WITHDRAW_CONFISCATE = 219;  //股东分红提现没收


    const TYPE_CTOM = 301;  //子转主钱包
    const TYPE_MTOC = 302; //主转子钱包
    const TYPE_CTOM_MANUAL = 303;  //手动子转钱包
    const TYPE_MTOC_MANUAL = 304;  //手动主转子钱包
    const TYPE_MFOC_MANUAL = 305;  //手动主转保险箱
    const TYPE_FMOC_MANUAL = 306;  //手动保险箱转主
    const TYPE_LEVEL_MANUAL1 = 308;//等级赠送  晋升彩金
    const TYPE_LEVEL_MANUAL2 = 309;//等级赠送  转卡彩金
    const TYPE_LEVEL_MONTHLY = 310;//不同等级对应的月俸禄奖金
    const TYPE_CTOM_SHARE = 311;//股东转入主钱包
    const TYPE_LEVEL_WEEK = 312;//不同等级对应的月薪奖金
    const TYPE_LOTTERY_SETTLE = 400;//彩票结算未中奖
    const TYPE_HAND_DML_ADD = 405;//手动增加打码量
    const TYPE_HAND_DML_PLUS = 406;//手动减少打码量
    const TYPE_THIRD_SETTLE = 408;//第三方结算
    const TYPE_TRANSFER_XIMA = 501;  //洗码活动
    const TYPE_DAILY_REBET = 701; //日反水
    const TYPE_WEEKLY_REBET = 702; //周反水
    const TYPE_MONTHLY_REBET = 703; //月反水

    protected $table = 'funds_deal_log';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'user_type',
        'username',
        'deal_number',
        'order_number',
        'deal_type',
        'deal_category',
        'deal_money',
        'coupon_money',
        'balance',
        'memo',
        'wallet_type',
        'status',
        // 'created'
        'withdraw_bet',
        'total_bet',
        'total_require_bet',
        'free_money',
        'admin_user',
        'admin_id',
    ];

    public static function boot() {
        global $playLoad;

        parent::boot();

        static::creating(function ($obj) use ($playLoad) {
            $obj->created = date('Y-m-d H:i:s');
            $obj->deal_number = FundsDealLog::generateDealNumber();
            $obj->coupon_money = $obj->coupon_money ?? 0;
            $obj->status = 1;
            $obj->user_type = empty($obj->user_type) ? 1 : $obj->user_type; // 代理改人人代理默认1
            $obj->admin_id = !empty($obj->admin_id) ? $obj->admin_id : ($playLoad['uid'] ?? 0);
            $obj->admin_user = !empty($obj->admin_user) ? $obj->admin_user : ($playLoad['nick'] ?? '');
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
        $query = \DB::table('funds_deal_log as f');
        $query->whereIn('f.deal_type', [104, 202, 101, 102, 201, 208,118,110,107,108,301,302]);
        isset($condition['user_id']) && $query->where('f.user_id', $condition['user_id']);
        isset($condition['deal_category']) && $query->where('f.deal_category', $condition['deal_category']);
        isset($condition['deal_type']) && $query->where('f.deal_type', $condition['deal_type']);
        isset($condition['start_time']) && $query->where('f.created','>=', $condition['start_time']);
        isset($condition['pc_start_time']) && $query->whereRaw("f.created >= FROM_UNIXTIME(?)",[$condition['pc_start_time']]);
        isset($condition['end_time']) && $query->where('f.created','<=', $condition['end_time']);
        isset($condition['pc_end_time']) && $query->whereRaw("f.created <= FROM_UNIXTIME(?)",[$condition['pc_end_time']]);

        //查询流水不需要包含打码量流水时
        if (isset($condition['without_withdraw']) && $condition['without_withdraw'] == true) {
            $query->whereRaw("(f.deal_money != 0 OR f.coupon_money != 0)");
        }

        //查询流水不需要包含修改可提余额的记录时
        if (isset($condition['without_free_money']) && $condition['without_free_money'] == true) {
            $query->where('f.deal_category','!=',4);
        }
        $sub_query = clone $query;
        $sub_query->selectRaw('SUM(f.deal_money) AS sum, SUM(f.coupon_money) AS sum_coupon');
        $result = $query->selectRaw('f.id,f.order_number,f.created,UNIX_TIMESTAMP(f.created) pc_created,f.deal_type,f.deal_category,f.deal_money,f.balance,f.withdraw_bet,
                                f.coupon_money,f.memo,c.sum,c.sum_coupon')->joinSub($sub_query,'c',null,null,null,'inner',true)->orderBy('id','desc')
            ->paginate($pageSize,['*'],'page',$page)->toArray();

        return [
            $result['data'],
            $result['total'],
        ];
    }

    public static function getRecordsSum($condition) {
        $query = \DB::table('funds_deal_log as f');
        $query->whereIn('f.deal_type', [400,401,402,403,405,406]);
        isset($condition['user_id']) && $query->where('f.user_id', $condition['user_id']);
        isset($condition['deal_category']) && $query->where('f.deal_category', $condition['deal_category']);
        isset($condition['deal_type']) && $query->where('f.deal_type', $condition['deal_type']);
        isset($condition['start_time']) && $query->where('f.created','>=', $condition['start_time']);
        isset($condition['pc_start_time']) && $query->whereRaw("f.created >= FROM_UNIXTIME(?)",[$condition['pc_start_time']]);
        isset($condition['end_time']) && $query->where('f.created','<=', $condition['end_time']);
        isset($condition['pc_end_time']) && $query->whereRaw("f.created <= FROM_UNIXTIME(?)",[$condition['pc_end_time']]);

        //查询流水不需要包含打码量流水时
        if (isset($condition['without_withdraw']) && $condition['without_withdraw'] == true) {
            $query->whereRaw("(f.deal_money != 0 OR f.coupon_money != 0)");
        }

        //查询流水不需要包含修改可提余额的记录时
        if (isset($condition['without_free_money']) && $condition['without_free_money'] == true) {
            $query->where('f.deal_category','!=',4);
        }
        $result = $query->selectRaw('f.deal_type, sum(f.deal_money) as money')->groupBy('deal_type')
            ->get()->toArray();

        return $result;
    }
}