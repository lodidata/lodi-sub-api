<?php
use Utils\Www\Action;
use Model\Lottery;
use Model\Hall;
use Model\LotteryInfo;
return new class extends Action
{
    const HIDDEN = true;
    const TITLE = "GET 获取赔率说明";
    const HINT = "开发的技术提示信息";
    const DESCRIPTION = "获取赔率说明";
    const TAGS = "彩票";
    const QUERY = [
       "lottery_id" => "int(required) #彩票id",
       "hall_id" => "int(required) #厅ID"
   ];
    const SCHEMAS = [
   ];


    public function run() {
        $lotteryId = (int) $this->request->getQueryParam('lottery_id', 0);
        $hallId = (int) $this->request->getQueryParam('hall_id', 0);

        if ($lotteryId == 0 || $hallId == 0) {
            return $this->lang->set(10);
        }

        $hashArr = ['1' => '','10' => '0-9','24' => '01-11','39' => '1-10','5' => '1-6'];

        $lottery = \Model\Lottery::where('id', $lotteryId)->first();
        if (empty($lottery)) {
            return $this->lang->set(23);
        }

        //获取pid
        $pid = $lottery['pid'];
        $returnData = [];

        $data = DB::table('lottery_play_odds')
        ->leftjoin('lottery_play_struct', 'lottery_play_odds.play_id', '=', 'lottery_play_struct.play_id')
        ->where('lottery_play_odds.lottery_id', '=', $lotteryId)
        ->where('lottery_play_odds.hall_id', '=', $hallId)
        ->where('lottery_play_struct.model', '聊天')
        ->selectRaw('lottery_play_struct.name as type,lottery_play_struct.play_id, lottery_play_odds.name, lottery_play_odds.odds')
        ->get()->toArray();

        foreach ($data as $k => $v){
            $v = (array) $v;
            $type = $v['type'];
            if($v['type'] == $v['name']){
                $v['name'] = $hashArr[$pid];
            }
            if(isset($returnData[$type])){
                $returnData[$type][] = $v;
            }else{
                $returnData[$type]=[];
                $returnData[$type][] = $v;
            }
        }

        //获取特殊赔率
        $sData = \Model\Pc28special::where('lottery_id', $lotteryId)->where('hall_id', $hallId)->get()->toArray();
        if(empty($sData)) {
            return ['normal' => $returnData, 'special' => '', 'desc' => ''];
        }
        $sReturnData = [];
        $desc = "";
        foreach ($sData as $k => $v){
            $v = (array) $v;
            $type = $v['type'];

            if(!$desc){
                $desc = $v['desc'];
            }

            $tempArr = [
                [
                    'name' => "<{$v['step1']}",'odds' => "{$v['odds']}",
                ],
                [
                    'name' => "<={$v['step2']}",'odds' => "{$v['odds1']}",
                ],
                [
                    'name' => ">{$v['step2']}",'odds' => "{$v['odds2']}",
                ],
            ];
            $sReturnData[$type] = $tempArr;
        }
        return ['normal' => $returnData, 'special' => $sReturnData, 'desc' => $desc];
    }
};