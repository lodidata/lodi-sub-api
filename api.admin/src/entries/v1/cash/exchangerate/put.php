<?php

use Logic\Admin\BaseController;
use Logic\Admin\Log;

return new class() extends BaseController
{
    const TITLE       = '货币汇率';
    const DESCRIPTION = '货币汇率编辑';
    const QUERY       = [
        'id' => 'int() #）'
    ];

    const PARAMS      = [
        'id'              => 'int(required) #ID',
        'type'            => 'int(required) #货币类型(1.法定货币 2.数字货币)',
        'name'            => 'string(required) #货币简称',
        'alias'           => 'string(required) #简称',
        'exchange_rate'   => 'float() #汇率',
        'created'         => 'datetime #创建时间',
        'update'          => 'datetime #更新时间',
    ];
    const EXCHANGE_RATE_TYPE        = [
            '1' => '法定货币',
            '2' => '数字货币',
    ];
    const CN           = [
            'type'          => '货币类型',
            'name'          => '货币简称',
            'alias'         => '简称',
            'exchange_rate' => '汇率',
    ];
    const SCHEMAS     = [

    ];
    //前置方法
    protected $beforeActionList = [
        'verifyToken','authorize'
    ];

    public function run($id = '')
    {
        $this->checkID($id);

        $param = $this->request->getParams();
        $validate = new \lib\validate\BaseValidate([
            'type' => 'number',
            'exchange_rate' => 'number',
        ]);
        $validate->paramsCheck('', $this->request, $this->response);
        $model = \Model\Admin\CurrencyExchangeRate::find($id);
        $list = $model->toarray();

        $model->name          = $param['name'];
        $model->alias         = $param['alias'];
        $model->exchange_rate = $param['exchange_rate'];

        $model->save();




        /*--------操作日志代码-------*/
        $str = '';
        foreach ($list as  $key => $item){
             if (isset($param[$key]) && $param[$key] != $list[$key]) {
                $str .= self::CN[$key] ."：[" . ($list[$key]) . "]更改为[" . ($param[$key]) . "]---";
             }
        }
        (new Log($this->ci))->create(null, null, Log::MODULE_CASH, '货币汇率', '货币汇率', '编辑', 1, $str);
        return $this->lang->set(0);
    }

};
