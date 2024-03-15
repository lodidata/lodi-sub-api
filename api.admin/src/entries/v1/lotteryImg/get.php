<?php
use Logic\Admin\BaseController;
return new class() extends BaseController
{
    const TITLE       = '彩种信息';
    const QUERY       = [
        'id' => 'int #彩种ID',
        'pid' => 'int #彩种父ID'
    ];
    const SCHEMAS     = [
        [
            "alias"=>"XYRB",
            "all_bet_max"=>"100000000",
            "code"=>"XYRB",
            "created"=>"2017-02-07 14:23:10",
            "h5_pic"=>"null",
            "id"=>" 1",
            "index_c_img"=>" null",
            "index_f_img"=>"null",
            "is_hot"=>" 0",
            "name"=>" 幸运28",
            "per_bet_max"=>" 1000000",
            "pic"=>"/static/images/lottery/xyrb.png",
            "open_img"=>"/static/images/lottery/xyrb.png",
            "buy_c_img"=>"/static/images/lottery/xyrb.png",
            "buy_f_img"=>"/static/images/lottery/xyrb.png",
            "pid"=>" 0",
            "sort"=>" 0",
            "state"=>"fast,auto,enabled,chat",
            "switch"=>"standard,fast",
            "type"=>"low",
            "updated"=>"2018-07-12 10:45:35"
        ]
    ];
//前置方法
    protected $beforeActionList = [
         'verifyToken','authorize'
    ];

    public function run()
    {
        $id = $this->request->getParam('id');
        $pid = $this->request->getParam('pid');
        $query = DB::table('lottery');
        $id && $query->where('id',$id);
        $pid && $query->where('pid',$pid);
        $query->whereRaw("FIND_IN_SET('enabled', state)");
        return $query->get([
            'id',
            'pid',
            'alias',
            'name',
            'all_bet_max',
            'code',
            'h5_pic',
            'index_c_img',
            'index_f_img',
            'is_hot',
            'switch',
            'state',
            'sort',
            'type',
            'pic',
            'open_img',
            'buy_f_img',
            'buy_c_img',
            'all_bet_max',
            'per_bet_max',
            'created',
            'updated',
        ])->toArray();

    }

};
