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

    public function run()
    {
        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }
        $user = (new \Logic\User\User($this->ci))->getUserInfo($this->auth->getUserId());
//        $levelLogic = new levelLogic($this->ci);
//        $use_pay_list = $levelLogic->getLevelOnline($user['ranting']);
        $use_pay_list = DB::table('level_payment')->where('level_id',$user['ranting'])->pluck('payment_id')->toArray();
        $res=DB::table('pay_channel')->where('status',1)->orderBy('sort','asc')->get(['id','logo','name','type','img','text','min_money','max_money','guide_url'])->toArray();
        $is_kpay = false;
        $pay_cid = 0;
        if(!empty($res)){
            foreach($res as $v){
                $channel=DB::table('payment_channel as pn')
                            ->leftJoin('pay_config as pc','pn.pay_config_id','=','pc.id')
                            ->leftJoin('currency_exchange_rate as ce','pn.currency_id','=','ce.id')
                           ->where('pn.pay_channel_id',$v->id)
                            ->where('pn.status',1)
                            ->where('pc.status','=','enabled')
                            ->orderBy('pn.sort')
                           ->get(['pn.id','pn.currency_type','pn.pay_channel_id','pn.name','pn.img','pn.logo','pn.text','pn.min_money','pn.max_money','pn.rechage_money','pn.coin_type','pc.type','ce.exchange_rate','ce.alias','pc.id as pay_cid'])
                           ->toArray();

                if(!empty($channel)){
                    foreach($channel as $key=>$value){
                        if ($value->type == 'kpay') {
                            $is_kpay = true;
                            $pay_cid = $value->pay_cid;
                        }
                        $value->pay_type='';
                        if(!empty($value->rechage_money)){
                            $rechage_money=json_decode($value->rechage_money,true);
                            ksort($rechage_money);
                            $value->rechage_money = $rechage_money;
                        }else{
                            $value->rechage_money = null;
                        }
                        if (!in_array($value->id, $use_pay_list)) {
                            unset($channel[$key]);
                        }
                        $value->logo = showImageUrl($value->logo);
                        $value->img = showImageUrl($value->img);
                    }
                }
                $v->channel = array_values($channel);
                $v->logo = showImageUrl($v->logo);
                $v->img = showImageUrl($v->img);
                $v->guide_url = showImageUrl($v->guide_url) ?? "";
                $v->is_auto = in_array($v->type, ['autotopup', 'ouspay', 'yespay']);
            }
        }
        $kpay_pending_num = 0;
        $kpay_trade_no = '';
        if ($is_kpay) {
            $kpay_funds_deposit = DB::table('funds_deposit')
                ->where('user_id',$this->auth->getUserId())
                ->where('deposit_type',$pay_cid)
                ->where('status','pending')
                ->where('is_upload_cert',0)
                ->where('created','>',date('Y-m-d H:i:s',time() - 3600*2))
                ->first(['trade_no']);
            if ($kpay_funds_deposit) {
                $kpay_pending_num = 1;
                $kpay_trade_no = $kpay_funds_deposit->trade_no;
            }
        }

        return $this->lang->set(0, [], $res,['kpay_pending_num'=>$kpay_pending_num,'trade_no'=>$kpay_trade_no]);
    }
};