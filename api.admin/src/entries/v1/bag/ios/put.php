<?php

use Logic\Admin\BaseController;
use Logic\Admin\Log;

return new class() extends BaseController
{
    const STATE = '';
    const TITLE = '修改/新增APP IOS包相关信息';
    const DESCRIPTION = '';
    
    const QUERY = [];
    
    const PARAMS = [
        'bound_id' => 'string(, 30)',
        'name' => 'string(require, 50)',#APP Name,
        'ver' => 'string(require, 150)',#版本
        'status' => 'int()#审核不通过0，通过1',
    ];
    const SCHEMAS = [
    ];

    //前置方法
    protected $beforeActionList = [
        'verifyToken', 'authorize'
    ];

    public function run($id = null)
    {
        $data = $this->request->getParams();
        $v = [
            'name' => 'require',
            'status' => 'in:0,1,9',
        ];
        !$id && $v['bound_id'] = 'require';
        $validate = new \lib\validate\BaseValidate($v);
        $validate->paramsCheck('', $this->request, $this->response);
        if (isset($data['ver'])) {
            $data['url'] = $data['ver'];
            unset($data['ver']);
        }
        $status = [
            '0' => '不通过',
            '1' => '通过',
            '9'=>'抓取IP'
        ];
        if ($id) {
            //更新
            $data['update_date'] = date("Y-m-d H:i:s");
            $data['update_uid'] = $this->playLoad['uid'];

            /*============================日志操作代码================================*/
            $info = \DB::table('app_bag')->find($id);

            $str="boundID:{$info->bound_id}/Ver:{$info->url}";
            if($info->name!=$data['name']){
                $str=$str."/APP名:[{$info->name}]更改为[{$data['name']}]";
            }
            if($status[$info->status]!=$status[$data['status']]){
                $str=$str."/审核结果[{$status[$info->status]}]更改为[{$status[$data['status']]}]";
            }
            $type="修改";
            /*============================================================*/

            $re = \DB::table('app_bag')->where('id', $id)->update($data);
        } else {  //添加
            if (\DB::table('app_bag')->where('bound_id', '=', $data['bound_id'])
                ->where('url', '=', $data['url'])
                ->where('delete', '=', 0)->count())
                return $this->lang->set(10406);
            $data['type'] = 1;
            $data['create_date'] = $data['update_date'] = date("Y-m-d H:i:s");
            $data['create_uid'] = $data['update_uid'] = $this->playLoad['uid'];
            $re = \DB::table('app_bag')->insertGetId($data);

            /*============================日志操作代码================================*/
            $type="新增";
            $str = "boundID:{$data['bound_id']}/Ver:{$data['url']}/APP名:{$data['name']}/审核结果[{$status[$data['status']]}]";
            /*============================================================*/
        }
        if ($re) {
            /*============================日志操作代码================================*/
            $sta = $re !== false ? 1 : 0;
            (new Log($this->ci))->create(null, null, Log::MODULE_APP, 'IOS马甲包', 'IOS马甲包', $type, $sta, $str);
            /*============================================================*/
            return $this->lang->set(0);
        }

        return $this->lang->set(-2);
    }
};
