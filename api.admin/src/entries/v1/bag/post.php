<?php

use Logic\Admin\BaseController;
use Logic\Admin\Log;

return new class() extends BaseController
{
    const STATE = '';
    const TITLE = '修改/新增APP 包相关信息';
    const DESCRIPTION = '';
    
    const QUERY = [];
    
    const PARAMS = [
        //'bound_id' => 'string(, 30)',
        'name' => 'string(require, 50)',#APP Name,
        'url' => 'string(require, 200)', #下载链接,
        //'ver' => 'string(require, 150)',#版本
        'type' => 'int()# 1：IOS马甲包，2：IOS企业包，3：Android上架包',
        'status' => 'int()#审核不通过0，通过1',
    ];
    const SCHEMAS = [
    ];

    //前置方法
    protected $beforeActionList = [
        'verifyToken', 'authorize'
    ];

    public function run($id)
    {
        $data = $this->request->getParams();
        unset($data['s']);

        $v = [
            'status' => 'in:0,1',
        ];
        $validate = new \lib\validate\BaseValidate($v);
        $validate->paramsCheck('', $this->request, $this->response);

        $status = $data['status'];

        if(!is_numeric($id) || $id <= 0){
            return $this->lang->set(-2);
        }

        /*============================日志操作代码================================*/
        $str = "";
        if($status == 0){
            $str = $str."包ID：".$id.",状态修改为正常";
        }else{
            $str = $str."包ID：".$id.",状态修改为停用";
        }
        $type="修改";
        $update = [
          'status' => $status,
          'updated' => date('Y-m-d H:i:s')
        ];
        /*============================================================*/

        $re = \DB::table('app_package')->where('id', $id)->update($update);

        if ($re) {
            /*============================日志操作代码================================*/
            $sta = 1;
            (new Log($this->ci))->create(null, null, Log::MODULE_APP, '安装包', '安装包', $type, $sta, $str);
            /*============================================================*/
            return $this->lang->set(0);
        }

        return $this->lang->set(-2);
    }
};
