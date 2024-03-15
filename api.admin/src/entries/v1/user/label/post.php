<?php

use Logic\Admin\BaseController;
use lib\validate\BaseValidate;
use Logic\Admin\Log;

return new class() extends BaseController
{
//    const STATE       = \API::DRAFT;
    const TITLE = '新增会员标签';
    const DESCRIPTION = '会员标签--新增';
    
    const QUERY = [];
    
    const STATEs = [
//        \Las\Utils\ErrorCode::DATA_EXIST => '重复标签'
    ];
    const PARAMS = [
        'name' => 'string() #标签名称',
        'desc' => 'string() #描述',
    ];
    const SCHEMAS = [
    ];
    protected $beforeActionList = [
        'verifyToken',
        'authorize'
    ];

    /**
     * @param null $id 标签id，新增时为空
     * @return bool|mixed
     */
    public function run($id = null)
    {
        $params = $this->request->getParams();

        (new BaseValidate(
            [
            'desc' => 'require|max:250',
            'name' => 'require|max:50|unique:label,title'
            ],
            [],
            [
                'desc' => '描述',
                'name' => '标签名称'
            ]
        ))->paramsCheck('', $this->request, $this->response);

        $admin_uid = $this->playLoad['uid'];
        $res = DB::table('label')->insert(['title' => $params['name'], 'content' => $params['desc'], 'admin_uid' => $admin_uid]);

        if ($res !== false) {
            /*============================日志操作代码================================*/
            $sta = $res !== false ? 1 : 0;
            (new Log($this->ci))->create(null, null, Log::MODULE_USER, '会员标签', '会员标签', '新增会员标签', $sta, "新增会员标签：{$params['name']}");
            /*============================================================*/
            return $this->lang->set(0);
        }
        return $this->lang->set(-2);

    }
};
