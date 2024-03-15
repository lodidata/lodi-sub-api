<?php
/**
 * vegas2.0
 *
 * @auth *^-^*<dm>
 * @copyright XLZ CO.
 * @package
 * @date 2017/4/10 13:50
 */
use Logic\Admin\BaseController;
return new class() extends BaseController
{
//    const STATE       = \API::DRAFT;
    const TITLE       = '今日盈利前10排名';
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
            'amount'=>'盈利金额',
            'sort'=>'排名',
        ],
    ];

    public function run()
    {
        $rs = DB::table('user as a')
                    ->join('send_prize as b','a.id','=','b.user_id')
                    ->join('user_level as c','a.ranting','=','c.level')
                    ->selectRaw("a.id,a.`name`,c.`name` name1,SUM(b.lose_earn) as earn")
                    ->whereRaw("b.created>=CURDATE() AND a.tags NOT IN (4,7)")
                    ->groupBy(DB::raw("a.id HAVING `earn` > 0 "))
                    ->orderBy('earn' ,'DESC')
                    ->limit(10)
                    ->get()->toArray();
        if(!$rs){
            return [];
        }
        $rs = array_map('get_object_vars',$rs);
        $arr=[];
        foreach ($rs as $k => &$val) {
            $arr[$k]['sort']=$k + 1;
            $arr[$k]['level']=$val['name1'];
            $arr[$k]['username']=$val['name'];
            $arr[$k]['amount']=intval($val['earn']);
        }

        return $arr??[];
    }
};
