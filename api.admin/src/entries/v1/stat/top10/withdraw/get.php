<?php
/**
 * vegas2.0
 *
 * @auth *^-^*<dm>
 * @copyright XLZ CO.
 * @package
 * @date 2017/4/10 14:04
 */
use Logic\Admin\BaseController;
return new class() extends BaseController
{
//    const STATE       = \API::DRAFT;
    const TITLE       = '今日取款金额前10';
    const DESCRIPTION = '';
    
    const QUERY       = [
        'days'=>'integer(require) #天数 默认7'
    ];
    
    const PARAMS      = [];
    const SCHEMAS     = [
        [
            'id'=>'用户id',
            'username'=>'用户名',
            'level'=>'层级名称',
            'amount'=>'取款金额',
            'sort'=>'排名',
        ],
    ];

    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize'
    ];

    public function run()
    {

        $rs = $this->player_withdraw();
        if(!$rs)
            return [];
        $rs = array_map('get_object_vars',$rs);
        $arr=[];
        foreach ($rs as $k => &$val) {
            $arr[$k]['sort']=$k + 1;
            $arr[$k]['level']=$val['name1'];
            $arr[$k]['username']=$val['name'];
            $arr[$k]['amount']=intval($val['get_money']);

        }

        return $arr??[];
    }

    /*
   *   今日取款金额前十名
   *   auth: hu
   * */
    public function player_withdraw( )
    {

        $info = DB::table('user as a')
                    ->join('funds_deal_log as b','a.id','=','b.user_id')
                    ->join('user_level as c','a.ranting','=','c.level')
                    ->selectRaw('b.id,a.`name` ,c.`name` name1,sum(ifnull(deal_money, 0))  as get_money')
                    ->whereRaw("b.deal_type in (201, 204) AND b.created>=CURDATE() AND a.tags not in (4,7)")
                    ->groupBy('a.id')
                    ->orderBy('get_money','DESC')
                    ->limit(10)
                    ->get()
                    ->toArray();
        return $info;
    }
};
