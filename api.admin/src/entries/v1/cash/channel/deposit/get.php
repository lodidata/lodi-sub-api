<?php
/**
 * Created by PhpStorm.
 * User: ben
 * Date: 2018/8/16
 * Time: 17:46
 */
use Logic\Admin\BaseController;
use lib\validate\BaseValidate;

return new class() extends BaseController
{
    const STATE = '';

    const TITLE = '资金流水记录';

    //前置方法
    protected $beforeActionList = [
        'verifyToken', 'authorize',
    ];

    public function run()
    {
        (new BaseValidate(
            [
                'thirdparty_id' => 'require',
                'start_time' => 'require|dateFormat:Y-m-d',
                'end_time' => 'require|dateFormat:Y-m-d',

            ], [], [
                'thirdparty_id' => '第三方支付ID',
                'start_time' => '开始时间',
                'end_time' => '结束时间',
            ]
        ))->paramsCheck('', $this->request, $this->response);

        $params = $this->request->getParams();

        $query = DB::connection('slave')->table('funds_deposit as fd')
            ->leftJoin('user', 'fd.user_id', '=', 'user.id')
            ->where('user.tags', '!=', 4)
            ->where('fd.status','paid')
            ->whereRaw("find_in_set('online',fd.state)")
            ->where('fd.pay_bank_id',$params['thirdparty_id'])
            ->where('fd.recharge_time','>=',$params['start_time'])
            ->where('fd.recharge_time','<=',$params['end_time'].' 23:59:59');

        $attributes['total'] =  $query->count();
        $attributes['number'] = $params['page'];
        $attributes['size'] = $params['page_size'];

        $data = $query
            ->selectRaw('fd.id,fd.user_id,fd.name as username,
            CONCAT(fd.trade_no,"") AS deal_number,
            fd.money as deal_money,fd.recharge_time as created,fd.memo')
            ->forPage($params['page'],$params['page_size'])
            ->get()->toArray();
        foreach ($data as &$val){
            $val->balance = '-';
        }

        return $this->lang->set(0,[],$data,$attributes);



    }
};