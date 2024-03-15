<?php
//统一走势图
use Utils\Www\Action;
return new class extends Action
{
    const HIDDEN = true;
    const TITLE = "文档编写中";
    const TAGS = "彩票";
    const DESCRIPTION = "文档编写中";
    

    public function run() {
        $lotteryId = (int) $this->request->getQueryParam('lottery_id', 11);
        // $pid = (int) $this->request->getQueryParam('pid', 10);
        $wei = (int) $this->request->getQueryParam('wei', 1);
        $number = (int) $this->request->getQueryParam('number', 30);
        $cacheKey = \Logic\Define\CacheKey::$perfix['trade'].'_'.$lotteryId.'_'.$wei.'_'.$number;
        $data = $this->redis->get($cacheKey);
        $data = !empty($data) ? json_decode($data, true) : [];
        
        // 强制刷新缓存
        $isRefresh = false;

        // 判断是否要强制刷新缓存
        if (!empty($data)) {
            $history = \Model\LotteryInfo::getCacheHistory($lotteryId);
            $end = isset($data['lottery_number']) ? $data['lottery_number'] : 0;
            if ($history[0]['lottery_number'] > $end) {
                $isRefresh = true;
            }
        }

        // 写入缓存
        if (empty($data) || $isRefresh) {
            $lottery = \Model\Lottery::where('id', $lotteryId)->select('pid')->first();
            if (empty($lottery)) {
                return [];
            }

            if($lottery->pid == 5) {
                // 快3类
                $data = $this->getK3Trade($lotteryId, $number);
            } else {
                // 非快3类
                $data = $this->getNotK3Trade($lotteryId, $lottery->pid, $wei, $number);
            }

            $this->redis->setex($cacheKey, 60, json_encode($data));
        }
        return $data;
    }

    public function getNotK3Trade($lotteryId, $pid, $wei, $number) {
        if ($pid == 10) {
            //时时彩
            $start = 0;
            $end = 10;
            $fun = 'getNumberChaSsc';
        } else if ($pid == 24) {
            //11选5
            $fun = 'getNumberCha11x5';
            $start = 1;
            $end = 12;
        } else if ($pid == 39) {
            //11选5
            $fun = 'getNumberChaSc';
            $start = 1;
            $end = 11;
        }

        $wei ='n'.$wei;
        //查询出近number期的第一期
        $sql = "select lottery_number from lottery_info where lottery_type = $lotteryId and find_in_set('open',state) order by lottery_number desc limit $number ";
        $numberData = DB::connection()->select($sql);
        $numberIndex = $number - 1;
        $number = $numberData[$numberIndex]->lottery_number;//第一期的期数
        //算出0-9位每一位的第一期的遗漏
        $result = [];
        for ($i = $start; $i < $end; $i++) {
            $data = DB::connection()
                ->table('lottery_info')
                ->select('lottery_number', $wei)
                ->where('lottery_type', $lotteryId)
                ->where('lottery_number', '<=', $number)
                ->where($wei, $i)
                ->whereRaw("find_in_set('open',state)")
                ->orderBy('lottery_number', 'desc')
                ->first();

            if ($data) {
                $result[$number][$i] = array('yl' => $this->$fun($number , $data->lottery_number));
            } else {
                $result[$number][$i] = array('yl' => 100);//找不到最到的遗漏默认给100
            }
        }
        //获取第一期的开奖结果
        $data = DB::connection()
            ->table('lottery_info')
            ->select('lottery_number', $wei)
            ->where('lottery_type', $lotteryId)
            ->where('lottery_number', $number)
            ->whereRaw("find_in_set('open',state)")
            ->orderBy('lottery_number', 'asc')
            ->first();
        if(!$data) {
            return [];
        }
        $result[$number]['open'] = $data->$wei;
        //循环算进30期的每一期遗漏
        $data = DB::connection()
            ->table('lottery_info')
            ->select('lottery_number', $wei)
            ->where('lottery_type', $lotteryId)
            ->where('lottery_number', '>', $number)
            ->whereRaw("find_in_set('open',state)")
            ->orderBy('lottery_number', 'asc')
            ->limit($number)
            ->get()
            ->toArray();
        $tongji = [];
        for($y = $start;$y<$end;$y++) {
            $ylKey="yl_default_".$y;
            $lianKey = "lian_default_".$i;//连出值
            $ylArrKey="yl_arr_".$y;
            $lianArrKey="lian_arr_".$y;
            $$ylKey = $result[$number][$y]['yl'];//每一位的初始遗漏值
            if ($y == $result[$number]['open']) {
                $$lianKey = 1;
            } else {
                $$lianKey = 0;
            }
            $$lianArrKey = [$$lianKey];//用来计算最大连出
            $$ylArrKey = [$$ylKey];//用来计算最大遗漏和平均遗漏
            $tongji[$y] = [];
        }

        foreach ($data as $k => $value) {
            $lotteryNumber = $value->lottery_number;
            $result[$lotteryNumber] = [];
            $result[$lotteryNumber]['open'] = $value->$wei;

            //var_dump($yl_default_0);exit;
            for($i = $start; $i < $end; $i++) {
                $ylKey = "yl_default_".$i;//遗漏值
                $lianKey = "lian_default_".$i;//连出值
                $ylArrKey = "yl_arr_".$i;
                $lianArrKey = "lian_arr_".$i;
                if($value->$wei == $i) {
                    //本期中，遗漏初始值值0，本期遗漏也为0
                    $$ylKey = 0;
                    @ $$lianKey = $$lianKey + 1;
                    $result[$lotteryNumber][$i]['yl'] = 0;
                } else {
                    @ $$ylKey = $$ylKey +1;
                    $$lianKey = 0;
                    $result[$lotteryNumber][$i]['yl'] = $$ylKey  ;//遗漏值
                }
                array_push($$ylArrKey, $$ylKey);
                array_push($$lianArrKey, $$lianKey);
                //计算平均遗漏，最大遗漏，最大连出，出现次数
                if($k == count($data) - 1) {
                    //必须把第一期的遗漏值算上

                    $maxYl = max($$ylArrKey);
                    $avYl = array_sum($$ylArrKey) / count($data);
                    $maxLian = max($$lianArrKey);
                    $isMeCount = 0;
                    foreach ($$ylArrKey as $k1 => $v1) {
                        if($v1 == 0) {
                            $isMeCount++;
                        }
                    }

                    //赋值
                    $tongji[$i] = [
                        'maxYl' => $maxYl,
                        'maxLian' => $maxLian,
                        'isMeCount' => $isMeCount,
                        'avYl' => (int) $avYl
                    ];
                }
            }
        }
        $arr = [];
        $lotteryNumber = 0;
        foreach ($result as $k => $v) {
            $temp = [];
            foreach ($v as $k1 => $v1) {
                if($k1 === 'open') {
                    $open = $v1;
                } else {
                    $temp[] = $v1;
                }
            }
            $lotteryNumber = $k;
            $arr[] = ["lottery_number" => substr($k, -6), 'data' => $temp, 'open' => $open];
        }
        $res = [];
        foreach ($tongji as $k => $v) {
            $res[] = ['boll' => $k, 'data' => $v];
        }

        return ['data' => $arr, 'tongji' => $res, 'lottery_number' => $lotteryNumber];
    }

    public function getNumberChaSsc($numberEnd, $numberStart) {

        $numberStartHou3 = (int) substr($numberStart,-3);//后3位
        $numberEndHou3 = (int) substr($numberEnd,-3);//后3位

        $numberStartDay = (int) substr($numberStart,6,2);//日期
        $numberEndDay = (int) substr($numberEnd,6,2);//日期

        $numberStartYue = (int) substr($numberStart,4,2);//月
        $numberEndYue = (int) substr($numberEnd,4,2);//月

        return ($numberEndYue - $numberStartYue) * 30 * 120 + ($numberEndDay - $numberStartDay) * 120 + ($numberEndHou3 - $numberStartHou3) ;
    }

    public function getNumberChaK3($numberEnd, $numberStart) {

        $numberStartHou3 = (int) substr($numberStart,-3);//后3位
        $numberEndHou3 = (int) substr($numberEnd,-3);//后3位

        $numberStartDay = (int) substr($numberStart,6,2);//日期
        $numberEndDay = (int) substr($numberEnd,6,2);//日期

        $numberStartYue = (int) substr($numberStart,4,2);//月
        $numberEndYue = (int) substr($numberEnd,4,2);//月

        return ($numberEndYue - $numberStartYue) * 30 * 80 + ($numberEndDay - $numberStartDay) * 80 + ($numberEndHou3 - $numberStartHou3) ;
    }

    public function getNumberCha11x5($numberEnd, $numberStart) {

        $numberStartHou3 = (int) substr($numberStart,-2);//后3位
        $numberEndHou3 = (int) substr($numberEnd,-2);//后3位

        $numberStartDay = (int) substr($numberStart,6,2);//日期
        $numberEndDay = (int) substr($numberEnd,6,2);//日期

        $numberStartYue = (int) substr($numberStart,4,2);//月
        $numberEndYue = (int) substr($numberEnd,4,2);//月

        return ($numberEndYue - $numberStartYue) * 30 * 84 + ($numberEndDay - $numberStartDay) * 84  + ($numberEndHou3 - $numberStartHou3);
    }

    public function getNumberChaSc($numberEnd, $numberStart) {
        return $numberEnd - $numberStart;
    }

    //快3特殊性
    public function getK3Trade($lotteryId, $number) {
        //查询出近number期的第一期
        $sql = "select lottery_number,period_code from lottery_info where lottery_type = $lotteryId and  find_in_set('open',state) order by lottery_number desc limit $number ";
        $numberData = DB::select($sql);
        $numberIndex = $number - 1;
        $number = $numberData[$numberIndex]->lottery_number;//第一期的期数
        //算出1-6位每一位的第一期的遗漏
        $result = [];
        for ($i=1; $i < 7; $i++) {
            $data = DB::connection()
                ->table('lottery_info')
                ->select('lottery_number')
                ->where('lottery_type', $lotteryId)
                ->where('lottery_number', '<=', $number)
                ->whereRaw("(n1 = $i or n2= $i or n3 = $i)")
                ->whereRaw("find_in_set('open',state)")
                ->orderBy('lottery_number', 'desc')
                ->limit(1)
                ->get()
                ->toArray();
            if($data) {
                $result[$number][$i] = ['yl' => $this->getNumberChaK3($number , $data[0]->lottery_number)];

            } else {
                $result[$number][$i] = ['yl' => 100];//找不到最到的遗漏默认给100
            }
        }
        //获取第一期的开奖结果
        $data = DB::connection()
            ->table('lottery_info')
            ->select('lottery_number', 'period_code')
            ->where('lottery_type', $lotteryId)
            ->where('lottery_number', $number)
            ->whereRaw("find_in_set('open',state)")
            ->orderBy('lottery_number', 'asc')
            ->limit(1)
            ->get()
            ->toArray();
        $result[$number]['open'] = $data[0]->period_code;

        //循环算进30期的每一期遗漏
        $data = DB::connection()
            ->table('lottery_info')
            ->select('lottery_number', 'period_code')
            ->where('lottery_type', $lotteryId)
            ->where('lottery_number', '>', $number)
            ->whereRaw("find_in_set('open',state)")
            ->orderBy('lottery_number', 'asc')
            ->limit($number)
            ->get()
            ->toArray();
        $tongji = [];
        for($y = 1; $y < 7; $y++) {
            $ylKey = "yl_default_".$y;
            $lianKey = "lian_default_".$i;//连出值
            $ylArrKey = "yl_arr_".$y;
            $lianArrKey = "lian_arr_".$y;
            $$ylKey = $result[$number][$y]['yl'];//每一位的初始遗漏值
            if(in_array($y, explode(', ', $result[$number]['open']))) {
                $$lianKey = 1;
            } else {
                $$lianKey = 0;
            }
            $$lianArrKey = [$$lianKey];//用来计算最大连出
            $$ylArrKey = [$$ylKey];//用来计算最大遗漏和平均遗漏
            $tongji[$y] = [];
        }

        foreach ($data as $k => $value) {
            $lotteryNumber = $value->lottery_number;
            $result[$lotteryNumber] = [];
            $result[$lotteryNumber]['open'] = $value->period_code;

            //var_dump($yl_default_0);exit;
            for($i = 1; $i < 7; $i++) {
                $ylKey = "yl_default_".$i;//遗漏值
                $lianKey = "lian_default_".$i;//连出值
                $ylArrKey = "yl_arr_".$i;
                $lianArrKey = "lian_arr_".$i;
                if(in_array($i, explode(', ', $result[$lotteryNumber]['open']))) {
                    //本期中，遗漏初始值值0，本期遗漏也为0
                    $$ylKey = 0;
                    @ $$lianKey = $$lianKey + 1;
                    $result[$lotteryNumber][$i]['yl'] = 0;
                } else {
                    @ $$ylKey = $$ylKey +1;
                    $$lianKey = 0;
                    $result[$lotteryNumber][$i]['yl']  = $$ylKey  ;//遗漏值
                }
                array_push($$ylArrKey, $$ylKey);
                array_push($$lianArrKey, $$lianKey);
                //计算平均遗漏，最大遗漏，最大连出，出现次数
                if($k == count($data) - 1) {
                    $maxYl = max($$ylArrKey);
                    $avYl = array_sum($$ylArrKey) / count($data);
                    $maxLian = max($$lianArrKey);
                    $isMeCount = 0;
                    foreach ($$ylArrKey as $k1 => $v1) {
                        if($v1 == 0) {
                            $isMeCount++;
                        }
                    }
                    $maxYl = $maxYl > $number ? $number : $maxYl;
                    $maxLian = $maxLian > $number ? $number : $maxLian;
                    //赋值
                    $tongji[$i] = [
                        'maxYl' => $maxYl,
                        'maxLian' => $maxLian,
                        'isMeCount' => $isMeCount,
                        'avYl' => (int)$avYl
                    ];
                }
            }
        }
        $arr = [];
        $lotteryNumber = 0;
        foreach ($result as $k => $v) {
            $temp = [];
            foreach ($v as $k1 => $v1) {
                if($k1 === 'open') {
                    $open = $v1;
                } else {
                    $temp[] = $v1;
                }
            }

            $arr[] = ["lottery_number" => substr($k, -6), 'data' => $temp, 'open' => $open];
            $lotteryNumber = $k;
        }
        $res=[];
        foreach ($tongji as $k => $v) {
            $res[] = ['boll' => $k, 'data' => $v];
        }

        return ['data' => $arr, 'tongji' => $res, 'lottery_number' => $lotteryNumber];
    }
};