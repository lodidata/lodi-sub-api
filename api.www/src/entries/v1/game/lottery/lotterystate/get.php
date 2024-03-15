<?php

use Utils\Www\Action;

return new class extends Action
{
    const HIDDEN = true;
    const TAGS = "彩票";
    public function run() {
        //select state from lottery_info WHERE lottery_name='悉尼分分彩' and lottery_number='201804230000'

        $lottery_id = $this->request->getQueryParam('lottery_id', null);
        $lottery_number = $this->request->getQueryParam('lottery_number', null);

        if ($lottery_id != null && $lottery_number != null) {
            $data = DB::table('lottery_info')
                      ->select('state', 'period_code')
                      ->where('lottery_type', $lottery_id)
                      ->where('lottery_number', $lottery_number)
                      ->get()
                      ->toArray();

            $data = array_map(function ($row) {
                if (empty($row->period_code)) {
                    $row->state = '';
                }
                return $row;
            }, $data);



            return $data;
        }

        return $this->lang->set(10);
    }
};