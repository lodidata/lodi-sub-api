<?php
namespace Logic\Lottery;

use Logic\Define\CacheKey;
use Model\LotteryInfo;
use Model\LotteryInsertNumber;
use Utils\Utils;

class InsertNumber extends \Logic\Logic
{
    /**
     * 添加开奖号码
     * @param $params
     */
    public static function insertNumber($params)
    {
        global $app,$logger;
        $redis = $app->getContainer()->redis;
        $cacheKey = CacheKey::$perfix['lotteryInsertNumber'].$params['lottery_id'].'_'.$params['lottery_number'];
        $data[] = [
            'uid'               => $params['uid'],
            'user_account'      => $params['user_account'],
            'number'            => $params['number'],
           // 'lottery_id'        => $params['lottery_id'],
           // 'lottery_number'    => $params['lottery_number'],
            'time'              => isset($params['time']) ? $params['time']: date('m/d/Y H:i:s', time())
        ];
        $redisData = $redis->get($cacheKey);
        if($redisData){
            $redisData = json_decode($redisData, true);
        }
        !$redisData && $redisData = [];
        $redisData = array_merge($data,$redisData);

        $redis->setex($cacheKey, 84600, json_encode($redisData));
    }

    /**
     * 获取每期输入号码
     * @param $lottery_id
     * @param $lottery_number
     * @param int $page
     * @param int $pageSize
     * @return array
     */
    public static function getLotteryNumberList($lottery_id, $lottery_number, $page = 1, $pageSize = 20)
    {
        global $app;
        $redis = $app->getContainer()->redis;
        $cacheKey = CacheKey::$perfix['lotteryInsertNumber'].$lottery_id.'_'.$lottery_number;
        $redisData = $redis->get($cacheKey);
        $sum = 0;
        if(empty($redisData)){
            return [ [], 0, $sum];
        }
        $redisData = json_decode($redisData, true);


        $sum = array_sum(array_column($redisData, 'number'));
        $currKey = ($page-1) * $pageSize;
        $returnList = [];
        $i = 0;

        foreach ($redisData as $key => $item){
            if( $key >= $currKey && $i < $pageSize){
                $item['user_account'] = self::jiaxin($item['user_account']);
                unset($item['uid'], $item['lottery_id'], $item['lottery_number']);
                $returnList[] = $item;
            }
            $i++;
        }

        return [$returnList, count($redisData), $sum];
    }

    /**
     * 返回开奖号码信息
     * @param $lottery_id
     * @param $lottery_number
     * @return array
     */
    public static function getResult($lottery_id, $lottery_number){
        global $app;
        $redis             = $app->getContainer()->redis;
        $cacheKey          = CacheKey::$perfix['lotteryInsertNumber'].$lottery_id.'_'.$lottery_number;
        $finished_history  = LotteryInfo::getCacheFinishedHistory($lottery_id);
        //当期是否能显示开奖结果
        $is_finished = false;
        $prize_info  = [];
        foreach ($finished_history ?? [] as $k => $v){
            if($lottery_number == $v['lottery_number']){
                $is_finished = true;
                $prize_info  = $v;
            }
        }

        $redisData = $redis->get($cacheKey);
        if(empty($redisData)){
            return [];
        }
        $redisData          = json_decode($redisData, true);
        $first_account      = self::jiaxin($redisData[0]['user_account']);
        $sixteenth          = $redisData[15];
        $sixteenth_num      = $sixteenth['number'];
        $sixteenth_account  = self::jiaxin($sixteenth['user_account']);
        $prize_num          = $is_finished ? $prize_info['period_code_part'].str_replace(',','',$prize_info['period_code']) : '';
        $sum                = $is_finished ? bcadd($prize_num, $sixteenth_num) : '';
        $first_3            = $prize_num ? substr($prize_num, -3) : '';
        $end_2              = $prize_num ? substr($prize_num, -5, 2) : '';
        $res = [
            'sum'               => $sum,
            'prize_num'         => $prize_num,
            'first_3'           => $first_3,
            'end_2'             => $end_2,
            'sisteenth_num'     => $sixteenth_num,
            'first_account'     => $first_account,
            'sixteenth_account' => $sixteenth_account,
        ];
        return $res;
    }

    /**
     * 插入号码生成开奖号
     * @param int $lottery_id 彩票ID
     * @param string $lottery_number 彩票期号
     * @param string $endTime 插入截止时间，插入时间大于这个会穿帮
     * @return bool|string
     */
    public static function openCode($lottery_id, $lottery_number, $endTime=null)
    {
        global $app;
        $redis     = $app->getContainer()->redis;
        $cacheKey  = CacheKey::$perfix['lotteryInsertNumber'].$lottery_id.'_'.$lottery_number;
        //加锁，不重复算结果  不让前端再插入数字
        $redis_key = CacheKey::$perfix['openCodeLock'].$lottery_id.'_'.$lottery_number;
        if($redis->get($redis_key)){
            return false;
        }

        $redis->set($redis_key, 1);
        $redis->expire($redis_key, 90);
        //总和
        $number_sum = 0;
        //获取第十六位
        $info16_number = 0;

        //取到开奖结果
        $period_code = LotteryInfo::where(['lottery_number' => $lottery_number, 'lottery_type' => $lottery_id, 'period_code_part' => ''])->value('period_code');
        if(!$period_code){
            return false;
        }

        $period_code = str_replace(',', '', $period_code);

        $redisData = $redis->get($cacheKey);

        if($redisData){
            $redisData = json_decode($redisData, true);
            $number_sum = array_sum(array_column($redisData, 'number'));
        }else{
            $redisData = [];
        }

        $length = count($redisData);
        //到开奖的时候，插入数字不够
        if($length < 20){
            $max_i = 20 - $length;
            for ($i = 0; $i < $max_i; $i++) {
                $data = [
                    'uid'               => 0,
                    'user_account'      => Utils::creatUsername(),
                    'number'            => mt_rand(0, 9) . mt_rand(0, 9) . mt_rand(0, 9) . mt_rand(0, 9) . mt_rand(0, 9),
                    'lottery_id'        => $lottery_id,
                    'lottery_number'    => $lottery_number,
                    'time'              => date('m/d/Y H:i:s', $endTime)
                ];
                InsertNumber::insertNumber($data);
            }
            $redisData = $redis->get($cacheKey);
            $redisData = json_decode($redisData, true);
            $number_sum = array_sum(array_column($redisData, 'number'));
        }

        /**
         * 规则要求：猜号总和结果 - 第十六位 = 开奖结果
         * 思路：总和加上1个号码-第十五位（最终结果的第十六位） = 开奖结果
         * 1、开奖结果+第十六位 < 总和后5位，则开奖结果+第十六位[+100000]，再补1个号码 9+x-4=3 => x=3+4+10-9=8
         * 2、开奖结果+第十六位 = 总和后5位，补个号码 00000
         * 3、开奖结果+第十六位 > 总和后5位 直接补1个号码 1+x-4=3 => x=4+3-1=6
         */
        //取第十五位号码
        $info16 = $redisData[14];
        $info16_number = $info16['number'];
        //echo $number_sum.'----'.$info16_number.'----'.$period_code.PHP_EOL;

        /*$period_code = mt_rand(0, 9) . mt_rand(0, 9) . mt_rand(0, 9) . mt_rand(0, 9) . mt_rand(0, 9);
        $info16_number = mt_rand(0, 9) . mt_rand(0, 9) . mt_rand(0, 9) . mt_rand(0, 9) . mt_rand(0, 9);
        $number_sum = mt_rand(0, 9) . mt_rand(0, 9) . mt_rand(0, 9) . mt_rand(0, 9) . mt_rand(0, 9) . mt_rand(0, 9) . mt_rand(0, 9) . mt_rand(0, 9);

       $number_sum = 133333;
        $period_code = 11111;
        $info16_number = 22222;*/


        //总和后5位
        $number_sum_5 = substr($number_sum, -5);
        $diff_total = bcadd($info16_number, $period_code, 0);
        //echo $number_sum . '---' .$number_sum_5 . '+x = '. $info16_number.'+' . $period_code. ' = '.$diff_total.PHP_EOL;

        //如果总和后5位>开奖结果+第十六位 开奖结果+第十六位+100000 9+x-3=4
        if(bccomp($number_sum_5, $diff_total, 0) > 0){
            $diff_total = bcadd($diff_total, 100000, 0);
        }

        //最后一位,超过5位则截取最后5位,不足5位前面补0
        $diff_number = bcsub($diff_total, $number_sum_5, 0);
        $number_sum  = bcadd($number_sum, $diff_number, 0);
        //echo 'diff_number：'.$diff_number.' = '. $diff_total. ' - ' . $number_sum_5 .PHP_EOL;
         /*if(bccomp($diff_number, 100000, 0) >= 0){
             $diff_number = substr($diff_number, 1,5);
         }*/
        $diff_number = str_pad($diff_number,5,"0",STR_PAD_LEFT);

        //echo $number_sum .'-'. $info16_number . ' = ' . ($number_sum-$info16_number) . ' = ' . $period_code.PHP_EOL;


        //最后一位号码
        $redisData1 = [
            'uid' => 0,
            'user_account' => Utils::creatUsername(),
            'number'        => $diff_number,
            'lottery_id'    => $lottery_id,
            'lottery_number' => $lottery_number,
            'time'           => date('m/d/Y H:i:s', $endTime)
        ];

        //第一名和第十六名记录数据库
        $data = $redisData1;
        $data['sort'] = 1;
        LotteryInsertNumber::insertNumber($data);
        $info16['sort'] = 16;
        $info16['lottery_id']     = $lottery_id;
        $info16['lottery_number'] = $lottery_number;
        LotteryInsertNumber::insertNumber($info16);


        $redisData = array_merge([$redisData1],$redisData);
        //最后一位号码入结果集
        $redis->setex($cacheKey, 84600, json_encode($redisData));
        //更新lottery_info 开奖6-8位(除去后5位，前面的那几位)
        $period_sum       = bcsub($number_sum, $info16_number);
        $period_code_part = substr($period_sum, 0,strlen($period_sum)-5);
        \Model\LotteryInfo::where('lottery_number', $lottery_number)
            ->where('lottery_type', $lottery_id)
            ->update(['period_code_part' => $period_code_part]);

        return true;
    }

    public static function jiaxin($str)
    {
        return mb_substr($str, 0,2).'***'.mb_substr($str, -2);
    }
}