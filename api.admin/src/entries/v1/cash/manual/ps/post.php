<?php

use Logic\Admin\BaseController;
use Logic\Admin\Log;

return new class() extends BaseController
{
    const STATE = '';
    const TITLE = '手动主钱包、子钱包互转';
    const DESCRIPTION = '手动子转主钱包/手动主转子钱包';
    
    const QUERY = [];
    
    const PARAMS = [
        'wid' => 'int(required) 主钱包id',
        'sid' => 'int(required) 子钱包id',
        'amount' => 'int(required) #变动金额',
        'uid' => 'int(required) #发生资金转换的用户user id',
        'type' => 'enum[1,2](required) #变动方向，1 主钱包转子钱包，2 子钱包转主钱包',
        'status' => 'enum[1,2](required) #状态，1 发生实际变动，2 只补单（只增加记录）',
        'memo' => 'string() #备注',
    ];
    const SCHEMAS = [
    ];

    const STATEs = [
    ];

    //前置方法
   protected $beforeActionList = [
       'verifyToken', 'authorize',
   ];

    public function run()
    {
        $params = $this->request->getParams();

        $validate = new \lib\validate\BaseValidate([
            'uid' => 'require|isPositiveInteger',
            'type' => 'in:1,0',
            'game_type', 'require'
        ]);

        $validate->paramsCheck('', $this->request, $this->response);


        $gameClass = \Logic\GameApi\GameApi::getApi($params['game_type'], $params['uid']);
        if ($params['type'] == '1') {
            $res = $gameClass->rollInThird();
        } else {
            $res = $gameClass->rollOutThird();
        }


        $sta = $res['msg'] == 'success' ? 1 : 0;

        /*============================日志操作代码================================*/
        $user = DB::table('user')
            ->select('name')
            ->where('id', '=', $params['uid'])
            ->get()
            ->first();
        $user = (array)$user;
        $type_str = [
            '1' => '主钱包转子钱包',
            '0' => '子钱包转主钱包'
        ];


        (new Log($this->ci))->create($params['uid'], $user['name'], Log::MODULE_CASH, '余额转换', '指定某个第三方', $type_str[$params['type']], $sta, "金额:" . ($params['amount'] / 100) . "元");
        /*============================================================*/

        return $this->lang->set(0,[],$res);
    }
};
