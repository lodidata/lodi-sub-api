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
    const TITLE = '代付通道';
    const DESCRIPTION = '';
    
    const QUERY = [
    ];
    
    const PARAMS = [];
    const SCHEMAS = [
        [
            "id" => "id",
            "name" => "代付名"
        ]
    ];

    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize'
    ];
    public function run()
    {

        $res=DB::table('transfer_config')->get(['id','name'])->toArray();
        return $res;
    }
};
