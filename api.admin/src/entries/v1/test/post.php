<?php
/**
 * User: nk
 * Date: 2019-01-04
 * Time: 14:24
 * Des :
 */

use Logic\Admin\BaseController;
use Model\Room;
use Model\Admin\LotteryPlayOdds;
use Model\Admin\Lottery;
use Model\Admin\Hall;
use Utils\Utils;

return new class() extends BaseController {
    const TITLE = '测试';
    const DESCRIPTION = '';
    
    const QUERY = [];
    
    const PARAMS = [];
    const SCHEMAS = [
    ];


    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize'
    ];

    public function run()
    {
        die;
    }
};
