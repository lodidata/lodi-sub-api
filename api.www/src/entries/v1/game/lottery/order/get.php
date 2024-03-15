<?php

use Utils\Www\Action;

return new class extends Action
{
    const TOKEN = true;
    const TITLE = "注单查询--彩票标准/快速";
    const DESCRIPTION = "map #\n            \"id\": \"45\",\n            \"created\": \"2018-02-06 15:41:10\",\n            \"order_number\": \"2018020603411045879\",\n            \"lottery_number\": \"871175\",\n            \"odds\": {\n                \"单\": \"2.00\",\n                \"双\": \"2.00\",\n                \"大\": \"2.00\",\n                \"小\": \"2.00\",\n                \"大单\": \"4.33\",\n                \"大双\": \"3.72\",\n                \"小单\": \"3.72\",\n                \"小双\": \"4.33\",\n                \"极大\": \"17.86\",\n                \"极小\": \"17.86\"\n            },\n            \"lottery_name\": \"北京幸运28\",\n            \"play_group\": \"大小单双\",\n            \"play_name\": \"大小单双\",\n            \"pay_money\": \"1000\",\n            \"bet_num\": \"10\",\n            \"play_number\": \"大|小|单|双|小单|小双|大单|大双|极小|极大\",\n            \"send_money\": null,\n            \"state\": \"\",\n        ";
    const TAGS = "彩票";
    const QUERY = [
        "lottery_id"     => "int #彩种id，见彩种列表",
        "state"          => "string #状态，",
        "lottery_number" => "string #期号，",
        "start_time"     => "datetime #查询时间，开始",
        "end_time"       => "datetime #查询时间，结束",
        'page'  => "int(,1) #第几页 默认为第1页",
        "page_size" => "int(,30) #分页显示记录数 默认30条记录"
    ];
    const SCHEMAS = [
    ];

    protected $origins = [1 => 'PC', 2 => 'H5', 3 => '移动端'];

    public function run()
    {
        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }
        $lotteryId = (int)$this->request->getQueryParam('lottery_id');
        $state = $this->request->getQueryParam('state');
        $lotteryNumber = $this->request->getQueryParam('lottery_number');
        $startDatetime = $this->request->getQueryParam('start_time');
        $endDatetime = $this->request->getQueryParam('end_time');
        $page = (int)$this->request->getQueryParam('page', 1);
        $pageSize = (int)$this->request->getQueryParam('page_size', 30);
        $origin = (int)$this->request->getQueryParam('origin', 1);

        if (!empty($startDatetime) || !empty($endDatetime)) {
            $startDatetime = $startDatetime . ' 00:00:00';
            $endDatetime = $endDatetime . ' 59:59:59';
        }

        $condition = [
            'user_id'        => $this->auth->getUserId(),
            'lottery_id'     => $lotteryId,
            'state'          => $state,
            'start_time'     => $startDatetime,
            'end_time'       => $endDatetime,
            'lottery_number' => $lotteryNumber,
            'info'           => true,
        ];
        $condition = array_filter($condition);
        if ($this->auth->getTrialStatus()) {
            //获取试玩注单
            $data = \Model\LotteryTrialOrder::getTrialRecords($condition, $page, $pageSize);

            $result = $this->trialPc($data->toArray()['data'], $this->ci);

            return $this->lang->set(
                0, [], $result, ['number' => $page, 'size' => $pageSize, 'total' => $data->total()]
            );
        } else {
            $data = \Model\LotteryOrder::getRecords($condition, $page, $pageSize);
            $result = [];
            // if ($origin == 1) {
            //     $result = $this->pc($data->toArray()['data']);
            // } else {
            //     $result = $this->app($data->toArray()['data']);
            // }

            $result = $this->pc($data->toArray()['data'], $this->ci);
            //升级弹窗
            $user_login = new Logic\User\User($this->ci);
            $user = \DB::table('user')->where('id', $this->auth->getUserId())->first();
            $user_login->upgradeLevelMsg((array)$user);
            $upgrade = $user_login->upgradeLevelWindows($this->auth->getUserId());
            return $this->lang->set(
                0, [], $result, [
                     'number'  => $page,
                     'size'    => $pageSize,
                     'total'   => $data->total(),
                     'upgrade' => $upgrade,
                 ]
            );
        }

    }

    protected function pc($data, $ci)
    {
        $result = [];
        $logic = new \LotteryPlay\Logic();
        $lotterCommon = new  \Logic\Lottery\Common();

        foreach ($data ?? [] as $k => $v) {
            $v = (array)$v;
            $result[$k] = isset($result[$k]) ? $result[$k] : [];
            $result[$k]['id'] = $v['id'];
            $result[$k]['order_number'] = $v['order_number'];
            $result[$k]['lottery_id'] = $v['lottery_id'];
            $result[$k]['lottery_number'] = $v['lottery_number'];
            $result[$k]['lottery_name'] = $v['lottery_name'];
            $result[$k]['play_group'] = $v['play_group'];
            $result[$k]['play_number'] = $v['play_number'];
            $result[$k]['play_numbers'] = $logic->getPretty($v['pid'], $v['play_id'], $v['play_number']);
            $result[$k]['pay_money'] = $v['pay_money'];
            $result[$k]['one_money'] = $v['one_money'] * $v['times'];
            $result[$k]['bet_num'] = $v['bet_num'];
            $result[$k]['times'] = $v['times'];
            $result[$k]['open_img'] = $v['open_img'];
            $result[$k]['play_name'] = $v['play_name'];
            $result[$k]['odds'] = $v['odds'];
            $result[$k]['win_bet_count'] = $v['win_bet_count'];
            $result[$k]['play_id'] = $v['play_id'];
            $result[$k]['room_id'] = $v['room_id'];
            $result[$k]['hall_id'] = $v['hall_id'];
            $result[$k]['pid'] = $v['pid'] ?? $v['lottery_id'];
            $temp_odds = [];
            $temp_win_bet_count = [];
            $ii = 0;
            foreach (json_decode($v['odds'], true) ?? [] as $kk => $vv) {
                $temp_odds[$ii]['name'] = $kk;
                $temp_odds[$ii]['num'] = $vv;
                $ii++;
            }
            $result[$k]['odds_array'] = $temp_odds;
            $ii = 0;
            if ($v['win_bet_count']) {
                foreach (json_decode($v['win_bet_count'], true) as $kk => $vv) {
                    $temp_win_bet_count[$ii]['name'] = $kk;
                    $temp_win_bet_count[$ii]['num'] = $vv;
                    $ii++;
                }
            }
            $result[$k]['win_bet_count_array'] = $temp_win_bet_count;
            $result[$k]['origin'] = $this->origins[$v['origin']] ?? '未知';
            $result[$k]['hall_name'] = $v['hall_name'] ?? '未知';
            $result[$k]['room_name'] = $v['room_name'] ?? '';
            $result[$k]['period_code'] = $v['period_code'];
            if ($v['period_code'] && $v['lottery_id'] == 52) {
                $period_code_arr = explode(',', $v['period_code']);
                $sx = [];
                foreach ($period_code_arr as $period_code) {
                    $sx[] = $lotterCommon->getSx($period_code);
                }
                $result[$k]['sx'] = implode(',', $sx);

            } else {
                $result[$k]['sx'] = null;
            }

            if (strstr($v['state'], 'winning')) {
                $result[$k]['state_str'] = 'winning';
                $result[$k]['state'] = $ci->lang->text('state.winning');
            } else if (strstr($v['state'], 'open')) {
                $result[$k]['state_str'] = 'lose';
                $result[$k]['state'] = $ci->lang->text('state.close');
            } else if (strstr($v['state'], 'canceled')) {
                $result[$k]['state_str'] = 'canceled';
                $result[$k]['state'] = $ci->lang->text('state.close');
            } else if (strstr($v['state'], 'created')) {
                $result[$k]['state_str'] = 'created';
                $result[$k]['state'] = $ci->lang->text('state.created');
            } else if (strstr($v['state'], 'std')) {
                $result[$k]['mode'] = $ci->lang->text('state.std');
                $result[$k]['mode_str'] = 'std';
            } else if (strstr($v['state'], 'video')) {
                $result[$k]['mode'] = $ci->lang->text('state.video');
                $result[$k]['mode_str'] = 'video';
            } else if (strstr($v['state'], 'chat')) {
                $result[$k]['mode'] = $ci->lang->text('state.chat');
                $result[$k]['mode_str'] = 'chat';
            } else if (strstr($v['state'], 'fast')) {
                $result[$k]['mode'] = $ci->lang->text('state.fast');
                $result[$k]['mode_str'] = 'fast';
            } else {
                $result[$k]['mode'] = $ci->lang->text('Other');
                $result[$k]['mode_str'] = 'other';
            }

            if (strstr($v['state'], 'fast')) {
                $tmp = $ci->lang->text('state.fast');
            } else if (strstr($v['state'], 'std')) {
                $tmp = $ci->lang->text('state.std');
            } else {
                $tmp = '';
            }
            //模式那栏  4是PC  5是传统  PC和传统是统一的
            switch ($v['hall_level']) {
                case 4:
                case 5:
                    $result[$k]['mode_name'] = $ci->lang->text("Traditional model")."<{$tmp}>";
                    break;
                case 6:
                    $result[$k]['mode_name'] = $ci->lang->text("Live mode");
                    break;
                default:
                    $result[$k]['mode_name'] = $ci->lang->text("Room mode")."<{$v['hall_name']}>";
            }


            $result[$k]['send_money'] = $v['send_money'] ?? 0;
            $result[$k]['created'] = $v['created'];
        }
        return $result;
    }

    protected function app($data, $ci)
    {
        $result = [];
        foreach ($data ?? [] as $k => $v) {
            $v = (array)$v;
            $result[$k] = isset($result[$k]) ? $result[$k] : [];
            $result[$k]['id'] = $v['id'];
            $result[$k]['order_number'] = $v['order_number'];
            $result[$k]['lottery_number'] = $v['lottery_number'];
            $result[$k]['lottery_name'] = $v['lottery_name'];
            $result[$k]['room_name'] = $v['room_name'] ?? '';
            $result[$k]['play_name'] = $v['play_group'] . '>' . $v['play_name'];
            $result[$k]['pay_money'] = $v['pay_money'];
            if (strstr($v['state'], 'winning')) {
                $result[$k]['state_str'] = 'winning';
                $result[$k]['state'] = $ci->lang->text('state.winning');
            } else if (strstr($v['state'], 'open')) {
                $result[$k]['state_str'] = 'lose';
                $result[$k]['state'] = $ci->lang->text('state.close');
            } else if (strstr($v['state'], 'canceled')) {
                $result[$k]['state_str'] = 'canceled';
                $result[$k]['state'] = $ci->lang->text('state.close');
            } else if (strstr($v['state'], 'created')) {
                $result[$k]['state_str'] = 'created';
                $result[$k]['state'] = $ci->lang->text('state.created');
            }
        }
        return $result;
    }

    /**
     * 试玩注单
     */
    protected function trialPc($data, $ci)
    {
        $result = [];
        $logic = new \LotteryPlay\Logic();
        foreach ($data ?? [] as $k => $v) {
            $v = (array)$v;
            $result[$k] = isset($result[$k]) ? $result[$k] : [];
            $result[$k]['id'] = $v['id'];
            $result[$k]['order_number'] = $v['order_number'];
            $result[$k]['lottery_id'] = $v['lottery_id'];
            $result[$k]['lottery_number'] = $v['lottery_number'];
            $result[$k]['lottery_name'] = $v['lottery_name'];
            $result[$k]['play_group'] = $v['play_group'];
            $result[$k]['play_number'] = $v['play_number'];
            $result[$k]['play_numbers'] = $logic->getPretty($v['pid'], $v['play_id'], $v['play_number']);
            $result[$k]['pay_money'] = $v['pay_money'];
            $result[$k]['one_money'] = $v['one_money'] * $v['times'];
            $result[$k]['bet_num'] = $v['bet_num'];
            $result[$k]['times'] = $v['times'];
            $result[$k]['open_img'] = $v['open_img'];
            $result[$k]['play_name'] = $v['play_name'];
            $result[$k]['odds'] = $v['odds'];
            $result[$k]['win_bet_count'] = $v['win_bet_count'];
            $result[$k]['play_id'] = $v['play_id'];
            $result[$k]['pid'] = isset($v['pid']) ?? $v['lottery_id'];
            $temp_odds = [];
            $temp_win_bet_count = [];
            $ii = 0;
            foreach (json_decode($v['odds'], true) ?? [] as $kk => $vv) {
                $temp_odds[$ii]['name'] = $kk;
                $temp_odds[$ii]['num'] = $vv;
                $ii++;
            }
            $result[$k]['odds_array'] = $temp_odds;
            $ii = 0;
            if ($v['win_bet_count']) {
                foreach (json_decode($v['win_bet_count'], true) as $kk => $vv) {
                    $temp_win_bet_count[$ii]['name'] = $kk;
                    $temp_win_bet_count[$ii]['num'] = $vv;
                    $ii++;
                }
            }
            $result[$k]['win_bet_count_array'] = $temp_win_bet_count;
            $result[$k]['origin'] = $this->origins[$v['origin']] ?? $ci->lang->text('unknown');
            $result[$k]['hall_name'] = $v['hall_name'] ?? $ci->lang->text('unknown');
            $result[$k]['room_name'] = $v['room_name'] ?? '';
            $result[$k]['period_code'] = $v['period_code'];
            $result[$k]['pid'] = $v['pid'];
            if (strstr($v['state'], 'winning')) {
                $result[$k]['state_str'] = 'winning';
                $result[$k]['state'] = $ci->lang->text('state.winning');
            } else if (strstr($v['state'], 'open')) {
                $result[$k]['state_str'] = 'lose';
                $result[$k]['state'] = $ci->lang->text('state.close');
            } else if (strstr($v['state'], 'canceled')) {
                $result[$k]['state_str'] = 'canceled';
                $result[$k]['state'] = $ci->lang->text('state.close');
            } else if (strstr($v['state'], 'created')) {
                $result[$k]['state_str'] = 'created';
                $result[$k]['state'] = $ci->lang->text('state.created');
            } else if (strstr($v['state'], 'std')) {
                $result[$k]['mode'] = $ci->lang->text('state.std');
                $result[$k]['mode_str'] = 'std';
            } else if (strstr($v['state'], 'video')) {
                $result[$k]['mode'] = $ci->lang->text('state.video');
                $result[$k]['mode_str'] = 'video';
            } else if (strstr($v['state'], 'chat')) {
                $result[$k]['mode'] = $ci->lang->text('state.chat');
                $result[$k]['mode_str'] = 'chat';
            } else if (strstr($v['state'], 'fast')) {
                $result[$k]['mode'] = $ci->lang->text('state.fast');
                $result[$k]['mode_str'] = 'fast';
            } else {
                $result[$k]['mode'] = $ci->lang->text('Other');
                $result[$k]['mode_str'] = 'other';
            }

            if (strstr($v['state'], 'fast')) {
                $tmp = $ci->lang->text('state.fast');
            } else if (strstr($v['state'], 'std')) {
                $tmp = $ci->lang->text('state.std');
            } else {
                $tmp = '';
            }
            //模式那栏  4是PC  5是传统  PC和传统是统一的
            switch ($v['hall_level']) {
                case 4:
                case 5:
                    $result[$k]['mode_name'] = $ci->lang->text("Traditional model")."<{$tmp}>";
                    break;
                case 6:
                    $result[$k]['mode_name'] = $ci->lang->text("Live mode");
                    break;
                default:
                    $result[$k]['mode_name'] = $ci->lang->text("Room mode")."<{$v['hall_name']}>";
            }

            $result[$k]['send_money'] = $v['send_money'] ?? 0;
            $result[$k]['created'] = $v['created'];
        }

        return $result;
    }
};