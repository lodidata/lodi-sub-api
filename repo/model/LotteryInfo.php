<?php
/**
 * Created by PhpStorm.
 * User:
 * Date: 2018/3/22
 * Time: 16:30
 */

namespace Model;

class LotteryInfo extends \Illuminate\Database\Eloquent\Model {
    protected $table = 'lottery_info';

    public $timestamps = false;

    protected $fillable = [
        'lottery_number',
        'lottery_name',
        'pid',
        'lottery_type',
        'start_time',
        'end_time',
        'catch_time',
        'official_time',
        'period_code',
        'period_result',
        'n1',
        'n2',
        'n3',
        'n4',
        'n5',
        'n6',
        'n7',
        'n8',
        'n9',
        'n10',
        'sell_status',
        'open_status',
        'state',
    ];

    /**
     * 获取某彩票每天期数,及当天剩余期数
     *
     * @param $id 彩票ID
     *
     * @return array
     */
    public static function getTodayPeriodCount($id) {
        date_default_timezone_set('prc');

        $time = time() - 15;

        $startTime = strtotime(date('Y-m-d 0:0:0', $time));
        $endTime = strtotime(date('Y-m-d 23:59:59', $time)) + 1;

        $todayCount = LotteryInfo::where('lottery_type', $id)
                                 ->where('start_time', '>=', $startTime)
                                 ->where('start_time', '<', $endTime)
                                 ->count();

        $nowCount = LotteryInfo::where('lottery_type', $id)
                               ->where('start_time', '>=', $time)
                               ->where('start_time', '<', $endTime)
                               ->count();

        return ['today_count' => (int)$todayCount ?? 0, 'now_count' => (int)$nowCount ?? 0];
    }

    /**
     * 取当前彩期
     *
     * @param  [type] $id [description]
     *
     * @return [type]     [description]
     */
    public static function getCurperiod($id) {
        global $app;
        $now = time();
        if (is_array($id)) {
            return LotteryInfo::where('start_time', '<=', $now)
                              ->where('end_time', '>', $now)
                              ->whereIn('lottery_type', $id)
                              ->get([
                                  'lottery_type',
                                  'start_time',
                                  'end_time',
                                  'official_time',
                                  'lottery_number',
                                  'state',
                              ])
                              ->toArray();
        } else {
            $sql = "SET @i = (SELECT end_time-start_time FROM lottery_info WHERE lottery_type = {$id} LIMIT 1);";
            $app->getContainer()->db->getConnection()
                                    ->select($sql);
            return LotteryInfo::where('start_time', '>=', $app->getContainer()->db->getConnection()
                                                                                  ->raw("$now - @i"))
                              ->where('start_time', '<=', $now)
                              ->where('end_time', '>=', $now)
                              ->where('end_time', '<', $app->getContainer()->db->getConnection()
                                                                               ->raw("$now + @i"))
                              ->where('end_time', '>', $now)
                              ->where('lottery_type', $id)
                              ->get([
                                  'lottery_type',
                                  'start_time',
                                  'end_time',
                                  'official_time',
                                  'lottery_number',
                                  'state',
                              ])
                              ->toArray();
        }
    }

    /**
     * 从缓存取当前期
     *
     * @param  [type] $ids [description]
     *
     * @return [type]      [description]
     */
    public static function getCacheCurrentPeriod($ids) {
        global $app;
        $redis   = $app->getContainer()->redis;
        $lottery = \Logic\Lottery\RsyncLotteryInfo::init();
        $data = [];
        if (is_array($ids)) {
            foreach ($ids as $id) {
                $temp = $redis->hget(\Logic\Define\CacheKey::$perfix['currentLotteryInfo'], $id);

                //如果缓存为空 主动调用
                if(empty($temp)){
                    \Logic\Lottery\RsyncLotteryInfo::writeCurrAndNextLotteryInfo($lottery);
                    $temp = $redis->hget(\Logic\Define\CacheKey::$perfix['currentLotteryInfo'], $id);
                }else{
                    $lottery_info = json_decode($temp, true);
                    //不是当前期，也要更新
                    if($lottery_info['end_time'] <= time()){
                        \Logic\Lottery\RsyncLotteryInfo::writeCurrAndNextLotteryInfo($lottery);
                        $temp         = $redis->hget(\Logic\Define\CacheKey::$perfix['currentLotteryInfo'], $id);
                    }
                }


                $data[$id]        = json_decode($temp, true);;
                $data[$id]['now'] = time();
            }
        } else {
            $temp = $redis->hget(\Logic\Define\CacheKey::$perfix['currentLotteryInfo'], $ids);

            //如果缓存为空 主动调用
            if(empty($temp)){
                \Logic\Lottery\RsyncLotteryInfo::writeCurrAndNextLotteryInfo($lottery);
                $temp = $redis->hget(\Logic\Define\CacheKey::$perfix['currentLotteryInfo'], $ids);
            }else{
                $lottery_info = json_decode($temp, true);
                //不是当前期，也要更新
                if($lottery_info['end_time'] <= time()){
                    \Logic\Lottery\RsyncLotteryInfo::writeCurrAndNextLotteryInfo($lottery);
                    $temp         = $redis->hget(\Logic\Define\CacheKey::$perfix['currentLotteryInfo'], $ids);
                }
            }
            $data        = json_decode($temp, true);;
            $data['now'] = time();
        }

        return $data;
    }

    /**
     * 从缓存取当下一期期
     *
     * @param  [type] $ids [description]
     *
     * @return [type]      [description]
     */
    public static function getCacheNextPeriod($ids) {
        global $app;
        $redis = $app->getContainer()->redis;
        $data = [];
        if (is_array($ids)) {
            foreach ($ids as $id) {
                $temp = $redis->hget(\Logic\Define\CacheKey::$perfix['nextLotteryInfo'], $id);
                if (!empty($temp)) {
                    $data[$id] = json_decode($temp, true);
                    if (!empty($data[$id])) {
                        $data[$id]['now'] = time();
                    } else {
                        $data[$id] = ['now' => time()];
                    }
                } else {
                    $data[$id] = ['now' => time()];
                }
            }
        } else {
            $temp = $redis->hget(\Logic\Define\CacheKey::$perfix['nextLotteryInfo'], $ids);
            if (!empty($temp)) {
                $data = json_decode($temp, true);
                if (!empty($data)) {
                    $data['now'] = time();
                } else {
                    $data = ['now' => time()];
                }
            } else {
                $data = ['now' => time()];
            }
        }
        return $data;
    }

    /**
 * 取历史列表
 *
 * @param  [type] $id [description]
 *
 * @return [type]     [description]
 */
    public static function getCacheHistory($id) {
        global $app;
        $redis = $app->getContainer()->redis;
        $temp = $redis->hget(\Logic\Define\CacheKey::$perfix['lotteryInfoHistoryList'], $id);
        return !empty($temp) ? json_decode($temp, true) : [];
    }

    /**
     * 取已完全结束历史列表  就是period_code_part 不为空的
     *
     * @param  [type] $id [description]
     *
     * @return [type]     [description]
     */
    public static function getCacheFinishedHistory($id) {
        global $app;
        $redis = $app->getContainer()->redis;
        $temp = $redis->hget(\Logic\Define\CacheKey::$perfix['lotteryInfoFinishedHistoryList'], $id);

        return !empty($temp) ? json_decode($temp, true) : [];
    }

    /**
     * 取上一期
     *
     * @param  [type] $id [description]
     *
     * @return [type]     [description]
     */
    public static function getCacheLastPeriod($id) {
        global $app;
        $redis = $app->getContainer()->redis;
        $temp  = $redis->get(\Logic\Define\CacheKey::$perfix['lastLotteryInfo'].'_'.$id);
        return !empty($temp) ? json_decode($temp, true) : [];
    }

    /**
     * 取统计项
     *
     * @param  [type] $id [description]
     *
     * @return [type]     [description]
     */
    public static function getCachePeriodCount($id) {
        global $app;
        $redis = $app->getContainer()->redis;
        $temp = $redis->hget(\Logic\Define\CacheKey::$perfix['lotteryPeriodCount'], $id);
        return !empty($temp) ? json_decode($temp, true) : [];
    }

    /**
     * 取追号列表
     *
     * @param  [type] $id [description]
     *
     * @return [type]     [description]
     */
    public static function getCacheNextPeriods($id, $size) {
        global $app;
        $redis = $app->getContainer()->redis;
        $data = $redis->hget(\Logic\Define\CacheKey::$perfix['chaseLotteryInfo'], $id);
        if (empty($data)) {
            return [];
        }
        $data = json_decode($data, true);
        foreach ($data as $k => $v){
            if(time() >= $v['end_time']){
                unset($data[$k]);
            }
        }
        $data = array_values($data);
        return array_splice($data, 0, $size);
    }

    /**
     * 获取彩期信息
     * @param $params
     * @return mixed
     */
    public static function getLotteryInfo($params){
        $where = [
            'lottery_number' => $params['lottery_number'],
            'lottery_type'   => $params['lottery_id'],
        ];
        return self::where($where)->select('end_time')->first();
    }
}
