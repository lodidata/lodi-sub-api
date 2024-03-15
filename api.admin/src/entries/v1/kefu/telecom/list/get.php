<?php
use Logic\Admin\BaseController;

return new class() extends BaseController {
    const TITLE       = '电访后台';
    const DESCRIPTION = '电访后台';
    const PARAMS       = [
    ];
    const SCHEMAS     = [

    ];
    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize',
    ];
    public function run() {
        $kefu_id = $this->request->getParam('kefu_id',0);
        if(!is_numeric($kefu_id) || $kefu_id <= 0){
            return createRsponse($this->response, 200, -2, '参数有误');
        }
        $list = DB::connection('slave')->table('kefu_telecom')
            ->select('id', 'name')->where('kefu_id', $kefu_id)
            ->orderBy('created','desc')
            ->get()->toArray();
        $data = [];
        foreach($list as $val){
            $data[] = [
                'id'   => $val->id,
                'name' => $val->name,
            ];
        }
        $attr = [];

        return $this->lang->set(0, '', $data, $attr);
    }
};