<?php
namespace Logic\Lottery;
use Logic\Define\CacheKey;
use Logic\Lottery\InsertNumber;
use Model\SelfOpen as selfOpenModel;
use Utils\Utils;
/**
 * 自开奖模块
 */
class OpenPrize extends \Logic\Logic {

    public static $tables = [
        /*99 => 'open_lottery_ffc_99',
        100 => 'open_lottery_sfc_100', 
        101 => 'open_lottery_hfc_101', 
        102 => 'open_lottery_wfc_102', 
        103 => 'open_lottery_xy28_103',
        104 => 'open_lottery_xy28_104',
        105 => 'open_lottery_xy28_105',
        106 => 'open_lottery_pk10_106',
        107 => 'open_lottery_ks_107',
        108 => 'open_lottery_ks_108',
        109 => 'open_lottery_ks_109',*/
        110 => 'open_lottery_swfc_110',
        111 => 'open_lottery_shfc_111',
        112 => 'open_lottery_wfc_112',
        113 => 'open_lottery_sfc_113',
    ];

    //每秒随机猜奖号码数
    public static $secondCount = [
        110 => 2,//15分钟+90=990
        111 => 3,//10分钟+90=690
        112 => 5,//5分钟+90=390
        113 => 6,//3分钟+90=270
    ];

    /**
     * 自开奖补
     * @return [type] [description]
     */
    public static function runReopen() {
        global $app, $logger;
        $db = $app->getContainer()->db->getConnection();
        foreach (self::$tables as $table) {
            $lotteryType = explode('_', $table)[2];
            $lotteryTypeId = explode('_', $table)[3];
            $now = time();
            $tmp_min = strtotime('-2 days');
            $data = $db->table($table)
                        ->leftjoin('lottery_info', function ($join) use ($table, $lotteryTypeId) {
                            $join->on('lottery_info.lottery_number', '=', $table.'.lottery_number');
                        })
//                        ->whereRaw("{$table}.end_time + 60 < {$now}")
                        ->whereRaw("{$table}.end_time < {$now}-60")
                        ->whereRaw("{$table}.end_time > $tmp_min")  //  自开奖没开奖会即时发现并补的，并不会超过七天，优化SQL添加排队条件
                        ->whereRaw("lottery_info.period_code = ''")
                        ->whereRaw("lottery_info.lottery_type = $lotteryTypeId")
                        ->select([
                            "{$table}.lottery_number",
                            "{$table}.period_code",
                        ])
                        ->get()
                        ->toArray();

            $logger->debug($table.' 【自开奖准备补】:'.count($data).'个');
            foreach ($data ?? [] as $val) {
                $val = (array) $val;
                if (empty($val['period_code'])) {
                    $val['period_code'] = self::getRandomOpenCode($lotteryType);
                    //$val['period_code'] = InsertNumber::openCode($lotteryTypeId, $val['lottery_number']);

                    $db->table($table)->where('lottery_number', $val['lottery_number'])->update([
                        'period_code' => $val['period_code'], 
                        'period_type' => 'rand_lottery'
                    ]);
                    $periodCodes = explode(',', $val['period_code']);
                    $n = [0 => '', 1 => '', 2 => '', 3 => '', 4 => '', 5 => '', 6 => '', 7 => '', 8 => '', 9 => ''];
                    foreach ($periodCodes as $ks => $vs) {
                        $n[$ks] = $vs;
                    }
                    $periodResult = array_sum(explode(',', $val['period_code']));
                    \Model\LotteryInfo::where('lottery_type', $lotteryTypeId)
                                        ->where('lottery_number', $val['lottery_number'])
                                        ->update([
                                            'state' => 'open',
                                            'period_code' => $val['period_code'],
                                            'period_result' => $periodResult,
                                            'catch_time' => $db->raw('end_time + 20'),
                                            'official_time' => $db->raw('end_time + 20'),
                                            'n1' => $n[0],
                                            'n2' => $n[1],
                                            'n3' => $n[2],
                                            'n4' => $n[3],
                                            'n5' => $n[4],
                                            'n6' => $n[5],
                                            'n7' => $n[6],
                                            'n8' => $n[7],
                                            'n9' => $n[8],
                                            'n10' => $n[9],
                                        ]);
                    // 发送结算通知
                    // $logger->debug('【自开奖补#2】', ['lottery_number' =>  $val['lottery_number'], 'lottery_type' => $v2['lottery_type'], 'period_code' => $val['period_code'], 'n' => $n]);
                } else {
                    $periodCodes = explode(',', $val['period_code']);
                    $n = [0 => '', 1 => '', 2 => '', 3 => '', 4 => '', 5 => '', 6 => '', 7 => '', 8 => '', 9 => ''];
                    foreach ($periodCodes as $ks => $vs) {
                        $n[$ks] = $vs;
                    }
                    $periodResult = array_sum(explode(',', $val['period_code']));
                    \Model\LotteryInfo::where('lottery_type', $lotteryTypeId)
                                        ->where('lottery_number', $val['lottery_number'])
                                        ->where('period_code', '=', '')
                                        ->update([
                                            'state' => 'open',
                                            'period_code' => $val['period_code'],
                                            'period_result' => $periodResult,
                                            'catch_time' => $db->raw('end_time + 20'),
                                            'official_time' => $db->raw('end_time + 20'),
                                            'n1' => $n[0],
                                            'n2' => $n[1],
                                            'n3' => $n[2],
                                            'n4' => $n[3],
                                            'n5' => $n[4],
                                            'n6' => $n[5],
                                            'n7' => $n[6],
                                            'n8' => $n[7],
                                            'n9' => $n[8],
                                            'n10' => $n[9],
                                        ]);
                }
                // 发送结算通知
                // $logger->debug('【自开奖补#1】', ['lottery_number' =>  $val['lottery_number'], 'lottery_type' => $v2['lottery_type'], 'period_code' => $val['period_code'], 'n' => $n]);
            }
        }
    }

    /**
     * 随机开奖结果 作废
     * @param  [type] $lotteryType [description]
     * @return [type]              [description]
     */
    public static function getPeriodCode($lotteryType,$lotteryId=0,$lotteryNumber=0) {

        switch ($lotteryType) {
            
            // 28类
            case 1:
            case 'xy28':
                return mt_rand(0,9).','.mt_rand(0,9).','.mt_rand(0,9);
                break;
            
            // 赛车类
            case 39:
            case 'pk10':
                $range = ['01', '02', '03', '04', '05', '06', '07', '08', '09', '10'];
                shuffle($range);
                return join(',', $range);
                break;

            // 快3类
            case 5:
            case 'ks':
                $range = [1, 2, 3, 4, 5, 6];
                $codes = [];
                shuffle($range);
                $codes[] = $range[0];
                shuffle($range);
                $codes[] = $range[0];
                shuffle($range);
                $codes[] = $range[0];
                sort($codes);
                return join(',', $codes);
                break;

            // 11选5
            case 24:
                $range = ['01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11'];
                shuffle($range);
                return join(',', array_splice($range, 0, 5));
                break;

            // 时时彩
            case 10:
            case 'ffc':

            case 'sfc':
            case 'hfc':
            case 'wfc':
            case 'shfc':
            case 'swfc':
                return mt_rand(0,9).','.mt_rand(0,9).','.mt_rand(0,9).','.mt_rand(0,9).','.mt_rand(0,9);
                break;

            default:
                return null;
        }
    }

    /**
     * 自开奖生成彩期
     * @param  [type] $timestamp [description]
     * @param  [type] $no        [description]
     * @return [type]            [description]
     */
    public static function getNo($timestamp, $no) {
        return date("Ymd", $timestamp) . str_pad($no, 4, "0", STR_PAD_LEFT);
    }

    /**
     * 自开奖彩期生成
     * @param  [type] $lottery [description]
     * @return [type]          [description]
     */
    public static function createLottery($lottery, $conf, $startDate = '2017-09-21', $delay = 5) {
        global $app, $logger;
        $conf = (array) $conf;
        $db = $app->getContainer()->db->getConnection();
        $table = self::$tables[$lottery['id']];
        $step = $conf['lottery_interval'];
//        $count = $db->table($table)->whereRaw("DATE(FROM_UNIXTIME(start_time)) = DATE('{$startDate}')")->count();
        //SQL优化
        $tmp_date = date('Y-m-d',strtotime($startDate));
        $stime = strtotime($tmp_date);
        $etime = strtotime($tmp_date.' 23:59:59');
        $count = $db->table($table)->where("start_time",'>=',$stime)->where("start_time",'<=',$etime)->count();

        if ($count == 0) {
            $lotteryNumber = 1;
            $numDate = 1;
            $openLotteryInfo = [];
            $lotteryInfo = [];
            for ($i = 0; $i < $numDate; $i++) {
                $nowTime = strtotime($startDate) + $i * 86400;
                $maxTime = strtotime($startDate) + ($i + 1) * 86400;
                $num = 0;
                while (true) {
                    if ($nowTime >= $maxTime) {
                        break;
                    }

                    $starTime = $nowTime;
                    $endTime = $nowTime + $step - $delay;
                    $no = self::getNo($nowTime, $lotteryNumber);
                    $openLotteryInfo[] = [
                        'period_type' => '',
                        'period_code' => '',
                        'start_time' => $starTime,
                        'end_time' => $endTime,
                        'lottery_number' => $no
                    ];
                    $starTime = $starTime - 5;
                    $lotteryInfo[] = [
                        'lottery_number' => $no,
                        'lottery_name' => $lottery['name'],
                        'pid' =>  $lottery['pid'],
                        'lottery_type' => $lottery['id'],
                        'start_time' => $starTime,
                        'end_time' => $endTime,
                    ];
                    $nowTime += $step;
                    $lotteryNumber++;
                    $num++;
                }

//                $logger->debug("{$table} {$startDate} 生成 {$num} 行数据, 最后一条数据时间:" . date('Y-m-d H:i:s', $endTime) . ' 最后彩期号:' . $lotteryNumber);
                $app->getContainer()->logger->info("{$table} {$startDate} 生成 {$num} 行数据, 最后一条数据时间:" . date('Y-m-d H:i:s', $endTime) . ' 最后彩期号:' . $lotteryNumber);
            }

            if (!empty($openLotteryInfo) && !empty($lotteryInfo)) {
                \Model\LotteryInfo::insert($lotteryInfo);
                $db->table($table)->insert($openLotteryInfo);
                $app->getContainer()->redis->del(CacheKey::$perfix['lotteryInfo']);
            }
        } else {
            // throw new \Exception("彩期已期生成", 1);
        }
    }

    /**
     * 自开奖彩期生成
     * @param  [type] $lottery [description]
     * @return [type]          [description]
     */
    public static function openPrizeCreateLottery($lottery) {
        global $app;
        $db = $app->getContainer()->db->getConnection();
        foreach ($lottery as $v) {
            if (!in_array($v['id'], array_keys(self::$tables))) {
                continue;
            }
            $conf = $db->table('openprize_config')->where('lottery_id', $v['id'])->first();
            self::createLottery($v, $conf, date('Y-m-d'));
            self::createLottery($v, $conf, date('Y-m-d', strtotime('+1 days')));
        }
    }

    /**
     * 执行自开奖
     * @return [type] [description]
     */
    public static function run($lottery) {
        global $app, $logger;
        $db = $app->getContainer()->db->getConnection();
        $redis = $app->getContainer()->redis;
        //循环所有彩种
        foreach ($lottery as $v) {
            if (!in_array($v['id'], array_keys(self::$tables))) {
                continue;
            }

            $table       = self::$tables[$v['id']];
            $lotteryInfo = (array) json_decode($redis->get(\Logic\Define\CacheKey::$perfix['prizeLotteryInfo'].$v['id']), true);

            //获取已结束的期，并产生开奖结果
            foreach ($lotteryInfo ??[] as $v2) {
                if(empty($v2['lottery_number'])){
                    $logger->info(json_encode($v2));
                    continue;
                }
                $lotteryNumber = $v2['lottery_number'] - 1;
                //获取已结束的期 延迟90秒开奖
                if ($v2['start_time'] + 2 < time() - 90 && $redis->sadd(\Logic\Define\CacheKey::$perfix['prizeOpenSettle'].$v['id'], $lotteryNumber)) {

                    $current = $db->table($table)->where('lottery_number', '=', $lotteryNumber)->first();

                    try {
                        $openCode = !empty($current) ? $current->period_code : '';
                    } catch (\Exception $e) {
                        echo "Error: $table $lotteryNumber", PHP_EOL;
                        $openCode = '';
                    }

                    // 自开奖表写入开奖结果
                    if (empty($openCode)) {
                         $openResult = self::getSuitableOpenCodeViva((int)$v2['lottery_type'], (string)$lotteryNumber, (int)$v2['pid']);
                         $openCode   = $openResult['prize_code'];

                         switch ($openResult['prize_type']){
                             case 'interval' :
                                 $period_type = 'interval_lottery';
                                 break;
                             case 'jackpot'  :
                                 $period_type = 'jackpot_lottery';
                                 break;
                             default :
                                 $period_type = 'rand_lottery';
                         }
                    }else{
                        self::getSuitableOpenCodeViva((int)$v2['lottery_type'],(string)$lotteryNumber,(int)$v2['pid'],(string)$openCode,true);  //主要 存数据
                        $period_type = 'manual_lottery';
                    }

                    $db->table($table)
                        ->where('lottery_number', $lotteryNumber)
                        ->update([
                            'period_type' => $period_type,
                            'period_code' => $openCode,
                        ]);
                    $periodCodes = explode(',', $openCode);
                    $n           = [0 => '', 1 => '', 2 => '', 3 => '', 4 => '', 5 => '', 6 => '', 7 => '', 8 => '', 9 => ''];

                    foreach ($periodCodes as $ks => $vs) {
                        $n[$ks] = $vs;
                    }
                    // lottery_info 写入开奖结果
                    $periodResult = array_sum(explode(',', $openCode));
                    \Model\LotteryInfo::where('lottery_number', $lotteryNumber)
                        ->where('lottery_type', $v2['lottery_type'])
                        ->where('period_code','=', '')
                        ->update([
                            'period_code'   => $openCode,
                            'state'         => 'open',
                            'catch_time'    => $db->raw('end_time + 20'),
                            'official_time' => $db->raw('end_time + 20'),
                            'period_result' => $periodResult,
                            'n1'            => (int) $n[0],
                            'n2'            => (int) $n[1],
                            'n3'            => (int) $n[2],
                            'n4'            => (int) $n[3],
                            'n5'            => (int) $n[4],
                            'n6'            => (int) $n[5],
                            'n7'            => (int) $n[6],
                            'n8'            => (int) $n[7],
                            'n9'            => (int) $n[8],
                            'n10'           => (int) $n[9],
                        ]);
                    
                    // 写入缓存
                    $redis->hset(\Logic\Define\CacheKey::$perfix['prizeLastPeriods'], $v['id'], json_encode([
                        'lottery_type' => $v['id'],
                        'lottery_number' => $lotteryNumber,
                        'lottery_name' => $v['name'],
                        'period_code' => $openCode,
                        'state' => 'open',
                        'catch_time' => $v2['end_time'] + 20,
                        'official_time' => $v2['end_time'] + 20,
                        'period_result' => $periodResult
                    ], JSON_UNESCAPED_UNICODE));

                    // 刷新缓存
                    \Logic\Lottery\RsyncLotteryInfo::refreshHistory($v);

                    // 发送结算通知
                    $logger->debug('【自开奖结算通知】', ['lottery_number' => $lotteryNumber, 'lottery_type' => $v2['lottery_type'], 'period_code' => $openCode, 'n' => $n]);
                    Common::sendQueOpenPrize($v2['lottery_type'],$lotteryNumber,$openCode);
//                    $exchange = 'lotterysettle';
//                    \Utils\MQServer::send($exchange, [
//                                                        'lottery_number' => $lotteryNumber,
//                                                        'lottery_type' => $v2['lottery_type'],
//                                                        'period_code' => $openCode
//                                                     ]);
                }
            }
        }
    }

    /**
     * 自开奖获取最优开奖号码  新  viva
     * @param int $lotteryId 彩种ID
     * @param string $lotteryNumber 当期彩票期号
     * @param int $pid 彩种上级ID 10
     * @param string $manualCode 开奖号
     * @param bool $isManual 是否使用开奖号
     * @param int $maxPrcoessTime 最大执行时间差
     * @return array
     */
    public static function getSuitableOpenCodeViva(int $lotteryId,string $lotteryNumber,int $pid,string $manualCode = '',bool $isManual = false,$maxPrcoessTime = 10){
        // 初始化相应的数据
        $timeNow            = time();
        $prize_code         = '';
        $rand_code          = [];
        $maxOpenTimes       = 10000;//默认开奖次数
        $sumPayMoney        = 0;//总下注金额
        $sumSendMoney       = 0;//总中奖金额
        $maxOnceSendMoney   = 0;//单次中奖最高金额
        $minOnceSendMoney   = 99999999999;//单次中奖最低金额
        $prizeCounts        = 0;//派奖次数
        $lotteryConfig      = \DB::table('openprize_config')->where("lottery_id",$lotteryId)->first();

        if($lotteryConfig){
            //有自开奖控制
            $minProfite = $lotteryConfig->min_profit ? : 0.60;
            $maxProfite = $lotteryConfig->max_profit ? : 0.90;
        }else {
            //无，随机开奖号
            return ['prize_code' => self::getRandomOpenCode($lotteryId),'prize_type' => 'rand'];
        }

        global $app;

        $settle 	=  new \Logic\Lottery\Settle($app->getContainer());
        $self_data 	= [];

        for($i=0; $i < $maxOpenTimes; $i++){
            if(time() - $timeNow > $maxPrcoessTime){
                break;//已经超过最大执行时间
            }

            $sumPayMoney      = 0;//总下注金额
            $sumSendMoney     = 0;//总中奖金额
            $maxOnceSendMoney = 0;//单次中奖最高金额
            $minOnceSendMoney = 99999999999;//单次中奖最低金额
            $prizeCounts 	  = 0;//派奖次数

            //当期开奖信息
            $lotteryInfo                = ['lottery_number'=>$lotteryNumber,'lottery_type'=>$lotteryId,'pid'=>$pid];
            $lotteryInfo['period_code'] = $isManual ? $manualCode : self::getRandomOpenCode($pid);
            //得到当期所有开奖后的数据
            $self_data = $settle->runByNotifyV2($lotteryInfo['lottery_type'],$lotteryInfo['lottery_number'],'test',$lotteryInfo);
            if(!$self_data)
                break;

            foreach($self_data as $openResult){
                $sumPayMoney  += $openResult['pay_money'];
                $sumSendMoney += $openResult['money'];

                if ($openResult['money'] > 0) {
                    $prizeCounts++;

                    if ($minOnceSendMoney > $openResult['money'])
                        $minOnceSendMoney = $openResult['money'];
                    if ($maxOnceSendMoney < $openResult['money'])
                        $maxOnceSendMoney = $openResult['money'];
                }
            }

            if($isManual){
                $prize_code = $lotteryInfo['period_code'];
                break;
            }

            switch ($lotteryConfig->period_code_type){
                case 'interval':  //返奖率模式   返奖率为  派奖金额 / 下注金额
                    //控制率控制 返奖率
                    if($lotteryConfig->interval_profit < 100) {
                        $rand_num =  rand() % 10000;
                        //随机数值不在控制数值内，随机开奖
                        if($rand_num > $lotteryConfig->interval_profit * 100) {
                            $prize_code = $lotteryInfo['period_code'];
                        }
                    }

                    if(!$prize_code) {
                        $profite = $sumPayMoney ? $sumSendMoney / $sumPayMoney : 0;
                        if ($profite >= $minProfite && $profite <= $maxProfite) {
                            $prize_code = $lotteryInfo['period_code'];
                        }
                        $tmp_k = intval($profite * 100);
                    }

                    break;
                case 'jackpot' :         //奖池模式  奖池  上期累计+本次投注金额
                     $jackpot = $sumPayMoney + $lotteryConfig->jackpot;
                     if($jackpot > $sumSendMoney){
                         $prize_code = $lotteryInfo['period_code'];
                     }

                    $tmp_k = intval($sumSendMoney);
                    break;
                default:               //完全随机开奖模式
                    $prize_code = $lotteryInfo['period_code'];
            }

            $tmp_k = isset($tmp_k) ? $tmp_k : 0;
            $rand_code[$tmp_k] = [      //随机测试过奖的列表，以防执行时间到，未取到最优角，则取里面最优解
                'period_code'       => $lotteryInfo['period_code'],
                'sumPayMoney'       => $sumPayMoney,
                'sumSendMoney'      => $sumSendMoney,
                'prizeCounts'       => $prizeCounts,
                'minOnceSendMoney'  => $minOnceSendMoney,
                'maxOnceSendMoney'  => $maxOnceSendMoney,
            ];
            if($prize_code)
                break;
        }
        //无数据
        if(count($self_data) < 1){
            if ($lotteryConfig->period_code_type == 'interval')
                $desc = ($minProfite*100) . '%-' . ($maxProfite*100).'%';
            elseif ($lotteryConfig->period_code_type == 'jackpot' && $lotteryConfig->jackpot_set == 1) {
                $desc = $app->getContainer()->lang->text("reset") . ($lotteryConfig->jackpot/100);
                \DB::table('openprize_config')->where("lottery_id", $lotteryId)->update(['jackpot_set' => '0']);
            }else {
                $desc = $app->getContainer()->lang->text('Completely random lottery');
            }
            $code = self::getRandomOpenCode($pid);
            $data=[
                'lottery_id'        => $lotteryId,
                'lottery_number'    => $lotteryNumber,
                'code'              => $code,
                'pay_money'         => 0,
                'send_money'        => 0,
                'profit'            => 0,
                'jackpot'           => $lotteryConfig->period_code_type == 'jackpot' ? $lotteryConfig->jackpot : 0,
                'time'              => 0,
                'counts'            => 0,
                'desc'              => $desc,
            ];
            if(!selfOpenModel::getId($lotteryId, $lotteryNumber)){
                \DB::table('self_open')->insert($data);
            }

            return ['prize_code' => $code,'prize_type' => $lotteryConfig->period_code_type];
        }
        //执行时间到，从结果集取最优的
        $desc = '';
        $newJackpot = 0;
        if(!$isManual) {
            if (!$prize_code) {
                ksort($rand_code);
                $tmp                = array_shift($rand_code);
                $prize_code         = $tmp['period_code'];
                $sumPayMoney        = $tmp['sumPayMoney'];
                $sumSendMoney       = $tmp['sumSendMoney'];
                $prizeCounts        = $tmp['prizeCounts'];
                $minOnceSendMoney   = $tmp['minOnceSendMoney'];
                $maxOnceSendMoney   = $tmp['maxOnceSendMoney'];
                $desc               = $app->getContainer()->lang->text('');
            }

            if ($lotteryConfig->period_code_type == 'interval')
                $desc = ($minProfite*100) . '%-' . ($maxProfite*100).'%';
            if ($lotteryConfig->period_code_type == 'jackpot' && $lotteryConfig->jackpot_set == 1) {
                $desc = $app->getContainer()->lang->text('reset') . ($lotteryConfig->jackpot/100);
            }

            if ($lotteryConfig->period_code_type == 'jackpot') {
                $newJackpot = $sumPayMoney + $lotteryConfig->jackpot - $sumSendMoney;
                \DB::table('openprize_config')->where("lottery_id", $lotteryId)->update(['jackpot' => $newJackpot, 'jackpot_set' => '0']);
            }
        }

        $minOnceSendMoney = $maxOnceSendMoney ? $minOnceSendMoney : 0;  //极端情况下，无一人中奖
//        echo "本次开奖共用时间：".(time() - $timeNow)."秒\n";
        $profit = $sumPayMoney ? round($sumSendMoney/$sumPayMoney,2) : 0;

        $data=[
            'lottery_id'            => $lotteryId,
            'lottery_number'        => $lotteryNumber,
            'code'                  => $prize_code,
            'time'                  => time() - $timeNow,
            'pay_money'             => $sumPayMoney,
            'send_money'            => $sumSendMoney,
            'profit'                => $lotteryConfig->period_code_type == 'interval' ? $profit : 0,
            'counts'                => count($self_data),
            'prize_counts'          => $prizeCounts,
            'min_prize_once_money'  => $minOnceSendMoney,
            'max_prize_once_money'  => $maxOnceSendMoney,
            'jackpot'               => $lotteryConfig->period_code_type == 'jackpot' ? $newJackpot : 0,
            'desc'                  => $lotteryConfig->period_code_type == 'rand' ? $desc : $app->getContainer()->lang->text("It's a completely self starter"),
        ];

        if(!selfOpenModel::getId($lotteryId, $lotteryNumber)){
            \DB::table('self_open')->insert($data);
        }
        return ['prize_code' => $prize_code,'prize_type' => $lotteryConfig->period_code_type];
    }

    /**
     * 随机开奖结果
     * @param string|int $lotteryType 彩票分类pid或者alias
     * @return string|null
     */
    public static function getRandomOpenCode($lotteryType) {
        switch ($lotteryType) {

            // 28类
            case 1:
            case 'xy28':
                return mt_rand(0,9).','.mt_rand(0,9).','.mt_rand(0,9);
                break;

            // 赛车类
            case 39:
            case 'pk10':
                $range = ['01', '02', '03', '04', '05', '06', '07', '08', '09', '10'];
                shuffle($range);
                return join(',', $range);
                break;

            // 快3类
            case 5:
            case 'ks':
                $range = [1, 2, 3, 4, 5, 6];
                $codes = [];
                shuffle($range);
                $codes[] = $range[0];
                shuffle($range);
                $codes[] = $range[0];
                shuffle($range);
                $codes[] = $range[0];
                sort($codes);
                return join(',', $codes);
                break;

            // 11选5
            case 24:
                $range = ['01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11'];
                shuffle($range);
                return join(',', array_splice($range, 0, 5));
                break;

            // 时时彩
            case 10:
            case 'ffc':

            case 'sfc':
            case 'hfc':
            case 'wfc':
            case 'shfc':
            case 'swfc':
                return mt_rand(0,9).','.mt_rand(0,9).','.mt_rand(0,9).','.mt_rand(0,9).','.mt_rand(0,9);
                break;

            default:
                return null;
        }
    }

    /*
     *
     */
    public static function array_sort($array, $on, $order=SORT_DESC,$amount)
    {
        $new_array = array();
        $sortable_array = array();

        if (count($array) > 0) {
            foreach ($array as $k => $v) {
                    $v = (array)$v;
                    $v['true_profit'] = $v[$on];
                    $v[$on] = abs($v[$on] - $amount);
                    $array[$k] = $v;
                    foreach ($v as $k2 => $v2) {
                        if ($k2 == $on) {
                            $sortable_array[$k] = $v2;
                        }
                    }
            }
            switch ($order) {
                case SORT_ASC:
                    asort($sortable_array);
                    break;
                case SORT_DESC:
                    arsort($sortable_array);
                    break;
            }
            foreach ($sortable_array as $k => $v) {
                $new_array[$k] = $array[$k];
            }
        }

        return $new_array;
    }


    /**
     * 每秒随机生成猜奖号
     * 随机条数根据分类设定$secondCount
     * 结束时间+90录入数据，90后根据开奖号设定最后一位随机数
     */
    public static function randomInsertNumber(){
        global $app,$logger;
        $redis = $app->getContainer()->redis;
        $time  = time();
        $tables = self::$tables;
        //加个锁
        if($redis->get(\Logic\Define\CacheKey::$perfix['randomInsertNumberLock'])){
            return;
        }
        $redis->setex(\Logic\Define\CacheKey::$perfix['randomInsertNumberLock'],1800,1);
        try{
            foreach($tables as $lottery_id => $table){
                //这个列表  只包含已经有开奖结果的
                $oldhistoryList = $redis->hGet(\Logic\Define\CacheKey::$perfix['lotteryInfoHistoryList'], $lottery_id);
                if(isset($oldhistoryList) && $oldhistoryList){
                    $historyList = json_decode($oldhistoryList, true);
                    $firstLottery = current($historyList);
                    $endLottery   = end($historyList);

                    //怕历史排序有变化  所以比较一下
                    if($firstLottery['end_time'] > $endLottery['end_time']){
                        $lastLottery = $firstLottery;
                    }else{
                        $lastLottery = $endLottery;
                    }

                    //开奖猜号码 开上一期猜奖号 结束时间+90
                    if($lastLottery['end_time'] + 90 < $time){
                        InsertNumber::openCode($lottery_id, $lastLottery['lottery_number'], $lastLottery['end_time'] + 90);
                    }
                }

                //当前期
                $currentLottery = $redis->hGet(\Logic\Define\CacheKey::$perfix['currentLotteryInfo'], $lottery_id);
                if(isset($currentLottery) && $currentLottery){
                    $currentLottery = json_decode($currentLottery, true);
                    $lottery_number = $currentLottery['lottery_number'];
                    $start_time     = $currentLottery['start_time'];
                    $end_time       = $currentLottery['end_time'];
                    //缓存里读取的当前期不一定正确
                    if($time >= $start_time && $time <= $end_time){
                        //每秒随机生成猜奖号
                        $max_i = random_int(0, self::$secondCount[$lottery_id]);
                        for ($i = 0; $i < $max_i; $i++) {
                            $data = [
                                'uid'               => 0,
                                'user_account'      => Utils::randUsername(),
                                'number'            => mt_rand(0, 9) . mt_rand(0, 9) . mt_rand(0, 9) . mt_rand(0, 9) . mt_rand(0, 9),
                                'lottery_id'        => $lottery_id,
                                'lottery_number'    => $lottery_number,
                                'time'              => date('m/d/Y H:i:s', $time)
                            ];
                            InsertNumber::insertNumber($data);
                        }
                    }
                    //插入上一期
                    if($time <= $start_time + 90){
                        $last_lottery_number = $lottery_number - 1;
                        //当第一期时，上一期其实不存在，但这也不管了
                        //每秒随机生成猜奖号
                        $max_i = random_int(0, self::$secondCount[$lottery_id]);
                        for ($i = 0; $i < $max_i; $i++) {
                            $data = [
                                'uid'               => 0,
                                'user_account'      => Utils::randUsername(),
                                'number'            => mt_rand(0, 9) . mt_rand(0, 9) . mt_rand(0, 9) . mt_rand(0, 9) . mt_rand(0, 9),
                                'lottery_id'        => $lottery_id,
                                'lottery_number'    => $last_lottery_number,
                                'time'              => date('m/d/Y H:i:s', $time)
                            ];
                            InsertNumber::insertNumber($data);
                        }
                    }

                }
            }
        }catch (\Exception $e){
            $redis->del(\Logic\Define\CacheKey::$perfix['randomInsertNumberLock']);
        }

        $redis->del(\Logic\Define\CacheKey::$perfix['randomInsertNumberLock']);
    }


}