<?php

use Logic\Admin\BaseController;
use Logic\Admin\Log;

return new class() extends BaseController {
    const STATE = '';
    const TITLE = '修改 支付渠道描述';
    const DESCRIPTION = '';

    const QUERY = [];

    const PARAMS = [
        'id'     => 'int(require)',#A,
        'title'  => 'string',#前端显示title,
        'desc'   => 'string',#描述
        'status' => 'int()#停用0，启用1',
    ];
    const SCHEMAS = [];

    //前置方法
    protected $beforeActionList = [
        'verifyToken', 'authorize',
    ];

    public function run($id) {
        $this->checkID($id);
        $data = $this->request->getParams();
        $validate = new \lib\validate\BaseValidate([
            'status' => 'in:0,1',
        ]);
        $validate->paramsCheck('', $this->request, $this->response);
        try {

            /*================================日志操作代码=================================*/
            $datas = DB::table('funds_channel')
                       ->where('id', '=', $id)
                       ->get()
                       ->first();
            $datas = (array)$datas;
            /*==================================================================================*/
            $res = \DB::table('funds_channel')
                      ->where('id', '=', $id)
                      ->update($data);

            /*================================日志操作代码=================================*/
            $sta_str = [
                '0' => '停用',
                '1' => '启用',
            ];
            $sta = $res !== false ? 1 : 0;
            if (isset($data['title']) && $data['title'] != null) {
                $str = "支付类型:{$datas['desc']}/描述[{$datas['title']}]更改为[{$data['title']}]";
                $type_str = '编辑';
            } else {
                $str = "支付类型:{$datas['desc']}";
                $type_str = $sta_str[$data['status']];
            }
            (new Log($this->ci))->create(null, null, Log::MODULE_CASH, '收款账号', '线下描述', $type_str, $sta, $str);
            /*==================================================================================*/

            return $this->lang->set(0);
        } catch (\Exception $e) {
            return $this->lang->set(-2);
        }
    }

};
