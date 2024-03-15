<?php
/**
 * Created by PhpStorm.
 * User: lzx
 * Date: 2017/11/2
 * Time: 15:08
 */

/**
 * Created by PhpStorm.
 * User: ben
 * Date: 2018/4/17
 * Time: 10:06
 */

use Logic\Admin\BaseController;
return new class() extends BaseController
{
    const TITLE       = '自开奖彩种';
    const DESCRIPTION = '';
    
    const QUERY       = [];
    
    const PARAMS      = [];
    const SCHEMAS     = [
        [
            'lottery_id'             => 'int #彩票ID',
            'lottery_name'             => 'string #彩种名称',
            'type'             => 'string #彩种简写昵称',
            'count'             => 'int #彩种开奖个数'
        ]
    ];

    public function run()
    {

        $data = DB::table('openprize_config as o')
                    ->leftJoin('lottery as l','o.lottery_id','=' ,'l.id' )
                    ->select(DB::raw('o.lottery_id,l.name as lottery_name,o.type,l.pid'))
                    ->whereRaw("find_in_set('enabled',l.state)")
                    ->orderBy('o.id')
                    ->get()->toArray();

        $data = array_map('get_object_vars',$data);
        if(is_array($data) && !empty($data)){
            foreach ($data as $k=>$v){
                if (in_array($v['pid'],[10,24])){
                    $data[$k]['count'] = 5;
                }else if(in_array($v['pid'],[1,5])){
                    $data[$k]['count'] = 3;
                }else{
                    $data[$k]['count'] = 10;
                }
            }
        }
        return $data;
    }
};