<?php

use Utils\Www\Action;
use Logic\Lottery\InsertNumber;

return new class extends Action
{
    const TOKEN = true;
    const TITLE = '获取指定彩票猜奖号列表';
    const DESCRIPTION = '获取指定彩票猜奖号列表 \r\n 返回attributes[number =>\'第几页\', \'size\' => \'记录条数\'， total=>\'总记录数\']';
    const TAGS = "彩票";
    const QUERY = [
        'lottery_id'     => 'int(required) #彩票id',
        "lottery_number" => "string(required) #彩票期号",
        'page'           => "int(,1) #第几页 默认为第1页",
        "page_size"      => "int(,20) #分页显示记录数 默认20条记录",
    ];
    const SCHEMAS = [
        [
            'user_account'  => 'string(required) #用户名',
            'number'        => 'string(required) #猜奖号',
            'time'          => 'datetime(required) #输入时间 11/16/2021 16:05:17',
        ]
    ];


    public function run() {
        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }
        $lottery_id = $this->request->getQueryParam('lottery_id');
        $page = (int)$this->request->getQueryParam('page', 1);
        $pageSize = (int)$this->request->getQueryParam('page_size', 20);
        $lottery_number = $this->request->getParam('lottery_number');
        if(!is_numeric($lottery_id) || empty($lottery_number)){
            return $this->lang->set(10);
        }

        list($data, $total, $sum) = InsertNumber::getLotteryNumberList($lottery_id, $lottery_number, $page, $pageSize);
        $attributes = [
            'number' => $page,
            'size' => $pageSize,
            'total' => $total,
            'sum'   => $sum
        ];
        return $this->lang->set(
            0, [], $data, $attributes
        );
    }
};
