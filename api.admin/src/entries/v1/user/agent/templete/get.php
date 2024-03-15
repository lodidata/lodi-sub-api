<?php

use Logic\Admin\BaseController;
use Logic\Define\CacheKey;
use Logic\Set\SystemConfig;

return new class() extends BaseController {
    const TITLE       = '获取代理申请模板设置';
    const QUERY       = [];
    const SCHEMAS     = [];

    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize',
    ];

    public $module = ['agent' => ''];
    public function run() {
        $res = SystemConfig::getModuleSystemConfig('agent');

        $return['desc'] = $res['agent_apply_desc'];

        $data = DB::table('agent_apply_question')->orderBy('sort','ASC')->get()->toArray();
        foreach ($data as $value) {
            $value->option = !empty($value->option) ? json_decode($value->option, true) : [];
        }
        $return['question'] = $data;

        return $return;
    }
};