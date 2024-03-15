<?php

use Logic\Admin\BaseController;

return new class() extends BaseController
{
    const STATE       = '';
    const TITLE       = '第三方支付列表接口';
    const DESCRIPTION = '获取第三方支付列表';
    
    const QUERY       = [
        'page'      => 'int(required)   #页码',
        'page_size' => 'int(required)    #每页大小',
        'pay_channel'   => 'string()   #渠道名称',
        'pay_scence'   => 'int() #支付类型',
        'status'    => 'enum[0,1]()   #状态，1 启用，0 停用',
          'sort'    => 'int()   #排序'
    ];
    
    const PARAMS      = [];
    const SCHEMAS     = [
        [
            'type'  => 'enum[rowset, row, dataset]',
            'size'  => 'unsigned',
            'total' => 'unsigned',
            'data'  => 'rows[id:int,app_id:string,channel_name:string,name:string,pay_scene:string,levels:string,type:string
                deposit_times:int, money_used:int, money_stop:int,money_day_stop:int,money_day_used:int,url_notify:string,url_return:string,
                created_uname:string,sort:int,status:set[enabled,default]]',
        ]
    ];
    //前置方法
    protected $beforeActionList = [
        'verifyToken','authorize'
    ];
    public function run()
    {

        $status = $this->request->getParam('status');
        $scence  = $this->request->getParam('pay_scence');
        $sort    = $this->request->getParam('sort');
        $page    = $this->request->getParam('page') ?? 1;
        $size    = $this->request->getParam('page_size') ?? 20;
        $params = [];
        !is_null($status) && $params['status']  = $status == 1 ? 'enabled' : 'disabled';
        !is_null($scence) && $params['type']    = $scence;

        $domains = explode('.', $_SERVER['HTTP_HOST']);
        $wwwdomin = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http').'://api-www.' . $domains[1].'.'.$domains[2];

//        $pay = new Logic\Recharge\Pay($this->ci);
//        $payList =  $pay->allPayConfig($params);
        $query = \DB::table('pay_config');
        isset($params['status'])  && $query->where('status', $params['status']);
        isset($params['id']) && $query->where('id', $params['id']);
        isset($params['type']) && $query->where('type', $params['type']);

        $total=$query->count();
        $payList = $query->orderBy('sort', 'ASC')->orderBy('id', 'ASC')->forPage($page,$size)->get()->toArray();


        foreach ($payList as &$value){
            $value=(array)$value;
            $value['app_id'] = $value['partner_id'];
            $value['channel'] = $value['name'];
            $value['pay_scene'] = $value['type'];
            $value['levels'] = 'all';
            $value['deposit_times'] = $value['updated'];
            $value['money_used'] = 0;
            $value['money_stop'] = 0;
            $value['url_notify'] = '';
            $value['url_return'] = '';
            $value['pay_callback_domain'] = $value['pay_callback_domain']?: $wwwdomin;
            $value['created_uname'] = '';
            $value['pay_id'] = $value['id'];
            unset($value['key'],$value['pub_key']);
        }
        $attr = array('number'=>$page,'size'=>$size,'tatal'=>$total);
        return $this->lang->set(0, [], $payList, $attr);
    }

};
