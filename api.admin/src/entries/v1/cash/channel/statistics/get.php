<?php
use Logic\Admin\BaseController;
use lib\validate\BaseValidate;
return new class() extends BaseController
{
    const STATE       = '';
    const TITLE       = '渠道统计';
    const DESCRIPTION = '';
    
    const QUERY       = [
        'id' => 'int(, 30)',
        'type_id' => 'int(, 30)',
        'title' => 'string(require, 50)',#,
        'desc' => 'string(require, 50)',#,
        'status' => 'int()#停用，启用1',
    ];
    
    const PARAMS      = [];
    const SCHEMAS = [];

    //前置方法
    protected $beforeActionList = [
        'verifyToken', 'authorize',
    ];

    public function run()
    {
        (new BaseValidate(
            [
                'start_time'=>'require|dateFormat:Y-m-d',
                'end_time'=>'require|dateFormat:Y-m-d',
            ]
        ))->paramsCheck('',$this->request,$this->response);

        $params = $this->request->getParams();
//        $sql = "SELECT pay_bank_id as thirdparty_id,
//       receive_bank_info -> '$.pay' as thirdparty_name,
//       SUM(money) as money,
//       COUNT(*) as total,
//       COUNT(DISTINCT user_id) as users
//  FROM funds_deposit fd
// INNER JOIN `user` u
//    ON fd.user_id = u.id
// WHERE fd.recharge_time >= '2018-07-01'
//    AND fd.recharge_time < '2018-08-01'
//    AND FIND_IN_SET('online', fd.state) > 0
//    AND fd.status = 'paid'
//    AND u.tags NOT IN (4, 7)
// GROUP BY pay_bank_id";

        $data = DB::connection('slave')->table('funds_deposit as fd')
                        ->join('user as u','fd.user_id','=','u.id')
                        ->selectRaw("pay_bank_id as thirdparty_id,receive_bank_info -> '$.pay' as thirdparty_name,SUM(money) as money,COUNT(*) as total,COUNT(DISTINCT user_id) as users")
                        ->where('fd.recharge_time','>=',$params['start_time'])
                        ->where('fd.recharge_time','<=',$params['end_time'].' 23:59:59')
                        ->whereRaw("find_in_set('online',fd.state)")
                        ->where('fd.status','paid')
                        ->whereNotIn('u.tags',[4,7])
                        ->groupBy('pay_bank_id')
                        ->get()->toArray();
        //合计
        $sum = [
            'thirdparty_id'=>0,
            'thirdparty_name'=>'合计',
            'money'=>0,
            'total'=>0,
            'users'=>0,
        ];

        foreach ($data as $val){
            $sum['money'] +=  $val->money;
            $sum['total'] +=  $val->total;
            $sum['users'] +=  $val->users;
        }
        array_push($data,$sum);

        return $data;
    }
};
