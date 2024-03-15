<?php

use Utils\Www\Action;

return new class() extends Action {
    const STATE = '';
    const TITLE = '返水历史记录列表';
    const DESCRIPTION = '';

    const QUERY = [];

    const PARAMS = [];
    const SCHEMAS = [];

    public $rebot_way = [
//        'loss',     //按当日亏损额回水
        'betting',  //按当日投注额回水
    ];

    public $rebot_way_type = [
        'percentage',   //返现百分比
//        'fixed',        //返现固定金额
    ];

    public function run()
    {
        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }

        $type = $this->request->getParam('type');
        $week = max((int)$this->request->getParam('week'), 1);
        $month = max((int)$this->request->getParam('month'), 1);
        $searchDay = $this->request->getParam('day', date('Y-m-d', strtotime('-1 day')));
        $redisKey = \Logic\Define\CacheKey::$perfix['rebet_history'] . implode('_', [
                $this->auth->getUserId(),
                $type,
                $week,
                $month,
                $searchDay,
            ]);
        if($type != 1 && $data = $this->redis->get($redisKey)) {
            $data = json_decode($data, true);
            return $this->lang->set(0, [], $data, null);
        }
        if (in_array($type, [2, 3])) {
            $query = DB::table('rebet_log as l')
                ->leftJoin('active_apply as a', 'a.id', '=', 'l.active_apply_id')
                ->leftJoin('active_backwater as b', 'b.batch_no', '=', 'a.batch_no')
                ->where('l.user_id', $this->auth->getUserId())
                ->where('b.type', $type)
                ->where('b.status', 2)
                ->orderBy('l.id', 'desc')
                ->addSelect([
                    'l.user_id',
                    'l.desc',
                    'batch_time',
                    'l.direct_rate',
                ]);
            $batchTime = [];
            if ($type == 2) {
//                for ($i = $week; $i > 0; $i--) {
                $weekDay = date('N') - 1;
                $beginDate = date('Y-m-d', time() - ($week * 7 + $weekDay) * 86400);
                $endDate = date('Y-m-d', time() - (($week - 1) * 7 + $weekDay + 1) * 86400);
                $batchTime[] = "{$beginDate}~{$endDate}";
//                }
            } else {
                $batchTimes = DB::table('active_apply as a')
                    ->leftJoin('active_backwater as b', 'a.batch_no', '=', 'b.batch_no')
                    ->where('b.type', $type)
                    ->where('b.status', 2)
                    ->max('b.batch_time');
                list($beginDate, $endDate) = explode('~', $batchTimes);
                $startDate = date('Y-m-01', strtotime('-2 month'));
                $batchTime[] = "{$beginDate}~{$endDate}";
                if (empty($batchTime) || strtotime($startDate) > strtotime($beginDate)) {
                    $formatFirstDate = strtotime(date('Y-m-01'));
//                for ($i = $month; $i > 0; $i--) {
                    $beginNum = -$month - 1;
                    $endNum = -$month;
                    $beginTime = strtotime("{$beginNum} month", $formatFirstDate);
                    $endTime = strtotime("{$endNum} month");
                    $beginDate = date('Y-m-28', $beginTime);
                    $endDate = date('Y-m-27', $endTime);
                    $batchTime[] = "{$beginDate}~{$endDate}";
//                }
                }
            }
            $query->whereIn('batch_time', $batchTime);
//            print_r($query->getBindings());
//            die($query->toSql());
            $data = $query->get()->toJson();
            $data = json_decode($data, true);
            $tmpData = [];
            foreach ($data as $item) {
                $descInfos = json_decode($item['desc'], true);
                foreach ($descInfos as $descInfo) {
                    $rebetConfigs = $descInfo['rebetConfigs'] ?? [];
                    $proportionValue = $this->getRebetActConfig($rebetConfigs, bcdiv($descInfo['allBetAmount'] ?? 0, 100, 2));
                    $gameId = 0;
                    !empty($item['game_id']) && $gameId = $item['game_id'];
                    !empty($descInfo['game_id']) && $gameId = $descInfo['game_id'];
                    $tmpItem = [
                        'day' => $item['batch_time'],
                        'rebet' => $descInfo['money'] ?? 0,
                        'bet_amount' => $descInfo['allBetAmount'] ?? 0,
                        'direct_rate' => $item['direct_rate'],
                        'proportion_value' => $proportionValue,
                        'game_id' => $gameId,
                    ];
                    empty($gameId) && !empty($descInfo['type']) && $tmpItem['game_type'] = $descInfo['type'];
                    $tmpData[] = $tmpItem;
                }
            }
            $data = $tmpData;
            unset($tmpData);
        } else {
            $day30 = date('Y-m-d', strtotime('-30 day'));
            $query = DB::table('rebet as r')
                ->leftJoin('active_backwater as b', 'b.batch_no', '=', 'r.batch_no')
                ->where('b.batch_time', '>=', $day30)
                ->where('r.user_id', $this->auth->getUserId())
                ->whereIn('r.status', [2, 3])
                ->orderBy('b.batch_time', 'desc')
                ->addSelect([
                    'b.batch_time as day',
                    'r.bet_amount',
                    'r.proportion_value',
                    'r.rebet',
                    'r.plat_id as game_id',
                ]);
            if (!empty($searchDay)) {
                $query->where('b.batch_time', $searchDay);
            }
//            print_r($query->getBindings());
//            die($query->toSql());
            $data = $query->get()->toJson();
            $data = json_decode($data, true);
        }
        $platIdArr = array_column($data, 'game_id', 'game_id');
        if ($platIdArr) {
            $gameMenus = DB::table('game_menu')
//                ->whereIn('id', $platIdArr)
                ->addSelect(['id', 'pid', 'name'])
                ->get()
                ->toJson();
            $gameMenus = json_decode($gameMenus, true);
            $gameMenus = array_column($gameMenus, null, 'id');
            $tmpData = [];
            foreach ($data as $item) {
                if(isset($gameMenus[$item['game_id']])) {
                    $id = $gameMenus[$item['game_id']]['id'];
                    if($gameMenus[$item['game_id']]['pid'] > 0) {
                        $id = $gameMenus[$item['game_id']]['pid'];
                    }
                    $item['game_type'] = $gameMenus[$id]['name'];
                    $tmpData[$id][] = $item;
                }
            }
            $data = [];
            foreach ($tmpData as $values) {
                $tmpDay = $values[0]['day'];
                $tmpRate = (($values[0]['direct_rate'] ?? 0) / 100);
                $rate = bcadd($values[0]['proportion_value'], bcmul($values[0]['proportion_value'], $tmpRate, 2), 2);
                $gameId = $values[0]['game_id'];
                $gameType = $values[0]['game_type'];
                $betAmount = 0;
                $rebet = 0;
                $gameIds = [];
                foreach ($values as $item) {
                    $item['rebet'] = bcadd($item['rebet'], bcmul($item['rebet'], $tmpRate, 2), 2);
                    $rebet = bcadd($rebet, $item['rebet'], 2);
                    $betAmount = bcadd($betAmount, $item['bet_amount'], 2);
                    $gameIds[] = $item['game_id'];
                }
                $data[] = [
                    'day' => $tmpDay,
                    'proportion_value' => $rate,
                    'game_id' => $gameId,
                    'game_type' => $gameType,
                    'bet_amount' => $betAmount,
                    'rebet' => $rebet,
                    'game_ids' => $gameIds,
                ];
            }
        }
        $tmpData = [];
        foreach ($data as $item) {
            $item['proportion_value'] = strval(floatval($item['proportion_value']) + ($item['direct_rate'] ?? 0));
            $tmpData[$item['day']][] = $item;
        }
        $data = [];
        foreach ($tmpData as $day => $tmpValues) {
            $rebet = 0;
            foreach ($tmpValues as $tmpKey => $item) {
                if ($type == 1) {
                    $item['bet_amount'] = bcmul($item['bet_amount'], 100, 2);
                    $item['rebet'] = bcmul($item['rebet'], 100, 2);
                }
                $rebet = bcadd($rebet, $item['rebet'], 2);
                $tmpValues[$tmpKey] = $item;
            }
            $data[] = [
                'day' => $day,
                'bet_amount' => null,
                'proportion_value' => null,
                'rebet' => strval($rebet),
                'game_type' => 'all',
                'summary' => true,
            ];
            $data = array_merge($data, $tmpValues);
        }

        if($type != 1) {
            $this->redis->setex($redisKey, 3600, json_encode($data, JSON_UNESCAPED_UNICODE));
        }
        return $this->lang->set(0, [], $data, null);
    }

    public function getRebetActConfig($data, $all_amount)
    {
        if (empty($data)) {
            return false;
        }
        if (!in_array($data['type'][0], $this->rebot_way)) {
            return '0';
        }
        if (!in_array($data['type'][1], $this->rebot_way_type)) {
            return '0';
        }
        $value = '0';
        //如果投注额超过最大比例 按最高的算
        foreach ($data['data'] as $key => $item) {
            if ($all_amount >= $item['range'][0]) {
                $value = $item['value'];
            }
        }

        return $value;
    }
};
