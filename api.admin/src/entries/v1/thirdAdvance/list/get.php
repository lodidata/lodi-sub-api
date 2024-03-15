<?php

use Logic\Admin\BaseController;

return new class() extends BaseController {
    const TITLE       = '第三方代付信息列表';
    const DESCRIPTION = '第三方代付';

    const QUERY       = [

    ];

    const PARAMS      = [];
    const SCHEMAS     = [
        [
            'id'   => 'int #ID',
            'name' => 'string #代付（显示）名称',
            'code' => "string #代码名称唯一",
            'balance' => 'int #第三方余额，单位：元',
            'sort'  => 'int #排序 从大到小',
            'status' => 'string #状态，是否启用 1启用，0停用',
            'partner_id' => 'string #商户号',
            'request_url' => 'string #接口地址',
            'pay_callback_domain' => 'string #代付回调域名 默认为https://api-admin.XXX.com',
        ],
    ];
    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize',
    ];

    public function run() {
        $status = $this->request->getParam('status');
        $code = $this->request->getParam('code');
        $page    = $this->request->getParam('page', 1);
        $page_size    = $this->request->getParam('page_size', 20);

        $query = \DB::table('transfer_config');
        if(!is_null($status)){
            $status = $status == 1 ? 'enabled' : 'default';
            $query->where('status', $status);
        }
        !is_null($code) && $query->where('code', $code);

        $total = $query->count();

        $query->forPage($page, $page_size);
        $list = $query->orderBy('sort', 'ASC')
            ->orderBy('id', 'ASC')
            ->get(['id','name','code','balance','sort','status','partner_id','request_url','pay_callback_domain'])
            ->toArray();

        $domains = explode('.', $_SERVER['HTTP_HOST']);
        $adminDomin = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http').'://api-admin.' . $domains[1].'.'.$domains[2];

        foreach($list as &$val){
            $val = (array)$val;
            $val['status'] = $val['status']=='enabled' ? 1 : 0;
            $val['balance'] = bcdiv($val['balance'], 100, 2);
            $val['pay_callback_domain'] = $val['pay_callback_domain']?: $adminDomin;
        }
        unset($val);
        $attributes['total'] = $total;
        $attributes['number'] = $page;
        $attributes['size'] = $page_size;

        return $this->lang->set(0, [], $list, $attributes);
    }

};
