<?php

use Logic\Admin\BaseController;
use Logic\Admin\Log;

return new class() extends BaseController
{
    const TITLE = '13/14特殊赔率';
    const DESCRIPTION = '';

    const QUERY = [

    ];

    const PARAMS = [];
    const SCHEMAS = [];
//前置方法
    protected $beforeActionList = [
        'verifyToken', 'authorize'
    ];

    public function run()
    {

        $params = $this->request->getParams();
        $odds = $params['odds'];
        if (!is_array($odds)) {
            return;
        }
        //PC与传统为统一的 传统，所以更新传统时 PC 也随之更新  hall_level 5 传统  hall_level 4  PC 房间
        if (isset($odds[0]['hall_level']) && $odds[0]['hall_level'] == 5) {
            $pc = \DB::table('pc28special')->where('hall_level', 4)->where('lottery_id', $odds[0]['lottery_id'])->where('type', $odds[0]['type'])->first();
            if ($pc) {
                $odds[0]['hall_level'] = 5;
                $tmp = $odds[0];
                $tmp['id'] = $pc->id;
                $tmp['hall_id'] = $pc->hall_id;
                $tmp['hall_level'] = $pc->hall_level;
                $tmp['lottery_id'] = $pc->lottery_id;
                $tmp['type'] = $pc->type;
                $odds = array_merge($odds, [$tmp]);
            }
        }
        foreach ($odds as $key => $odd) {
            if (is_numeric($key) && is_array($odd) && count($odd)) {
                if (isset($odd['sub_odds'])) {
                    unset($odd['sub_odds']);
                }

                /*================================日志操作代码=================================*/
                $data = DB::table('lottery')
                    ->select('id', 'name')
                    ->where('id', '=', $odd['lottery_id'])
                    ->get()
                    ->first();
                $data = (array)$data;
                $levelArray = [
                    '2' => '保本厅',
                    '1' => '回水厅',
                    '3' => '高赔率厅',
                    '4' => 'PC房',
                    '5' => '传统'
                ];
                $str = "彩种名称:{$data['name']}/大厅等级:{$levelArray[$odd['hall_level']]}/类型:{$odd['type']}/";

                /*==================================================================================*/

                $res = $this->updateAndLog($odd, $str);


            }
        }


    }

    public function updateAndLog($data, $str)
    {

        $special = \Model\Admin\Pc28special::find($data['id']);

        if (isset($data['odds'])) {
            $special->odds = $data['odds'];
        }
        if (isset($data['desc'])) {
            $special->desc = $data['desc'];
        }
        if (isset($data['step1'])) {
            $special->step1 = $data['step1'];
        }
        if (isset($data['step2'])) {
            $special->step2 = $data['step2'];
        }
        if (isset($data['odds1'])) {
            $special->odds1 = $data['odds1'];
        }
        if (isset($data['odds2'])) {
            $special->odds2 = $data['odds2'];
        }
        $special->desc_desc = $str;
        $res = $special->save();
        return $res;
    }

};
