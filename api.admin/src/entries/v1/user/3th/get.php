<?php

use Model\Game3th;
use Logic\Admin\BaseController;

return new class() extends BaseController {
    const TITLE = '第三方会员列表';
    const DESCRIPTION = '';
    
    const QUERY = [

    ];
    
    const PARAMS = [];
    const SCHEMAS = [];
    //前置方法
    protected $beforeActionList = [
        'verifyToken', 'authorize',
    ];

    public function run() {

        $params = $this->request->getParams();
        $data = Game3th::getRecords($params, $params['page'], $params['page_size']);

        $attributes['total'] = $data['count'][0]->cnt;
        $attributes['number'] = $params['page'];
        $attributes['size'] = $params['page_size'];
        $data = $data['data'];

        return $this->lang->set(0, [], $data, $attributes);
    }

};
