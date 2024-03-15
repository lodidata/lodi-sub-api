<?php
namespace Logic\Report;

use Logic\Admin\Message;

ini_set("memory_limit", "1056M");

class RptChannel extends \Logic\Logic {

    //每晚凌晨统计一下渠道报表汇总历史累计数据
    public function rptChannel(){
        $pre_day = date("Y-m-d", strtotime("-1 day"));
        //查询前一天的rpt_channel统计数据
        $data = \DB::connection('slave')->table('rpt_channel')->whereRaw('count_date = ? and channel_id is not null', [$pre_day])
            ->selectRaw('channel_id,channel_name,award_money,click,cz_amount,cz_person,qk_person,qk_amount,tz_amount,pc_amount,hd_amount,hs_amount,js_amount,
            zk_amount,fyz_amount,first_recharge_user,first_recharge,first_withdraw,first_bet,first_prize')->groupBy(['channel_id'])->get()->toArray();
        if (empty($data)) {
            return;
        }

        $list = [];
        foreach ($data as $v) {
            $itm = (array)$v;
            if (empty($itm['channel_id'])) {
                $itm['channel_id'] = "default";
            }
            $itm['award_money'] = empty($itm['award_money']) ? 0 : bcadd($itm['award_money'], 0, 2);
            $list[] = $itm;
        }
        $ck_data = \DB::connection('slave')->table('rpt_channel_total')->get()->toArray();
        if (empty($ck_data)) {     //第一次统计时rpt_channel_total没有数据，直接插入数据
            foreach ($list as $itm) {
                $res = \DB::table('rpt_channel_total')->insertGetId($itm);
                if ($res <= 0) {
                    $this->logger->error("渠道汇总数据统计-插入数据失败：".json_encode($itm));
                }
            }
        } else {  //后续统计在原有数据上累计更新数据
            $fmt_ck_data = [];
            foreach ($ck_data as $ck) {
                $fmt_ck_data[$ck->channel_id] = (array)$ck;
            }
            //循环比较统计数据，已有对应渠道数据则更新，没有则新增
            foreach ($list as $ltm) {
                if (isset($fmt_ck_data[$ltm['channel_id']])) {
                    \DB::table('rpt_channel_total')->whereRaw('channel_id=?',[$ltm['channel_id']])->update([
                        'award_money' => \DB::raw('award_money + '. $ltm['award_money']),
                        'click' =>  \DB::raw('click + '. $ltm['click']),
                        'cz_amount' => \DB::raw('cz_amount + '. $ltm['cz_amount']),
                        'cz_person' => \DB::raw('cz_person + '. $ltm['cz_person']),
                        'qk_person' => \DB::raw('qk_person + '. $ltm['qk_person']),
                        'qk_amount' => \DB::raw('qk_amount + '. $ltm['qk_amount']),
                        'tz_amount' => \DB::raw('tz_amount + '. $ltm['tz_amount']),
                        'pc_amount' => \DB::raw('pc_amount + '. $ltm['pc_amount']),
                        'hd_amount' => \DB::raw('hd_amount + '. $ltm['hd_amount']),
                        'hs_amount' => \DB::raw('hs_amount + '. $ltm['hs_amount']),
                        'js_amount' => \DB::raw('js_amount + '. $ltm['js_amount']),
                        'zk_amount' => \DB::raw('zk_amount + '. $ltm['zk_amount']),
                        'fyz_amount' => \DB::raw('fyz_amount + '. $ltm['fyz_amount']),
                        'first_recharge_user' => \DB::raw('first_recharge_user + '. $ltm['first_recharge_user']),
                        'first_recharge' => \DB::raw('first_recharge + '. $ltm['first_recharge']),
                        'first_withdraw' => \DB::raw('first_withdraw + '. $ltm['first_withdraw']),
                        'first_bet' => \DB::raw('first_bet + '. $ltm['first_bet']),
                        'first_prize' => \DB::raw('first_prize + '. $ltm['first_prize']),
                    ]);
                } else {
                    \DB::table('rpt_channel_total')->insertGetId($ltm);
                }
            }
        }
        return 0;
    }
}