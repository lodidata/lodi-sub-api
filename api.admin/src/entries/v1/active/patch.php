<?php

use lib\validate\BaseValidate;
use Logic\Admin\Log;
use Logic\Admin\Notice as noticeLogic;
use Logic\Admin\BaseController;
use lib\validate\admin\NoticeValidate;

/**
 * 活动停用
 */
return new class() extends BaseController {
    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize'
    ];


    public function run()
    {
        $params = $this->request->getParams();

        (new BaseValidate([
            'id' => 'require|isPositiveInteger',
            'status' => 'require'
        ],
            [], ['id' => '活动id', 'status' => '状态']
        ))->paramsCheck('', $this->request, $this->response);

        $result = DB::table('active')
            ->where('id', '=', $params['id'])
            ->update([
                'status' => $params['status']
            ]);

        /*============================日志操作代码================================*/
        $sta = $result !== false ? 1 : 0;

        $type = $params['status'] == 'disabled' ? '停用' : '启用';

        $info = DB::table('active')
            ->leftJoin('active_template', 'active_template.id', '=', 'type_id')
            ->select(['active_template.name as type_name', 'title', 'active.state as state'])
            ->where('active.id', '=', $params['id'])
            ->get()
            ->first();
        if (!$info) {
            return $this->lang->set(-2);
        }

        $str = "活动名称:{$info->title}/活动类型:{$info->type_name}";

        (new Log($this->ci))->create(null, null, Log::MODULE_ACTIVE, '活动列表', '活动列表', $type, $sta, $str);
        /*============================================================*/
        if ($result !== false) {
            // 停用活动时需要将当日该活动用户申请次数重置
            if ($params['status'] == 'disabled' && $info->state == 'apply') {
                $res = DB::table('active_apply')
                    ->where(['active_id' => $params['id'], ['created', '>=', date("Y-m-d") . " 00:00:00"], ['created', '<=', date("Y-m-d") . " 23:59:59"]])
                    ->update([
                        'apply_count_status' => 0
                    ]);
            }
            return $this->lang->set(0);
        }
        return $this->lang->set(-2);
    }


};
