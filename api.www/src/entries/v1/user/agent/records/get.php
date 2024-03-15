<?php

use Utils\Www\Action;

return new class extends Action
{
    const HIDDEN = true;

    const TITLE = '团队投注记录详情';
    const TAGS = "代理返佣";
    protected $origins = [
        1 => 'PC',
        2 => 'H5',
        3 => 'APP',
    ];

    public function run() {
        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }
        $lottery_id = $this->request->getParam('lottery_id');
        $lottery_number = $this->request->getParam('lottery_number');
        $state = $this->request->getParam('state');
        $user_name = $this->request->getParam('user_name');
        $stime = $this->request->getParam('start_time');
        $etime = $this->request->getParam('end_time');
        $page = $this->request->getParam('page', 1);
        $size = $this->request->getParam('page_size', 20);
        $uid = $this->auth->getUserId();

        $ids = \DB::table('user_agent')
                  ->where('user_id', '!=', $uid)
                  ->where('uid_agent', '=', $uid)
                  ->pluck('user_id')
                  ->toArray();

        $query = DB::table('lottery_order as order')
                   ->leftjoin('lottery', 'lottery.id', '=', 'order.lottery_id')
                   ->leftjoin('send_prize', function ($q) {
                       $q->on('send_prize.order_number', '=', 'order.order_number')
                         ->on('send_prize.user_id', '=', 'order.user_id');
                   })
                   ->whereIn('order.user_id', $ids);

        $lottery_id && $query->where('lottery.id', '=', $lottery_id);

        switch ($state) {
            case 'winning' :
                $query->whereRaw("FIND_IN_SET('winning',order.state)");
                break;
            case 'lose' :
                $query->whereRaw("!FIND_IN_SET('winning',order.state)");
                $query->whereRaw("FIND_IN_SET('open',order.state)");
                break;
            case 'created' :
                $query->whereRaw("!FIND_IN_SET('open',order.state)");
                break;
        }

        $user_name && $query->where('order.user_name', '=', $user_name);
        $lottery_number && $query->where('order.lottery_number', '=', $lottery_number);
        $stime && $query->where('order.created', '>=', $stime . ' 00:00:00');
        $etime && $query->where('order.created', '<=', $etime . ' 59:59:59');
        $total = clone $query;
        $attributes['total'] = $total->count();
        $attributes['size'] = $size;
        $attributes['number'] = $page;

        $data = $query->forPage($page, $size)
                      ->orderby('id', 'desc')
                      ->get([
                          'order.id',
                          'lottery.pid',
                          'order.play_id',
                          'order.one_money',
                          'order.user_name',
                          'order.order_number',
                          'order.times',
                          'order.room_id',
                          'order.hall_id',
                          'order.origin',
                          'lottery.name as lottery_name',
                          'lottery.id as lottery_id',
                          'lottery.open_img',
                          'order.lottery_number',
                          'order.play_group',
                          'order.play_name',
                          'order.play_number',
                          'order.pay_money',
                          'order.state',
                          'order.odds',
                          'order.bet_num',
                          'order.win_bet_count',
                          'send_prize.money as send_money',
                          'order.created',
                          DB::raw('(SELECT period_code FROM lottery_info WHERE lottery_number = order.lottery_number AND lottery_type = order.lottery_id) AS period_code'),
                      ])
                      ->toArray();

        $data = $this->pc($data, $this->ci);

        return $this->lang->set(0, [], $data, $attributes);
    }

    protected function pc($data, $ci) {
        $result = [];
        $logic = new \LotteryPlay\Logic();
        $lotterCommon = new  \Logic\Lottery\Common();
        foreach ($data ?? [] as $k => &$v) {
            $v = (array)$v;
            $result[$k] = isset($result[$k]) ? $result[$k] : [];
            $result[$k]['id'] = $v['id'];
            $result[$k]['user_name'] = $v['user_name'];
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
            $result[$k]['open_img'] = showImageUrl($v['open_img']);
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

            $v['play_numbers'] = $logic->getPretty($v['pid'], $v['play_id'], $v['play_number']);
            $v['period_code'] = \DB::table('lottery_info')
                                   ->where('lottery_number', '=', $v['lottery_number'])
                                   ->where('lottery_type', '=', $v['lottery_id'])
                                   ->value('period_code');

            if ($v['period_code'] && $v['lottery_id'] == 52) {
                $period_code_arr = explode(',', $v['period_code']);
                $sx = [];
                foreach ($period_code_arr as $period_code) {
                    $sx[] = $lotterCommon->getSx($period_code);
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
            $result[$k]['send_money'] = $v['send_money'] ?? 0;
            $result[$k]['created'] = $v['created'];
        }

        return $result;
    }
};