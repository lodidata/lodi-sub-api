<?php

use Logic\Admin\BaseController;
use Illuminate\Database\Capsule\Manager as DB;

return new class() extends BaseController {
    const TITLE = '获取直推奖励配置';
    const QUERY = [];
    const SCHEMAS = [];

    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize',
    ];

    public $module = ['direct' => ''];

    public function run()
    {
        $res['direct'] = \Logic\Set\SystemConfig::getModuleSystemConfig('direct');

        // 直推返水奖励
        $res['direct_bkge'] = DB::table('direct_bkge')
            ->select(['id', 'serial_no', 'register_count', 'recharge_count', 'bkge_increase', 'bkge_increase_week', 'bkge_increase_month'])
            ->orderBy('serial_no')
            ->get()
            ->toArray();

        // 直推图片管理
        $res['direct_imgs'] = DB::table('direct_imgs')
            ->select(['id', 'type', 'name', 'desc', 'img', 'reward', 'status'])
            ->orderBy('id')
            ->get()
            ->toArray();
        
        foreach($res['direct_imgs'] as $key=>$value){
            $res['direct_imgs'][$key]->img = showImageUrl($value->img);
        }

        return $res;
    }
};
