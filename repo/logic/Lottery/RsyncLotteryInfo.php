<?php
namespace Logic\Lottery;
/**
 * 彩期同步模块
 */
class RsyncLotteryInfo extends \Logic\Logic {

    public static $lottery;

    public static function init() {
        if (empty(self::$lottery)) {
            self::$lottery = \Model\Lottery::where('pid', '>', 0)->whereRaw("FIND_IN_SET('enabled',state)")->get()->toArray();
        }
        return self::$lottery;
    }

    /**
     * 同步彩期方法
     * @param  [type] $lottery [description]
     * @return [type]          [description]
     */
    public static function rsyncCreateLottery($lottery, $id = null) {
        global $app, $logger;
        $tables = \Logic\Lottery\OpenPrize::$tables;
//        $common = $app->getContainer()->db->getConnection('common');
        $core = $app->getContainer()->db->getConnection('default');

        foreach ($lottery as $v) {
            if (in_array($v['id'], array_keys($tables))) {
                continue;
            }

            if (!empty($id) && $v['id'] != $id) {
                continue;
            }


//            $commonLastLotteryNumber = (array) $common->table('lottery_info')->where('lottery_type', $v['id'])->orderBy('lottery_number', 'desc')->take(1)->first();
            $commonLastLotteryNumber = self::getApi('info.last', ['id' => $v['id']]);
            if ($commonLastLotteryNumber === false) {
                continue;
            }

            $commonLastLotteryNumber = intval(empty($commonLastLotteryNumber) ? 0 : $commonLastLotteryNumber['lottery_number']);

            $coreLastLotteryNumber = (array) $core->table('lottery_info')->where('lottery_type', $v['id'])->orderBy('lottery_number', 'desc')->take(1)->first();
            $coreLastLotteryNumber = intval(empty($coreLastLotteryNumber) ? 0 : $coreLastLotteryNumber['lottery_number']);
             //print_r($coreLastLotteryNumber);
            // 判断平台和厅主最后一期彩期
            if ($commonLastLotteryNumber > $coreLastLotteryNumber) {
                $bf = $commonLastLotteryNumber - $coreLastLotteryNumber;
                $logger->debug("开始同步彩期 {$v['id']} {$v['name']} common: $commonLastLotteryNumber core: $coreLastLotteryNumber 共: $bf 期");
//                $lotteryInfo = $common->table('lottery_info')
//                ->where('start_time', '>', strtotime('-1 days'))
//                ->where('lottery_type', '=', $v['id'])
//                ->where('lottery_number', '>', $coreLastLotteryNumber)
//                ->orderBy('lottery_number', 'asc')->select(['lottery_name', 'pid', 'lottery_type', 'start_time', 'end_time', 'lottery_number', 'period_code', 'catch_time', 'official_time', 'period_result', 'n1', 'n2', 'n3', 'n4', 'n5', 'n6', 'n7', 'n8', 'n9', 'n10'])->get();

                $lotteryInfo = self::getApi('info', ['id' => $v['id'], 'lottery_number' => $coreLastLotteryNumber]);
                if (empty($lotteryInfo)) {
                    continue;
                }

                foreach ($lotteryInfo as $v2) {
                    $v2 = (array) $v2;
                    $temp = [];
                    $temp['lottery_name'] = $v2['lottery_name'];
                    $temp['pid'] = $v2['pid'];
                    $temp['lottery_type'] = $v2['lottery_type'];
                    $temp['lottery_number'] = $v2['lottery_number'];
                    $temp['start_time'] = $v2['start_time'];
                    $temp['end_time'] = $v2['end_time'];

                    if (!empty($v2['period_code'])) {
                        $temp['period_code'] = $v2['period_code'];
                        $temp['catch_time'] = $v2['catch_time'];
                        $temp['official_time'] = $v2['official_time'];
                        $temp['period_result'] = $v2['period_result'];
                        $temp['n1'] = (int) $v2['n1'];
                        $temp['n2'] = (int) $v2['n2'];
                        $temp['n3'] = (int) $v2['n3'];
                        $temp['n4'] = (int) $v2['n4'];
                        $temp['n5'] = (int) $v2['n5'];
                        $temp['n6'] = (int) $v2['n6'];
                        $temp['n7'] = (int) $v2['n7'];
                        $temp['n8'] = (int) $v2['n8'];
                        $temp['n9'] = (int) $v2['n9'];
                        $temp['n10'] = (int) $v2['n10'];
                    }

                    try {
                        $core->table('lottery_info')->insert($temp);
                    } catch (\Exception $e) {
                        //echo 'message' . $e->getMessage() .PHP_EOL;
                    }
                }


            } else {
                $logger->debug("同步彩期 {$v['id']} {$v['name']} common: $commonLastLotteryNumber core: $coreLastLotteryNumber 共: 0 期");
            }
        }
    }

    /**
     * 六合彩同步彩期方法
     * @param  [type] $lottery [description]
     * @return [type]          [description]
     */
    public static function rsyncCreateLotteryLhc($lottery, $id = 52) {
        global $app, $logger;
        $tables = \Logic\Lottery\OpenPrize::$tables;
//        $common = $app->getContainer()->db->getConnection('common');
        $core = $app->getContainer()->db->getConnection('default');

        foreach ($lottery as $v) {
            if (in_array($v['id'], array_keys($tables))) {
                continue;
            }

            if (!empty($id) && $v['id'] != $id) {
                continue;
            }

//            $commonLastLottery = (array) $common->table('lottery_info')->where('lottery_type', $v['id'])->orderBy('lottery_number', 'desc')->take(1)->first();
            $commonLastLottery = self::getApi('info.last', ['id' => $v['id']]);
            if ($commonLastLottery === false) {
                continue;
            }

             //print_r($commonLastLotteryNumber);
            $commonLastLotteryNumber = intval(empty($commonLastLottery) ? 0 : $commonLastLottery['lottery_number']);
            $commonLastLotteryEndTime = intval(empty($commonLastLottery) ? 0 : $commonLastLottery['end_time']);

            $coreLastLottery = (array) $core->table('lottery_info')->where('lottery_type', $v['id'])->orderBy('lottery_number', 'desc')->take(1)->first();
            $coreLastLotteryNumber = intval(empty($coreLastLottery) ? 0 : $coreLastLottery['lottery_number']);
            $coreLastLotteryEndTime = intval(empty($coreLastLottery) ? 0 : $coreLastLottery['end_time']);
             //print_r($coreLastLotteryNumber);
            // 判断平台和厅主最后一期彩期
            if ($commonLastLotteryNumber > $coreLastLotteryNumber || $commonLastLotteryEndTime != $coreLastLotteryEndTime) {
                // 清除旧数据
                $core->table('lottery_info')->where('lottery_number', '=', $commonLastLotteryNumber)->where('lottery_type', $v['id'])->delete();

                $bf = $commonLastLotteryNumber - $coreLastLotteryNumber;
                $logger->debug("开始同步彩期 {$v['id']} {$v['name']} common: $commonLastLotteryNumber core: $coreLastLotteryNumber 共: $bf 期");

                $lotteryInfo = self::getApi("info", ['id' => $v['id'], 'lottery_number' => $coreLastLotteryNumber]);
                if (empty($lotteryInfo)) {
                    continue;
                }
//                $lotteryInfo = $common->table('lottery_info')
//                ->where('lottery_type', '=', $v['id'])
//                ->where('lottery_number', '>', $coreLastLotteryNumber)
//                ->orderBy('lottery_number', 'asc')->select(['lottery_name', 'pid', 'lottery_type', 'start_time', 'end_time', 'lottery_number', 'period_code', 'catch_time', 'official_time', 'period_result', 'n1', 'n2', 'n3', 'n4', 'n5', 'n6', 'n7', 'n8', 'n9', 'n10'])->get();

                foreach ($lotteryInfo as $v2) {
                    $v2 = (array) $v2;
                    $temp = [];
                    $temp['lottery_name'] = $v2['lottery_name'];
                    $temp['pid'] = $v2['pid'];
                    $temp['lottery_type'] = $v2['lottery_type'];
                    $temp['lottery_number'] = $v2['lottery_number'];
                    $temp['start_time'] = $v2['start_time'];
                    $temp['end_time'] = $v2['end_time'];

                    if (!empty($v2['period_code'])) {
                        $temp['period_code'] = $v2['period_code'];
                        $temp['catch_time'] = $v2['catch_time'];
                        $temp['official_time'] = $v2['official_time'];
                        $temp['period_result'] = $v2['period_result'];
                        $temp['n1'] = (int) $v2['n1'];
                        $temp['n2'] = (int) $v2['n2'];
                        $temp['n3'] = (int) $v2['n3'];
                        $temp['n4'] = (int) $v2['n4'];
                        $temp['n5'] = (int) $v2['n5'];
                        $temp['n6'] = (int) $v2['n6'];
                        $temp['n7'] = (int) $v2['n7'];
                        $temp['n8'] = (int) $v2['n8'];
                        $temp['n9'] = (int) $v2['n9'];
                        $temp['n10'] = (int) $v2['n10'];
                    }

                    try {
                        $core->table('lottery_info')->insert($temp);
                    } catch (\Exception $e) {
                        //echo 'message' . $e->getMessage() .PHP_EOL;
                    }
                }

                // $core->table('lottery_info')->insert($lotteryInfo);

            } else {
                $logger->debug("同步彩期 {$v['id']} {$v['name']} common: $commonLastLotteryNumber core: $coreLastLotteryNumber 共: 0 期");
            }
        }
    }
    /**
     * 刷新历史列表
     * @param  [type] $lottery [description]
     * @return [type]          [description]
     */
    public static function refreshHistory($lottery) {
        global $app;
        
        $data = \Model\LotteryInfo::where('lottery_type', $lottery['id'])
            ->where('end_time', '<', time())
            ->where('period_code', '!=', '')
            ->orderBy('end_time', 'desc')
            ->take(11)
            ->get([
                'lottery_number',
                // 'lottery_name',
                'end_time',
                'period_result',
                'period_code',
                'period_code_part',
                'official_time',
                'state',
                $app->getContainer()->db->getConnection()->raw('unix_timestamp(now()) as now_time'),
                'pid'
            ])->toArray();

        $app->getContainer()->redis->hset(\Logic\Define\CacheKey::$perfix['lotteryInfoHistoryList'], $lottery['id'], json_encode($data, JSON_UNESCAPED_UNICODE));

        if($data){
            $new_data = [];
            foreach ($data as $k => $v){
                if($v['period_code_part']){
                    $new_data[] = $v;
                }
            }
            $app->getContainer()->redis->hset(\Logic\Define\CacheKey::$perfix['lotteryInfoFinishedHistoryList'], $lottery['id'], json_encode($new_data, JSON_UNESCAPED_UNICODE));
        }

        $data = \Model\LotteryInfo::getTodayPeriodCount($lottery['id']);
        $app->getContainer()->redis->hset(\Logic\Define\CacheKey::$perfix['lotteryPeriodCount'], $lottery['id'], json_encode($data, JSON_UNESCAPED_UNICODE));
    }

    /**
     * 加载彩期
     * @return [type] [description]
     */
    public static function loadLotteryInfo($lottery, $continue = null, $lotteryId = null) {
        global $app;
        $redis = $app->getContainer()->redis;
        $redis_key =  \Logic\Define\CacheKey::$perfix['loadLotteryInfoLock'];
        $redis_lock = $redis->get($redis_key);
        if($redis_lock){
            return ;
        }
        $redis->set($redis_key, 1);
        $redis->expire($redis_key, 10*60);

        if (empty($lotteryId)) {
            foreach ($lottery as $v) {
                if ($continue != null && is_array($v['id'], $continue)) {
                    continue;
                }

                $lotteryInfo = \Model\LotteryInfo::select('lottery_number', 'lottery_type', 'start_time', 'end_time', 'pid')
                    ->where('lottery_type', $v['id'])
                    ->whereRaw("UNIX_TIMESTAMP() <= end_time")
                    ->orderBy('end_time', 'asc')
                    ->take(25)
                    ->get()
                    ->toArray();
                // print_r($lotteryInfo);

                $lotteryInfo && $redis->set(\Logic\Define\CacheKey::$perfix['prizeLotteryInfo'].$v['id'], json_encode($lotteryInfo, JSON_UNESCAPED_UNICODE));
            }
        } else {
            $lotteryInfo = \Model\LotteryInfo::field('lottery_number', 'lottery_type', 'start_time', 'end_time', 'pid')
                ->where('lottery_type', $lotteryId)
                ->whereRaw('UNIX_TIMESTAMP() <= end_time')
                ->orderBy('end_time', 'asc')
                ->take(25)
                ->get()
                ->toArray();

            $lotteryInfo && $redis->set(\Logic\Define\CacheKey::$perfix['prizeLotteryInfo'].$lotteryId, json_encode($lotteryInfo, JSON_UNESCAPED_UNICODE));
        }
        $redis->del($redis_key);
    }

    /**
     * 当前彩期和下一期缓存写入
     * @return [type] [description]
     */
    public static function writeCurrAndNextLotteryInfo($lottery) {
        global $app, $logger;
        $redis     = $app->getContainer()->redis;
        $redis_key = \Logic\Define\CacheKey::$perfix['writeCurrAndNextLotteryInfoLock'];
        $lock = $redis->get($redis_key);
        if($lock){
            return;
        }
        $redis->set($redis_key, 1);
        $redis->expire($redis_key, 60*2);

        try{
            foreach ($lottery as $k => $v) {
                if ($v['pid'] <= 0) {
                    continue;
                }
                $lotteryInfo = json_decode($redis->get(\Logic\Define\CacheKey::$perfix['prizeLotteryInfo'].$v['id']), true);

                //主动调用
                if(empty($lotteryInfo)){
                    self::loadLotteryInfo($lottery);
                    $lotteryInfo = json_decode($redis->get(\Logic\Define\CacheKey::$perfix['prizeLotteryInfo'].$v['id']), true);
                }else{
                    //如果最后一期都比当前时间小，说明要更新缓存了
                    $end_period = end($lotteryInfo);
                    if($end_period['end_time'] < time()){
                        self::loadLotteryInfo($lottery);
                        $lotteryInfo = json_decode($redis->get(\Logic\Define\CacheKey::$perfix['prizeLotteryInfo'].$v['id']), true);
                    }
                }

                $isFind      = false;
                foreach ($lotteryInfo ?? [] as $key => $v2) {
                    $time = time();
                    if ($time >= $v2['start_time']  && $time < $v2['end_time']) {
                        $isFind = true;
                        // 写入当前期
                        $redis->hset(\Logic\Define\CacheKey::$perfix['currentLotteryInfo'], $v2['lottery_type'], json_encode(
                            [
                                'lottery_type' => $v2['lottery_type'],
                                'start_time' => $v2['start_time'],
                                'end_time' => $v2['end_time'],
                                'lottery_number' => $v2['lottery_number'],
                                'state' => 'open',
                            ], JSON_UNESCAPED_UNICODE));

                        //self::refreshHistory($v);
                        break;
                    }
                }
            }
        }catch (\Exception $e){
            $redis->del($redis_key);
            $logger->error($e->getMessage());
        }

        $redis->del($redis_key);
    }

    /**
     * 写入追号期数到缓存
     */
    public static function writeChaseLotteryInfo($lottery){
        global $app, $logger;
        $redis     = $app->getContainer()->redis;
        foreach ($lottery as $k => $v){
            $logger->info('时间：'.time());
            // 写入追号期数
            $data = \Model\LotteryInfo::where('end_time', '>', time())
                ->where('lottery_type', $v['id'])
                ->take(200)
                ->select([
                    'end_time',
                    'lottery_number',
                ])->orderBy('end_time', 'asc')->get()->toArray();

            $data && $redis->hset(\Logic\Define\CacheKey::$perfix['chaseLotteryInfo'], $v['id'], json_encode((array) $data, JSON_UNESCAPED_UNICODE));

        }

    }

    /**
     * 追号通知
     * @return [type] [description]
     */
    public static function chaseNotify($lottery) {
        global $app;
        foreach ($lottery as $v) {
            $lotteryInfo = (array) json_decode($app->getContainer()->redis->get(\Logic\Define\CacheKey::$perfix['prizeLotteryInfo'].$v['id']), true);
            foreach ($lotteryInfo as $v2) {
                    //纷纷彩速度较快的，多追一期，现在改下封盘后再追号
                if ($v2['end_time'] - 5 <= time() && $app->getContainer()->redis->sadd(\Logic\Define\CacheKey::$perfix['prizeChase'].$v['id'], $v2['lottery_number'])) {
//                if ($v2['end_time'] <= time() && $app->getContainer()->redis->sadd(\Logic\Define\CacheKey::$perfix['prizeChase'].$v['id'], $v2['lottery_number'])) {
                    $exchange = 'lottery_start_point';
                    \Utils\MQServer::send($exchange, [
                                                'lottery_number' => $v2['lottery_number'],
                                                'lottery_type' => $v2['lottery_type'],
                                                'start_time' => $v2['start_time'],
                                                'end_time' => $v2['end_time'],
                                                'now' => time()
                                            ]);
                }
            }
        }
    }

    /**
     * 执行官开开奖同步
     * @return [type] [description]
     */
    public static function run($lottery) {
        global $app, $logger;
        $tables = \Logic\Lottery\OpenPrize::$tables;
//        $common = $app->getContainer()->db->getConnection('common');
        $core = $app->getContainer()->db->getConnection('default');
        foreach ($lottery as $v) {
            if (in_array($v['id'], array_keys($tables))) {
                continue;
            }

            $lotteryInfo = self::getApi("last", ['id' => $v['id']]);
            if ($lotteryInfo === false) {
                continue;
            }

            // 反转
            $lotteryInfo = array_reverse($lotteryInfo);
            foreach ($lotteryInfo ?? [] as $v2) {
                $v2 = (array) $v2;
                $periodCodes = explode(',', $v2['period_code']);
                $n = [0 => '', 1 => '', 2 => '', 3 => '', 4 => '', 5 => '', 6 => '', 7 => '', 8 => '', 9 => ''];
                foreach ($periodCodes as $ks => $vs) {
                    $n[$ks] = $vs;
                }
               // $logger->debug('【开奖结算通知@1】:'.json_encode(['lottery_number' => $v2['lottery_number'], 'lottery_type' => $v2['lottery_type'], 'period_code' => $v2['period_code']]));
                if ($app->getContainer()->redis->sadd(\Logic\Define\CacheKey::$perfix['prizeRsync'].$v['id'], $v2['lottery_number'])) {
                    $core->table('lottery_info')
                    ->where('lottery_type', $v['id'])
                    ->where('lottery_number', $v2['lottery_number'])
                    ->update([
                        'period_code' => $v2['period_code'],
                        'state' => 'open',
                        'catch_time' => $v2['catch_time'],
                        'official_time' => $v2['official_time'],
                        'period_result' => $v2['period_result'],
                        'n1' => (int) $n[0],
                        'n2' => (int) $n[1],
                        'n3' => (int) $n[2],
                        'n4' => (int) $n[3],
                        'n5' => (int) $n[4],
                        'n6' => (int) $n[5],
                        'n7' => (int) $n[6],
                        'n8' => (int) $n[7],
                        'n9' => (int) $n[8],
                        'n10' => (int) $n[9],
                    ]);

                    // if ($k == 0) {
                        // 写入缓存
                        $app->getContainer()->redis->hset(\Logic\Define\CacheKey::$perfix['prizeLastPeriods'], $v['id'], json_encode([
                            'lottery_number' => $v2['lottery_number'],
                            'lottery_name' => $v['name'],
                            'period_code' => $v2['period_code'],
                            'state' => 'open',
                            'catch_time' => $v2['catch_time'],
                            'official_time' => $v2['official_time'],
                            'period_result' => $v2['period_result'],
                        ], JSON_UNESCAPED_UNICODE));
                    // }
                    // 刷新缓存
                    self::refreshHistory($v);

                    // 发送结算通知
                    $logger->debug('【开奖结算通知】',['lottery_number' => $v2['lottery_number'], 'lottery_type' => $v2['lottery_type'], 'period_code' => $v2['period_code']]);
                    Common::sendQueOpenPrize($v2['lottery_type'],$v2['lottery_number'],$v2['period_code']);
//                    $exchange = 'lotterysettle';
//                    \Utils\MQServer::send($exchange, [
//                                                        'lottery_number' => $v2['lottery_number'],
//                                                        'lottery_type' => $v2['lottery_type'],
//                                                        'period_code' => $v2['period_code']
//                                                    ]);
                }

            }
        }
    }

    /**定时刷新
     * @param $lottery
     */
    public static function autoRefreshHistory($lottery){
        foreach ($lottery as $k => $v){
            self::refreshHistory($v);
        }

    }
    /**
     * 彩果全量同步
     */
    public static function allprize($lottery, $date = null) {
        global $app, $logger;
        $tables = \Logic\Lottery\OpenPrize::$tables;
//        $common = $app->getContainer()->db->getConnection('common');
        $core = $app->getContainer()->db->getConnection('default');
        $date = empty($date) ? (time() - strtotime(date('Y-m-d')) > 10 * 60 ?
                                date('Y-m-d') : date('Y-m-d', strtotime('-1 days'))) : $date;


        foreach ($lottery as $v) {
            if (in_array($v['id'], array_keys($tables))) {//排除自开
                continue;
            }

            $count = $core->table('lottery_info')
                ->where('period_code', '=', '')
                ->where('lottery_type', $v['id'])
                ->where('end_time', '<', time() - 120 > strtotime($date.' 23:59:59') ? strtotime($date.' 23:59:59') : time() - 120)
                ->where('start_time', '>', strtotime($date))
                ->count();

            if ($count > 0) {

                $lotteryInfo = self::getApi("list", ['id' => $v['id'], 'date' => $date]);
                if ($lotteryInfo === false) {
                    continue;
                }
                // $logger->debug('【全量补奖】', ['name' => $v['name'], 'pid' => $v['pid']]);

                foreach ($lotteryInfo ?? [] as $v2) {
                    $v2 = (array) $v2;
                    // $logger->debug('【全量补奖】', ['name' => $v['name'], 'pid' => $v['pid'], 'lottery_number' => $v2['lottery_number'], 'period_code' => $v2['period_code']]); 
                    $core->table('lottery_info')
                    ->where('lottery_type', $v['id'])
                    ->where('lottery_number', $v2['lottery_number'])
                    ->update([
                        'period_code' => $v2['period_code'],
                        'state' => 'open',
                        'catch_time' => $v2['catch_time'],
                        'official_time' => $v2['official_time'],
                        'period_result' => $v2['period_result'],
                        'n1' => $v2['n1'],
                        'n2' => $v2['n2'],
                        'n3' => $v2['n3'],
                        'n4' => $v2['n4'],
                        'n5' => $v2['n5'],
                        'n6' => $v2['n6'],
                        'n7' => $v2['n7'],
                        'n8' => $v2['n8'],
                        'n9' => $v2['n9'],
                        'n10' => $v2['n10'],
                    ]);
                }
            } else {

            }
        }
    }
    /**
     * 执行官开开奖同步
     * @return [type] [description]
     */
    public static function copyCommonLotteryInfoToCore($lotteryId,$lotteryNumberStart,$lotteryNumberEnd) {
        global $app, $logger;
        $common = $app->getContainer()->db->getConnection('common');
        $core = $app->getContainer()->db->getConnection('default');


            // $logger->debug('【官方同步开奖】', ['name' => $v['name'], 'pid' => $v['pid']]);
            $lotteryInfo = $common->table('lottery_info')->where('period_code', '!=', '')
                ->where('lottery_type', $lotteryId)->whereRaw("lottery_number >= $lotteryNumberStart")
                ->whereRaw("lottery_number < $lotteryNumberEnd")->orderBy('lottery_number', 'desc')->get([
                'period_code',
                'lottery_number',
                'lottery_name',
                'period_result',
                'start_time',
                'end_time',
                'catch_time',
                'official_time',
                'lottery_type', 'n1', 'n2', 'n3', 'n4', 'n5', 'n6', 'n7', 'n8', 'n9', 'n10'
            ])->toArray();

            // 反转
            $lotteryInfo = array_reverse($lotteryInfo);
            foreach ($lotteryInfo ?? [] as $v2) {
                $v2 = (array) $v2;

                // $logger->debug('【开奖结算通知@1】:'.json_encode(['lottery_number' => $v2['lottery_number'], 'lottery_type' => $v2['lottery_type'], 'period_code' => $v2['period_code']]));
                if (1) {
                    var_dump($lotteryId);var_dump($v2['lottery_number']);
                    $core->table('lottery_info')
                        ->where('lottery_type', $lotteryId)
                        ->where('lottery_number', $v2['lottery_number'])
                        ->update([
                            'period_code' => $v2['period_code'],
                            'state' => 'open',
                            'catch_time' => $v2['catch_time'],
                            'official_time' => $v2['official_time'],
                            'period_result' => $v2['period_result'],
                            'n1' => $v2['n1'],
                            'n2' => $v2['n2'],
                            'n3' => $v2['n3'],
                            'n4' => $v2['n4'],
                            'n5' => $v2['n5'],
                            'n6' => $v2['n6'],
                            'n7' => $v2['n7'],
                            'n8' => $v2['n8'],
                            'n9' => $v2['n9'],
                            'n10' => $v2['n10'],
                        ]);



                    // 发送结算通知
                    $logger->debug('【开奖结算通知】',['lottery_number' => $v2['lottery_number'], 'lottery_type' => $v2['lottery_type'], 'period_code' => $v2['period_code']]);
                    Common::sendQueOpenPrize($v2['lottery_type'],$v2['lottery_number'],$v2['period_code']);
//                    $exchange = 'lotterysettle';
//                    \Utils\MQServer::send($exchange, [
//                        'lottery_number' => $v2['lottery_number'],
//                        'lottery_type' => $v2['lottery_type'],
//                        'period_code' => $v2['period_code']
//                    ]);
                }

            }
    }

    /**
     * 将直接操作common库修改成通过接口的方式请求common库的内容
     * @author Taylor 2018-12-02
     */
    public static function getApi($action, $args) {
        global $app, $logger;
        $domain = $app->getContainer()->get('settings')['website']['api_common_url'];
        $domain = $domain[array_rand($domain)];//随机去一个地址
        $token = "do3e28ae0e6".date("Ymd");
        $actions = [
            'last' => "api/last", // id
            'list' => "api/list", // id & date
            'info' => "api/info", // id & lottery_number
            'info.last' => "api/info/last", // id
        ];

        try {
            $logger->info('【common db api】', ['api_url'=>$domain.$actions[$action]."?".http_build_query($args)]);
            $req = \Requests::get($domain.$actions[$action]."?".http_build_query($args), ["token" => $token], ["timeout" => 5]);
            if ($req->status_code != 200) {
                return false;
            }

            $data = json_decode($req->body, true);
            $data = json_decode($data['data'], true);
            return $data ?? [];
        } catch (\Exception $e) {
            return [];
        }
    }

}