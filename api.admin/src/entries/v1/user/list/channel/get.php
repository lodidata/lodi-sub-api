<?php
/**
 * vegas2.0
 *
 * @auth *^-^*<dm>
 * @copyright XLZ CO.
 * @package
 * @date 2017/4/5 14:53
 */

use Logic\Admin\BaseController;
use Logic\User\Agent as agentLgoic;
return new class() extends BaseController{
    const TITLE = '会员列表-渠道下拉';
    const DESCRIPTION = '会员列表-渠道下拉';
    
    const QUERY = [
    ];
    
    const PARAMS = [];
    const SCHEMAS = [
    ];
    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize'
    ];

    public function run() {
        $res=DB::table('channel_management')->get('number as channel_id')->toArray();


        return $res;
    }
};
