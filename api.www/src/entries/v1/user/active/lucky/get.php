<?php

use Utils\Www\Action;
use Model\Active;
use Utils\Utils;

return new class extends Action
{
    const TOKEN = true;
    const TITLE = "获取幸运轮盘配置信息";
    const TAGS = "优惠活动";
    const QUERY = [];
    const SCHEMAS = [
        "winHistory" => [
            [
                "user"  => "string(required) #用户名",
                "money" => "float(required) #中奖金额 如：1000",
                "date"  => "string(required) #中奖日期 如：2019-01-09",
            ]
        ],
        "luckyCount" => "int(required) #抽奖次数 10",
        "luckySetting" => [
            "id"        => "int(required) #活动ID",
            "name"      => "string(required) #活动名称",
            "cover"     => "string(required) #活动图片",
            "content"   => "string(required) #规则说明",
            "end_time"  => "string(required) #结束时间 如：2019-01-09 10:51:51",
            "state"     => "enum[apply,auto](required) #状态,有apply就显示,没有就不显示(apply:可申请, auto:自动参与)"
        ],
        "rule" => [
            [
                "award_id"      => "int(required) #规则ID",
                "award_name"    => "string(required) #第几等奖 八等奖",
                "award_type"    => "int(required) #规则类型 1,2,3,4",
                "award_money"   => "float(required) #中奖金额 如：1000",
                "img"           => "string(required) #奖品图片"
            ]
        ]
    ];

    /**
     * 返回幸运转盘配置信息
     * @return mixed
     */
    public function run()
    {
        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }

        $userId = (int)$this->auth->getUserId();

//        $userId = 0;

        /* 查询幸运转盘配置*/
        $luckyData = DB::table('active')->select([
            'id',
            'name',
            'cover',
            'content', 'end_time','description','status'
        ])->where('type_id', 6)->first();

        if (!$luckyData || $luckyData->status!='enabled'|| $luckyData->end_time < date('Y-m-d H:i:s')) {
            //不存在该类型活动
            return $this->lang->set(10811);
        }
        $luckyData->cover && $luckyData->cover = showImageUrl($luckyData->cover);
        $luckyData->description && $luckyData->description = mergeImageUrl($luckyData->description);
        /* 查询幸运转盘rule*/
        $ruleData = DB::table('active_rule')->select(['rule', 'limit_times'])->where('active_id', $luckyData->id)->first();

        if (!$ruleData ) {
            //不存在该类型活动的rule
            return $this->lang->set(10811);
        }


        /* 查询抽奖次数*/
        $count = $this->redis->hget(\Logic\Define\CacheKey::$perfix['lockyCount'] . date('Y-m-d'), $userId);


        $dr=\Model\UserData::where('user_id',$userId)->value('draw_count');

        /* 不存在记录且设置了每天赠送次数.则给用户设置赠送次数*/
        if ($count === null && (int)$ruleData->limit_times) {
            $count = (int)$ruleData->limit_times;
            $this->redis->hset(\Logic\Define\CacheKey::$perfix['lockyCount'] . date('Y-m-d'), $userId, (int)$ruleData->limit_times);

            $this->redis->hset(\Logic\Define\CacheKey::$perfix['luckyCountSum'] . date('Y-m-d'), $userId, $count+$dr);
        }

        $count += $dr;


        /* 查询中奖记录(10条真的10条假的)*/
        $applyData = DB::table('active_apply')->select(['user_name', 'coupon_money'])->where('active_id', $luckyData->id)->limit(10)->orderBy('id', 'desc')->get()->toArray();
        
        /* 循环.处理用户名*/
        foreach ($applyData as $k => &$v) {
            $v = (array)$v;
            $name = $v['user_name'];
            if (strlen($name) > 4) {
                $cutstr = mb_substr($name, 2, -2);
                $name = str_replace($cutstr, '***', $name);
            }

            $applyData[$k] = (array)$applyData[$k];
            $applyData[$k]['user'] = $name;
            $applyData[$k]['money'] = $v['coupon_money'];
            $applyData[$k]['date'] = date('Y-m-d', time());
            unset($v['user_name']);
            unset($v['coupon_money']);
        }
        unset($v);
        /* 获取配置.根据前面的记录造假数据*/
        $rule = json_decode($ruleData->rule, true);

        $money = [];

        foreach ($rule as $k => $v) {
            if($v['award_money']){
                for($j=0; $j<$v['award_id'];$j++){
                    $money[] = $v['award_money'];
                }
            }
            unset($v['user_list']);
            unset($v['withdraw_val']);
            unset($v['award_code']);
            $v['img'] = showImageUrl($v['img']);
            $rule[$k] = $v;
        }

        /* 计算需要造假数据的条数*/
        $limit = 20 - count($applyData);

        $fake = [];

        for ($i = 0; $i < $limit; $i++) {
            $fake[$i]['date'] = date('Y-m-d', time());
            $fake[$i]['user'] = Utils::randUsername();
            $fake[$i]['money'] = $money[array_rand($money)];
        }

        if (count($applyData) > 0) {
            $fake = array_merge($fake, $applyData);
        }

        shuffle($fake);


        $data['winHistory'] = $fake;
        $data['luckyCount'] = (int)$count;
        $data['luckySetting'] = $luckyData;
        $data['rule'] = $rule;

        return $data;
    }
};