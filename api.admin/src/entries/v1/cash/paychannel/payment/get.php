<?php
use Logic\Admin\BaseController;
return new class() extends BaseController
{
    const STATE       = '';
    const TITLE       = '支付通道';
    const DESCRIPTION = '支付通道信息';

    const QUERY       = [];

    const PARAMS      = [];
    const SCHEMAS     = [];
    //前置方法
    protected $beforeActionList = [
        'verifyToken','authorize'
    ];
    public function run()
    {
        $page = $this->request->getParam('page', 1);
        $size = $this->request->getParam('page_size', 10);
        $pay_channel_id=$this->request->getParam('pay_channel_id');
        $name=$this->request->getParam('name');
        $pay_config_id=$this->request->getParam('pay_config_id');
        $status=$this->request->getParam('status',-1);


        $query=DB::table('payment_channel as a')
                ->leftJoin('pay_channel as b','a.pay_channel_id','=','b.id')
                ->leftJoin('pay_config as c','a.pay_config_id','=','c.id')
                ->select(['a.id','a.name','a.currency_type','a.coin_type','a.img','a.text','a.status','a.min_money','a.max_money','a.money_day_stop','a.sort','a.logo',
                    'a.money_stop','a.rechage_money','a.give','a.give_protion','a.give_recharge_dml','a.give_lottery_dml','a.remark',
                    'b.name as channel_name','c.type as pay_config_type','a.type as show_type','c.params']);

        $pay_channel_id && $query->where('a.pay_channel_id', '=', $pay_channel_id);
        $name && $query->where('a.name', '=', $name);
        $pay_config_id && $query->where('a.pay_config_id', '=', $pay_config_id);
        if($status != -1){
            $query->where('a.status', '=', $status);
        }

        $count = clone $query;
        $attributes['total'] = $count->count();

        $attributes['size'] = $size;
        $attributes['number'] = $page;

        $data=$query->orderBy('a.sort')->forPage($page,$size)->get()->toArray();

        if(!empty($data)){
            foreach($data as $v){
                if(!empty($v->rechage_money)){
                    $rechage_money=json_decode($v->rechage_money,true);
                    ksort($rechage_money);
                    $v->rechage_money=array_values($rechage_money);
                    $v->img = showImageUrl($v->img);
                    $v->logo = showImageUrl($v->logo);
                }
                $v->level=DB::table('level_payment')->where('payment_id',$v->id)->pluck('level_id')->toArray();
                if(!empty($v->params)){
                    $params=json_decode($v->params,true);
                    if(isset($params['USDT'])){
                        $v->coin_arr  = array_keys($params['USDT']);
                    }

                }
                unset($v->params);
            }
        }

        return $this->lang->set(0, [], $data, $attributes);
    }

};
