<?php

use Utils\Www\Action;
use Logic\GameApi\Format\OrderRecordCommon;
use Model\GameMenu;

return new class extends Action {
    const TOKEN = true;
    const TITLE = '团队投注记录列表';
    const DESCRIPTION = "由于投注游戏不同，返回结果详细字段不同，需要根据实际情况处理  \r\n返回attributes[number =>'第几页', 'size' => '记录条数'， total=>'总记录数']";
    const TAGS = "代理返佣";
    const QUERY = [
        'type_name'     => "string() #游戏类型 默认为第一个分类",
        'category'      => "string() #投注分类值，多个分类用|分隔，与key搭配使用",
        'key'           => "string() #投注分类键名，多个分类用|分隔，与category搭配使用",
        "start_time"    => "date() #投注开始日期 2021-08-12",
        "end_time"      => "date() #投注结束日期 2021-08-25",
        'page'          => "int(,1) #第几页 默认为第1页",
        "page_size"     => "int(,20) #分页显示记录数 默认20条记录"
    ];
    const SCHEMAS = [
        [
            "id"            => "int() #ID",
            'order_number'  => "string() #订单号",
            "game_name"     => "string() #游戏名称",
            "user_name"     => "string() #用户名",
            "sub_game_name"  => "string() #子订单号",
            "play_name"     => "string() #玩法名",
            "profit"        => "string() #输赢(真实金额 不用除100)",
            "pay_money"     => "int() #投注金额 (真实金额 不用除100)",
            "type_name"     => "string() #类型名称",
            "create_time"   => "dateTime() #创建时间 2021-08-25 12:21:33",
            "state"         => "enum[created,winning,close]() #状态 created:待开奖,winning:已中奖,close:未中奖",
            "mode"          => "string() #模式 第三方",
        ]
    ];


    public function run() {
        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }
        $type       = $this->request->getQueryParam('type_name');
        $page       = (int)$this->request->getQueryParam('page', 1);
        $pageSize   = (int)$this->request->getQueryParam('page_size', 20);
        $category   = $this->request->getParam('category');
        $key        = $this->request->getParam('key');
        $start_time = $this->request->getParam('start_time');
        $end_time   = $this->request->getParam('end_time');

        if($end_time){
            $end_time = $end_time.' 23:59:59';
        }
        // 默认处理 type
        if (!isset($type)){
            $childList = GameMenu::getMenuAllList();
            if (count($childList) && isset($childList[0]['childrens'])){
                $type = $childList[0]['childrens'][0]['type'];
            }
        }
        $user_id = $this->auth->getUserId();
        if($this->auth->getTrialStatus()){
            // 试玩 后面再补这个逻辑
            return [];
        }

        $ids = \DB::table('user_agent')
            ->where('user_id', '!=', $user_id)
            ->where('uid_agent', '=', $user_id)
            ->pluck('user_id')
            ->toArray();
        if(!$ids) return [];
//        array_push($ids,$user_id);
        // 加入过滤
        $condition = [
            'category'   => $category,
            'key'        => $key,
            'start_time' => $start_time,
            'end_time'   => $end_time,
            // 代理数据
            'user_agent_ids' => $ids
        ];
        // 转换 筛选 为了兼容 更多第三方
        $condition        = OrderRecordCommon::filter($condition);
        list($re, $total) = OrderRecordCommon::records($type, $condition, $page, $pageSize);
        return $this->lang->set(
            0, [], $re, [
                'number' => $page,
                'size' => $pageSize,
                'total' => $total,
            ]
        );
    }

};