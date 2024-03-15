<?php
/**
 * vegas2.0
 *
 * @auth *^-^*<dm>
 * @copyright XLZ CO.
 * @package
 * @date 2017/4/12 21:14
 */

use Logic\Admin\BaseController;

return new  class() extends BaseController
{
//    const STATE       = \API::DRAFT;
    const TITLE = '余额统计';
    const DESCRIPTION = '';
    
    const QUERY = [
    ];
    
    const PARAMS = [];
    const SCHEMAS = [
        [
            "id" => "钱包id",
            "balance" => "钱包余额",
            "name" => "钱包名称"
        ]
    ];

    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize'
    ];
    public function run()
    {

        $parent = DB::table('funds')
            ->leftJoin('user','funds.id','=','user.wallet_id')
            ->whereNotIn('user.tags',[4,7])
            ->selectRaw("funds.id,sum(funds.balance) as balance,funds.name")
            ->get()->first();

        $datas[0] = (array)$parent;
        $datas[0]['game_type'] = "主钱包";

        $data = DB::table('funds_child')
            ->leftJoin('funds','funds.id','=','funds_child.pid')
            ->leftJoin('user','funds.id','=','user.wallet_id')
            ->whereNotIn('user.tags',[4,7])
            ->selectRaw("funds_child.id,sum(funds_child.balance) as balance,funds_child.game_type,funds_child.name")
            ->get()
            ->toArray();

        return array_merge($datas, $data);
    }
};
