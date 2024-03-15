<?php

use Logic\Admin\BaseController;
use Logic\Admin\Log;

return new class() extends BaseController
{
    const STATE = '';
    const TITLE = '修改/新增APP Android包相关信息';
    const DESCRIPTION = '';
    
    const QUERY = [];
    
    const PARAMS = [
        'channel_id' => 'string(, 30)',
        'name' => 'string(require, 50)',#APP Name,
        'status' => 'int()#审核不通过0，通过1',
    ];
    const SCHEMAS = [];
    //前置方法
    protected $beforeActionList = [
        'verifyToken', 'authorize'
    ];

    public function run($id = null)
    {
        $data = $this->request->getParams();
        $channel = $this->request->getParam('channel');
        $v = [
            'name' => 'require',
            'status' => 'in:0,1,9',
        ];
        !$id && $v['channel_id'] = 'require';
        $validate = new \lib\validate\BaseValidate($v);
        $validate->paramsCheck('', $this->request, $this->response);

        //审核结果
        $status = [
            '0' => '不通过',
            '1' => '通过',
            '9'=>'抓取IP'
        ];

        //强制更新
        $force = [
            '1' => '启用',
            '2' => '停用',
        ];

        if(in_array($data['status'], [1, 9])){//审核结果通过或抓取IP
            $data['force_update'] = 2;
            $data['name_update'] = '';
            $data['url_update'] = '';
        }else{//审核结果不通过
            if(empty($data['force_update'])){
                return $this->lang->set(886,['请选择是否强制更新']);
            }
            if($data['force_update'] == 2){//强制更新停用时
                $data['name_update'] = '';
                $data['url_update'] = '';
            }else{
                if(empty($data['name_update']) || empty($data['url_update'])){
                    return $this->lang->set(886,['强制更新启用时，更新包名称或链接必填']);
                }
                if(!filter_var($data['url_update'], FILTER_VALIDATE_URL)){
                    return $this->lang->set(886,['强制更新启用时，更新包链接不合法']);
                }
            }
        }

        if ($id) {
            //更新
            $data['update_date'] = date("Y-m-d H:i:s");
            $data['update_uid'] = $this->playLoad['uid'];

            /*============================日志操作代码================================*/
            $info = \DB::table('app_bag')->find($id);
            $type = "修改";

            $str="渠道ID：{$info->channel_id}";

            if($data['name']!=$info->name){
                $str = $str."/App名称：[{$info->name}]更改为[{$data['name']}]";
            }
            if($status[$info->status]!=$status[$data['status']]){
                $str = $str."/审核结果：[{$status[$info->status]}]更改为：[{$status[$data['status']]}]";
            }
            if($force[$info->force_update]!=$force[$data['force_update']]){
                $str = $str."/强制更新：[{$force[$info->force_update]}]更改为：[{$force[$data['force_update']]}]";
            }
            if($info->name_update != $data['name_update']){
                $str = $str."/更新包名称：[{$info->name_update}]更改为：[{$data['name_update']}]";
            }
            if($info->url_update != $data['url_update']){
                $str = $str."/更新包链接：[{$info->url_update}]更改为：[{$data['url_update']}]";
            }

            /*============================================================*/
            $data['status_rule'] = json_encode($data['status_rule']);
            $re = \DB::table('app_bag')->where('id', $id)->update($data);
            if ($channel) {
                $c = ['channel' => $channel];
                \DB::table('app_black_ip')->where('channel', '=', $channel)->update($c);
                \DB::table('app_black_area')->where('channel', '=', $channel)->update($c);
            }

        } else {  //添加
            //channel_id必须唯一
            if (\DB::table('app_bag')->where('channel_id', '=', $data['channel_id'])
                ->where('delete', '=', 0)->count())
                return $this->lang->set(10406,['包']);
            $c = substr($data['channel_id'], strrpos($data['channel_id'], '_') + 1) ?? substr($data['channel_id'], strrpos($data['channel_id'], '-') + 1);
            $channel = $channel ? $channel : $c;//如果chanel不存在
            $data['type'] = 3;
            $data['channel'] = $channel;
            $data['create_date'] = $data['update_date'] = date("Y-m-d H:i:s");
            $data['create_uid'] = $data['update_uid'] = $this->playLoad['uid'];
            $re = \DB::table('app_bag')->insertGetId($data);//获取插入的id

            /*============================日志操作代码================================*/
            $type = "新增";
            $str = "渠道ID：{$data['channel_id']}/App名称：{$data['name']}/审核结果：[{$status[$data['status']]}]/强制更新：[{$force[$data['force_update']]}]/更新包名称：[{$data['name_update']}]/更新包链接：{$data['url_update']}";
            /*============================================================*/
        }
        if ($re) {
            /*============================日志操作代码================================*/
            $sta = $re !== false ? 1 : 0;
            (new Log($this->ci))->create(null , null, Log::MODULE_APP, 'Andriod上架包', 'Andriod上架包', $type, $sta, $str);
            /*============================================================*/
            return $this->lang->set(0);
        }

        return $this->lang->set(-2);
    }
};
