<?php

use Logic\Admin\Log;
use Logic\Admin\Notice as noticeLogic;
use Logic\Admin\BaseController;

return new class() extends BaseController
{
//    const STATE       = \API::DRAFT;
    const TITLE = '启用停用公告';
    const DESCRIPTION = '';
    
    const QUERY = [

    ];
    
    const PARAMS = [
        'id' => 'int(required) #id',
        'status' => 'int(required) #status',
    ];
    const SCHEMAS = [];

    protected $beforeActionList = [
        'verifyToken',
        'authorize'
    ];

    public $id;

    public function run($id = '')
    {
        $this->checkID($id);

        $status = $this->request->getParam('status');


        $notice = \Model\Admin\Notice::find($id);
        $notice->logs_type = '停用\启用';
        $notice->status = $status;
        $res = $notice->save();
        if (!$res) {
            return $this->lang->set(-2);
        }
        return $this->lang->set(0);
    }
};
