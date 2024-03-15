<?php

use Logic\Admin\BaseController;

return new class() extends BaseController
{
    const TITLE = '账号转移--渠道号查询';
    const DESCRIPTION = '渠道号查询';
    const QUERY = [
        'number' => 'string #渠道号'
    ];

    //前置方法
    protected $beforeActionList = [
        'verifyToken','authorize'
    ];

    public function run(){
        $params = $this->request->getParams();

        $query = DB::table('channel_management')->selectRaw('id,name,number');

        $query = isset($params['number']) && !empty($params['number']) ? $query->where('number', 'like', "%{$params['number']}%") : $query;

        $result = $query->orderByDesc('id')->limit(10)->get()->toArray();
        array_unshift($result, ['id' => 999999999, 'name' => '无渠道号']);
        return $result;
    }
};