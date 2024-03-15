<?php

use Logic\Admin\BaseController;
return new class() extends BaseController {
    const TITLE = "银行帐号列表接口";
    const DESCRIPTION = "获取银行帐户信息列表";

    const QUERY = [
        "name" => "string() #收款户名",
        "bank_id" => "int #开户银行id",
        "level" => "int #用户层级",
        "status" => "enum[enabled,disabled] # 状态，enabled 启用，disabled 禁用",
        "page" => "int()   #页码",
        "page_size" => "int()    #每页大小"
    ];
    const SCHEMAS = [
        [
            "id" => "帐户ID",
            "bank_name" => "银行/支付名称",
            "address" => "开启行",
            "accountname" => "string #户名",
            "card" => "帐号",
            "usage" => "使用层级",
            "limit_once_min" => "单笔最低",
            "limit_once_max" => "单笔最高",
            "today" => "今日累计",
            "limit_day_max" => "每日最大存款",
            "total" => "累计存款",
            "limit_max" => "总存款限制",
            "qrcode" => "二维码",
            "created" => "创建时间",
            "created_uname" => "创建人",
            "sort" => "排序",
            "state" => "集合信息,online:线上, default:默认, enabled:启用",
            "levels" => "array #层级"
        ]
    ];

    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize'
    ];

    public function run()
    {
        $page = $this->request->getParam('page') ?? 1;
        $size= $this->request->getParam('page_size') ?? 20;
        $type = $this->request->getParam('type');
        $bank_id = $this->request->getParam('bank_id');
        $name = $this->request->getParam('name');
        $status = $this->request->getParam('state');

        $accounts = \DB::connection('slave')->table('bank_account as a')
            ->leftJoin('bank','a.bank_id','=','bank.id')
            ->leftJoin('admin_user as admin','a.creater','=','admin.id')
            ->whereRaw('!FIND_IN_SET("deleted", state)');
        $type && $accounts->where('a.type', '=', $type);
        $bank_id && $accounts->where('a.bank_id', '=', $bank_id);
        $name && $accounts->where('a.name', '=', $name);
        $status && $accounts->where('a.state', '=', $status);
        $accounts->select(['a.id','a.name','bank.name as bank_name','bank.id as bank_id',
            'a.address','a.card','a.created','admin.username as created_uname','a.limit_max',
            'a.limit_once_min','a.limit_once_max','a.limit_day_max','a.sort',
            'a.qrcode','a.state','a.usage','a.total','a.today','a.type','a.comment','bank.code as bank_code'
        ]);
        $attributes['total'] = $accounts->count();
        $attributes['number'] = $page;
        $attributes['size'] = $size;
        $data = $accounts->orderBy('sort')->forPage($page,$size)->get()->toArray();
        if(!empty($data)){
            foreach ($data as &$val) {
                $val->bank_name = $this->lang->text($val->bank_code);
                $levels = \DB::connection('slave')->table('level_bank_account')->where('bank_account_id', $val->id)->pluck('level_id');
                $level_names = \Model\UserLevel::whereIn('id', $levels)->orderBy('id')->pluck('name');
                $val->levels = is_array($level_names) ? implode(',', $level_names) : implode(',', $level_names->toArray());
                $val->level = $levels->toArray();
            }
        }
        $data = \Utils\Utils::RSAPatch($data);
        return  $this->lang->set(0,[],$data,$attributes);
    }
};
