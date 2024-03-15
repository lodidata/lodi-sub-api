<?php
/**
 * vegas2.0
 *
 * @auth *^-^*<dm>
 * @copyright XLZ CO.
 * @package
 * @date 2017/4/10 10:49
 */

use Logic\Admin\BaseController;
return new class() extends BaseController{
//    const STATE       = \API::DRAFT;
    const TITLE       = '获取服务器时区与时间';
    const DESCRIPTION = '';
    
    const QUERY       = [];
    
    const PARAMS      = [];
    const SCHEMAS     = [

    ];

    public function run()
    {
        $timezone = date_default_timezone_get();
        return [
            'zone'  => $timezone == 'Brazil/West' ? '美东时间' : $timezone,
            'time'  => date('Y-m-d H:i:s'),
            'p' => date('P')
        ];
    }
};
