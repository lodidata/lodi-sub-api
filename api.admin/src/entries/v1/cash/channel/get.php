<?php
use Logic\Admin\BaseController;
return new class() extends BaseController
{
    const STATE       = '';
    const TITLE       = '获取支付渠道描述列表';
    const DESCRIPTION = '';
    
    const QUERY       = [
        'id' => 'int(, 30)',
        'type_id' => 'int(, 30)',
        'title' => 'string(require, 50)',#,
        'desc' => 'string(require, 50)',#,
        'status' => 'int()#停用，启用1',
    ];
    
    const PARAMS      = [];
    const SCHEMAS     = [];
    public $show = ['online'=>'线上','offline'=>'线下'];
    public function run()
    {
        $data = \DB::connection('slave')->table('funds_channel')->orderBy('sort')->get()->toArray();
        foreach ($data as &$val){
            $val->type_name = $val->desc ?? '';
            $val->show_name = $this->show[$val->show] ?? '';
            $val->status_name = $val->status ? '启用' : '停用';
        }
        unset($val);
        return $data;
    }
};
