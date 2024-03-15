<?php

use Logic\Admin\Notice as noticeLogic;
use Logic\Admin\BaseController;
return new class() extends BaseController
{
    const TITLE       = '房间公告列表';
    const DESCRIPTION = '房间公告列表';
    

    const QUERY       = [

    ];
    const SCHEMAS = [];

    public function run()
    {
        $params = $this->request->getParams();
        $roomNoticeData = (new noticeLogic($this->ci))->getRoomNoticeList($params['page'],$params['page_size']);

        return $roomNoticeData;

    }

};
