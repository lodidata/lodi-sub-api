<?php

use Logic\Admin\Log;
use Logic\Admin\BaseController;
use Model\ChannelDownload;

return new class() extends BaseController {
    const TITLE = '批量编辑渠道';
    const DESCRIPTION = '批量编辑超级签';


    //前置方法
    protected $beforeActionList = [
        'verifyToken', 'authorize'
    ];

    public function run()
    {
        $params = $this->request->getParams();
        //判断参数是否有误
        if (empty($params['ids'])) {
            return $this->lang->set(886, ['id组不能为空']);
        }
        if (empty($params['super_label'])) {
            return $this->lang->set(886, ['超级签不能为空']);
        }
        $idArr = explode(',', $params['ids']);
        if ($idArr) {
            ChannelDownload::query()->whereIn('id', $idArr)->update(['super_label' => $params['super_label']]);
            (new Log($this->ci))->create(
                null,
                null,
                Log::MODULE_USER,
                '渠道管理',
                self::TITLE,
                self::DESCRIPTION,
                1,
                '社区ID组：' . $params['ids']
            );
        }
        return $this->lang->set(0);
    }


};
