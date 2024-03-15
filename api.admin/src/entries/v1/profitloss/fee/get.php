<?php

use \Logic\Admin\BaseController;

return new class() extends BaseController {

    const TITLE = '盈亏返佣 盈亏成本列表';
    const DESCRIPTION = '';

    const QUERY = [
        'name'          => 'string() #项目名',
        'type'          => 'int() #占比类型 1:游戏盈亏(投注-派彩),2:充值,3:取款，4:营收, 5:平台彩金, 6:平台服务(人工扣款)',
        'settle_status' => 'int() #参与结算 1:是,2:否',
        'page'          => "int(,1) #第几页 默认为第1页",
        "page_size"     => "int(,10) #分页显示记录数 默认10条记录",

    ];

    const PARAMS = [];
    const SCHEMAS = [
        [
            'id'               => 'id',
            'name'             => '项目名',
            'type'             => '占比类型,1:游戏盈亏(投注-派彩),2:充值,3:取款，4:营收, 5:平台彩金, 6:平台服务(人工扣款)',
            'proportion_value' => '占比值',
            'settle_status'    => '参与结算,1:是,2:否',
            'part_value'       => '参与比例值',
            'status'           => '状态,1:正常,2:停用',
        ],
    ];
    protected $beforeActionList = [
        'verifyToken','authorize'
    ];

    public function run() {
        $params = $this->request->getParams();

        $query = DB::table("agent_loseearn_fee");

        $query = isset($params['name']) && !empty($params['name']) ? $query->where('name', $params['name']) : $query;
        $query = isset($params['type']) && !empty($params['type']) ? $query->where('type', $params['type']) : $query;
        $query = isset($params['settle_status']) && !empty($params['settle_status']) ? $query->where('settle_status', $params['settle_status']) : $query;

        $attributes['total'] = $query->count();

        $res = $query->forPage($params['page'], $params['page_size'])->get()->toArray();

        if(!$res) {
            return [];
        }

        $attributes['number'] = $params['page'];

        return $this->lang->set(0, [], $res, $attributes);
    }

};