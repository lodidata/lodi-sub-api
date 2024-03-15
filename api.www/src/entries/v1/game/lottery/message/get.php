<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/6/27
 * Time: 17:03
 */

use \Utils\Www\Action;

return new class extends Action
{
    const TITLE = "中奖信息展示";
    const HINT = "开发的技术提示信息";
    const TAGS = "彩票";
    const QUERY = [
        'lottery_id'=>'彩种ID',
        'lottery_number'=>'期号（非必传）'
    ];
    const SCHEMAS = [
            [
                'id'=>'信息ID',
                'user_name'=>'用户名',
                'play_group'=>'玩法组',
                'money'=>'中奖金额'
            ]
    ];

    public function run()
    {
        $lottery_id     = $this->request->getQueryParam('lottery_id');//彩种id
        $lottery_number = $this->request->getQueryParam('lottery_number');//期号
        $res            = \Logic\Lottery\InsertNumber::getResult($lottery_id, $lottery_number);

        return $this->lang->set(
            0, [], $res
        );
        /*$lottery_id = $this->request->getQueryParam('lottery_id');//彩种id
        $lottery_number = $this->request->getQueryParam('lottery_number');//期号
        $sql = DB::table('lottery_order')
            ->leftJoin('send_prize', 'lottery_order.order_number', '=', 'send_prize.order_number')
            ->leftJoin('lottery','lottery_id','=','lottery.id')
            ->where('lottery_id', $lottery_id);
        if ($lottery_number != null || $lottery_number != "") {
            $sql->where('lottery_order.lottery_number',$lottery_number);
        }

        $message=$sql
            ->whereRaw("FIND_IN_SET('winning',lottery_order.state)")
            ->orderBy('lottery_order.created', 'desc')
            ->limit(10)
            ->selectRaw('lottery_order.id,lottery_order.user_name,play_group,send_prize.money,lottery.name')
            ->get();
        $message=json_decode($message,true);
        foreach ($message as $key=>$item) {
            foreach ($item as $key2=>$it) {
               if($key2=='user_name'){
                   $str1=mb_substr($it, 0, 2, 'utf-8');
                   $str2=mb_substr($it, -2, 2, 'utf-8');
                   $it=$str1.'****'.$str2;
                   $message[$key][$key2]=$it;
               }
            }
        }
        //return json_decode($message, JSON_UNESCAPED_UNICODE);
        return $message;*/
    }
};