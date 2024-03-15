<?php
namespace Model;
use DB;
class FundsWithdraw extends \Illuminate\Database\Eloquent\Model {

    protected $table = 'funds_withdraw';

    public $timestamps = false;

    protected $fillable = [
                              'id',
                              'user_id',
                              'user_type',
                              'type',
                              'trade_no',
                              'money',
                              'coupon_money',
                              'withdraw_bet',
                              'valid_bet',
                              'residue_bet',
                              'fee',
                              'admin_fee',
                              'previous_time',
                              'confirm_time',
                              'receive_bank_account_id',
                              'bank_id',
                              'receive_bank_info',
                              'today_money',
                              'today_times',
                              'ip',
                              'state',
                              'origin',
                              'memo',
                              'status',
                              'created_uid',
                              'process_uid',
                              'marks',
                        ];

    /**
     * 查询记录
     * @param  [type] $condition [description]
     * @param  [type] $page      [description]
     * @param  [type] $pageSize  [description]
     * @return [type]            [description]
     */
    public static function getRecords($params, $condition, $page = 1, $pageSize = 20) {
        $table = DB::table('funds_withdraw');        
        isset($condition['user_name']) && $table = $table->where('user.name', 'like', $condition['user_name'].'%');
        isset($condition['trade_no']) && $table = $table->where('funds_withdraw.trade_no', 'like', $condition['trade_no']);
        isset($condition['user_type']) && $table = $table->where('funds_withdraw.trade_no', $condition['user_type']);
        isset($condition['ranting']) && $table = $table->where('user.ranting', $condition['ranting']);
//        isset($condition['date_from']) && isset($condition['date_to']) && $table = $table->whereBetween(DB::raw('DATE(funds_withdraw.created)'), [$condition['date_from'], $condition['date_to']]);
        isset($condition['date_from']) && isset($condition['date_to']) && $table = $table->whereBetween('funds_withdraw.created', [$condition['date_from'], $condition['date_to'].' 23:59:59']);
        isset($condition['money_from']) && isset($condition['money_to']) && $table = $table->whereBetween('funds_withdraw.money', [$condition['money_from'], $condition['money_to']]);
        isset($condition['status']) && $table = $table->where('funds_withdraw.status', $condition['status']);
        isset($condition['id']) && $table = $table->where('funds_withdraw.id', $condition['id']);
        isset($condition['user_id']) && $table = $table->where('funds_withdraw.user_id', $condition['user_id']);
        isset($condition['confirm_time']) && $table = $table->where('funds_withdraw.confirm_time', $condition['confirm_time']);
        isset($condition['user_type']) && $table = $table->where('funds_withdraw.user_type', $condition['user_type']);
        isset($condition['status']) && $table = $table->where('funds_withdraw.status', $condition['status']);
        isset($condition['admin_user']) && $table = $table->where('admin_user.username', $condition['admin_user']);

        $select = [
            'coupon_money' => 'funds_withdraw.coupon_money',
            'withdraw_bet' => 'funds_withdraw.withdraw_bet',
            'valid_bet' => 'funds_withdraw.valid_bet',
            'id' => 'funds_withdraw.id',
            'user_name' => 'user.name.user_name',
            'user_tags' => 'user.tags.user_tags',
            'agent_id' => 'user.agent_id.agent_id',
            'user_id' => 'funds_withdraw.user_id',
            'ranting' => 'user.ranting',
            // 'agent_name',
            'trade_no' => 'funds_withdraw.trade_no',
            'money' => 'funds_withdraw.money',
            'fee' => 'funds_withdraw.fee',
            'admin_fee' => 'funds_withdraw.admin_fee',
            'apply_time' => DB::raw('funds_withdraw.created as apply_time'),
            'receive_bank_info' => 'funds_withdraw.receive_bank_info',
            'ip' => 'funds_withdraw.ip',
            'state' => 'funds_withdraw.state',
            'status' => 'funds_withdraw.status',
            'confirm_time' => 'funds_withdraw.confirm_time', 
            'previous_time' => 'funds_withdraw.previous_time',
            'process_uname' => DB::raw('admin.username as process_uname'),
            'today_times' => 'funds_withdraw.today_times', 
            'memo' => 'funds_withdraw.memo',
            'process_uid' => 'funds_withdraw.process_uid',
            'admin_user' => DB::raw('admin.username as admin_user'),
            'marks' => 'funds_withdraw.marks',
        ];

        $select = DB::mergeColumns($select, $params);
        $table = $table->select(array_values($select));

        if ($params == '*' || empty($params) || isset($params['process_uname']) || isset($params['admin_user']) || isset($condition['admin_user'])) {
            $table = $table->leftjoin('admin_user');
        }
        
        if ($params == '*' || empty($params) || isset($params['ranting']) || isset($params['user_name']) || isset($condition['user_name']) || isset($condition['ranting'])) {
            $table = $table->leftjoin('user');
        }

        $table = isset($condition['order']) ? $table->ordeBy('apply_time', 'desc')->orderBy('user_id') : $table->ordeBy($table['order'], $table['orderBy']);
        return $table->paginate($pageSize, ['*'], 'page', $page);
    }
}

