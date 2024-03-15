<?php
use Logic\Admin\BaseController;
return new class() extends BaseController
{
    const STATE       = '';
    const TITLE       = '支付渠道';
    const DESCRIPTION = '支付渠道详情信息';

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
        $data=DB::table('pay_channel')->orderBy('sort')->get()->forPage($page,$size)->toArray();
        $attributes['total'] = DB::table('pay_channel')->count();

        $attributes['size'] = $size;
        $attributes['number'] = $page;
        if(!empty($data)){
            foreach($data as $v){
                if(!empty($v->rechage_money)){
                    $rechage_money=json_decode($v->rechage_money,true);
                    ksort($rechage_money);
                    $v->rechage_money=$rechage_money;
                }
                $v->channel_num=DB::table('payment_channel')->where('pay_channel_id',$v->id)->count();
                $v->img = showImageUrl($v->img);
                $v->logo = showImageUrl($v->logo);
                $v->guide_url = showImageUrl($v->guide_url);
            }
        }

        return $this->lang->set(0, [], $data, $attributes);
    }

};
