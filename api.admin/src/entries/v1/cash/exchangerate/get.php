<?php
use Logic\Admin\BaseController;
use Logic\Set\SystemConfig;
return new class() extends BaseController
{
    const STATE       = '';
    const TITLE       = '货币汇率';
    const DESCRIPTION = '货币汇率列表';

    const QUERY       = [
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
    const PARAMS      = [];
    const SCHEMAS     = [

    ];
    //前置方法
    protected $beforeActionList = [
        'verifyToken','authorize'
    ];
    public function run()
    {
        $page = $this->request->getParam('page') ?? 1;
        $size = $this->request->getParam('page_size') ?? 10;
        $query=DB::table('currency_exchange_rate');

        $attributes['total']  = $query->count();
        $data                 = $query->forPage($page,$size)
                                ->get()
                                ->toArray();
        $config = \Model\Admin\SystemConfig::where('key','currency_id')->first();
        $newPrototalnum = array_column($data, null, 'id');
        if (isset($newPrototalnum[$config->value])){
            $attributes['currency_id'] = $newPrototalnum[$config->value]->name.'('.$newPrototalnum[$config->value]->alias.')';
        }
        $attributes['size']   = $size;
        $attributes['number'] = $page;
        return $this->lang->set(0, [], $data, $attributes);

    }

};
