<?php
use Utils\Www\Action;
use Logic\Level\Level as levelLogic;

return new class extends Action {
    const TOKEN = true;
    const TITLE = "获取支付渠道";
    const TAGS = "钱包";
    const QUERY = [
    ];
    const SCHEMAS = [];
    public function run($id) {

        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }
        $user = (new \Logic\User\User($this->ci))->getUserInfo($this->auth->getUserId());
        $levelLogic = new levelLogic($this->ci);
        $use_pay_list = $levelLogic->getLevelOnline($user['ranting']);
        $channel=DB::table('payment_channel as pn')
                    ->leftJoin('pay_config as pc','pn.pay_config_id','=','pc.id')
                    ->leftJoin('currency_exchange_rate as ce','pn.currency_id','=','ce.id')
                   ->where('pn.pay_channel_id',$id)
                    ->where('pn.status',1)
                    ->where('pc.status','=','enabled')
                    ->orderBy('pn.sort')
                   ->get(['pn.id','pn.name','pn.currency_type','pn.img','pn.logo','pn.text','pn.min_money','pn.max_money','pn.rechage_money','pn.coin_type','pc.type','ce.exchange_rate','ce.alias'])
                   ->toArray();
        if(!empty($channel)){
            foreach($channel as $key=>&$value){
                $value->pay_type='';
                if(!empty($value->rechage_money)){
                    $rechage_money=json_decode($value->rechage_money,true);
                    ksort($rechage_money);
                    $value->rechage_money=$rechage_money;
                }
                if(!in_array($value->type,$use_pay_list)){
                    unset($channel[$key]);
                }
            }
        }
        return array_values($channel);
    }
};