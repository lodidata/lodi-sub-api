<?php

use Utils\Www\Action;

return new class extends Action {
    const TOKEN = true;
    const TITLE = "年数据";
    const DESCRIPTION = "";
    const TAGS = "代理返佣";
    const QUERY = [

    ];
    const SCHEMAS = [
        [
            "bet_amount" => "int(required) #流水",
            "proportion" => "int(required) #占成",
            "bkge" => "int(required) #净利润",
        ]
    ];

    public function run()
    {
        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }
        $uid = $this->auth->getUserId();
        $year = $this->request->getParam('year');

        if (!isset($year) || empty($year)) {
            return $this->lang->set(0, [], []);
        }

        $res = [];
        $info = DB::select('select date_format(date, "%m") as month, sum(bkge) as bkge, sum(bet_amount) as bet_amount, proportion from unlimited_agent_bkge where uid = ? '
            .' and date_format(date, "%Y") = ? group date_format(date, "%m") order by date_format(date, "%m") asc ', [$uid,$year]);

        if (!empty($info)) {
            $tmp_v = 0;
            $counter = 0;
            foreach ($info as $item) {
                $res[$item->month] = [
                    'month' => $item->month,
                    'bet_amount' => $item->bet_amount,
                    'proportion' => $item->proportion,
                    'bkge' => $item->bkge
                ];
                //比较上个月盈利情况
                if ($counter == 0) {
                    $res[$item->month]['change'] = 0;
                } else {
                    if ($tmp_v > $item->bkge) {
                        $res[$item->month]['change'] = -1;
                    } elseif ($tmp_v < $item->bkge) {
                        $res[$item->month]['change'] = 1;
                    } else {
                        $res[$item->month]['change'] = 0;
                    }
                }
                $tmp_v = $item->bkge;
                $counter += 1;
            }
        }

        return $this->lang->set(0, [], $res);
    }
};