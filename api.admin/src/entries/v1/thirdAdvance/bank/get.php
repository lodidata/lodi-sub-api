<?php

use Logic\Admin\BaseController;
use lib\validate\BaseValidate;

return new class() extends BaseController
{
    const TITLE = '第三方代付支持银行';
    const DESCRIPTION = '第三方代付';
    
    const QUERY = [

    ];
    
    const PARAMS = [];
    const SCHEMAS = [
        [
            "code" => "BOC#银行代号",
            "name" => "中国银行#银行名",
            "logo" => "//res.lebodev.com/1/web/BC.png#银行Logo地址"
        ]
    ];
//前置方法
    protected $beforeActionList = [
        'verifyToken', 'authorize'
    ];

    public function run($id = null)
    {
        $this->checkID($id);
        $result = [];
        $bankJson = \DB::table('transfer_config')->where('id', $id)->value('bank_list');
        if ($bankJson) {
            $bankArray = json_decode($bankJson, true);
            $bankStr   = '"' . implode('","', array_values($bankArray)) . '"';
            $data      = \Model\Bank::select(['code', 'h5_logo as logo'])->whereRaw("code IN ({$bankStr})")->get()->toArray();
            $bankArray = array_flip($bankArray);

            if ($data) {
                foreach ($data as $k => $val) {
                    $val['code'] = $bankArray[$val['code']];
                    $val['name'] = $this->lang->text($val['code']);
                    $result[]    = $val;
                }
            }
        }

        return $result;
    }

};
