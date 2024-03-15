<?php
/**
 * vegas2.0
 *
 * @auth *^-^*<dm>
 * @copyright XLZ CO.
 * @package
 * @date 2017/4/10 14:00
 */
use Logic\Admin\BaseController;
return new class() extends BaseController
{
//    const STATE       = \API::DRAFT;
    const TITLE       = '今日存款前10';
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
            'amount'=>'存款',
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
        $rs     = $this->player_deposit();
        if(!$rs)
            return [];
        $rs = array_map('get_object_vars',$rs);
        foreach ($rs as $k => &$val) {
            $arr[$k]['sort']=$k + 1;
            $arr[$k]['level']=$val['name1'];
            $arr[$k]['username']=$val['name'];
            $arr[$k]['amount']=intval($val['deposit_money']);

        }

        return $arr;
    }

    /*
   *   今日存款金额前十名
   *   auth: hu
   * */
    public function player_deposit( )
    {

        $info = DB::table('user as a')
            ->join('funds_deposit as b','a.id','=','b.user_id')
            ->join('user_level as c','a.ranting','=','c.level')
            ->selectRaw('a.id,a.`name`,c.`name` name1,SUM(b.money) deposit_money')
            ->whereRaw("b.`created` >= CURDATE() AND a.tags not in (4,7)  AND b.`status`='paid' and b.money>0")
            ->groupBy('a.id')
            ->orderBy('deposit_money','DESC')
            ->limit(10)
            ->get()
            ->toArray();
//        print_r($info);exit;
        return $info;
    }
};
