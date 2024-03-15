<?php

use Logic\Admin\BaseController;
use Logic\Admin\Log;

return new class() extends BaseController {
    const MODULE = '第三方支付';
    const TITLE = '批量修改状态';

    protected $beforeActionList = [
        'verifyToken',
        'authorize',
    ];

    public function run()
    {
        $ids = $this->request->getParam('ids', '');
        $status = $this->request->getParam('status');

        if (empty($ids)) {
            return $this->lang->set(10010);
        }
        if (!is_numeric($status) || !in_array($status, [0, 1])) {
            return $this->lang->set(10010);
        }

        $id_arr = explode(',', $ids);
        DB::table('payment_channel')->whereIn('id', $id_arr)->update(['status' => $status]);
        $statusArr = ['停用', '启用'];
        $str = 'id:【' . $ids . '】批量更新状态为【' . $statusArr[$status] . '】';
        (new Log($this->ci))->create(null, null, Log::MODULE_CASH, self::MODULE, self::TITLE, self::TITLE, 1, $str);
        return $this->lang->set(0);
    }

};
