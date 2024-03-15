<?php
/**
 * @author Taylor 2019-01-11
 */
use Logic\Admin\BaseController;
return new class() extends BaseController{
    const TITLE       = '会员层级查询';
    
    const PARAMS      = [
        'name'=> 'string() #层级名称',
        'uid'=> 'int() #用户id',
        'page'=> 'int #第几页',
        'page_size'=> 'int #每页多少条'
    ];
    const SCHEMAS     = [
        [
            'name' => 'string #等级名称',
            'deposit_money' => 'int #最低充值金额',
            'online_dml_percent' => 'float #线上充值打码量',
            'offline_dml_percent' => 'float #线下充值打码量',
            'level' => 'int #数字等级',
            'icon' => 'string #等级图标URL地址',
            'lottery_money' => 'int #最低投注量',
            'user_count' => 'int #该层级对应的会员人数',
            'upgrade_dml_percent' => 'float #提现打码量',
            'draw_count' => 'int #活动免费抽奖次数',
            'promote_handsel' => 'int #晋升彩金',
            'transfer_handsel' => 'int #转卡彩金',
            'monthly_money' => 'int #月俸禄彩金，分为单位',
            'monthly_percent' => 'int #月俸禄条件百分比',
            'comment' => 'string #备注',
            'created' => 'datetime #创建时间，eg:2017-07-26 21:34:21',
            'updated' => 'datetime #更新时间，eg:2017-08-22 14:43:13'
        ],
    ];

    //前置方法
    protected $beforeActionList = [
        'verifyToken', 'authorize'
    ];

    public function run()
    {
        $params = $this->request->getParams();
        $query = DB::table('user_level');
        if (isset($params['uid']) && !empty($params['uid'])) {//查询单个用户的层级
            $rantingId = DB::table('user')->where('id', $params['uid'])->value('ranting');
            $query = $query->where('level', $rantingId);
        }
        if (isset($params['name'])&&!empty($params['name'])) {
            $query = $query->where('name', $params['name']);
        }

        $res = $query->get()->toArray();
        if(!$res){
            return [];
        }
        $res =  array_map('get_object_vars', $res);
        $attributes['total'] = $query->count();
        return $this->lang->set(0, [], $res, $attributes);
    }

};
