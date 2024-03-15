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

    public function run($id = null)
    {
        $data = $this->request->getParams();
        unset($data['s']);

        $v = [
            'name'   => 'require',
            'bag_url' => 'require',
            'version' => 'require',
            'type'   => 'require',
            'status' => 'in:0,1',
            'update_type' => 'require|in:1,2'
        ];
        //!$id && $v['bound_id'] = 'require';
        $validate = new \lib\validate\BaseValidate($v);
        $validate->paramsCheck('', $this->request, $this->response);

        // ios不能设置为增量更新
        if ($data['type'] == 2 && $data['update_type'] == 1) {
            return $this->lang->set(10013, ['update_type']);
        }

        $status = [
            '0' => '正常',
            '1' => '停用',
        ];

        $data['bag_url'] = isset($data['bag_url']) && !empty($data['bag_url']) ? replaceImageUrl($data['bag_url']) : '';
        $data['icon_url'] = isset($data['icon_url']) && !empty($data['icon_url']) ? replaceImageUrl($data['icon_url']) : '';

        $generateChannel = true; // 是否更新渠道包
        if ($id) {
            /*============================日志操作代码================================*/
            $info = \DB::table('app_package')->find($id);

            $str = "";
            foreach($data as $key => $val){
                if($val != $info->$key){
                    $str = $str."[{$info->$key}]更改为[{$val}],";
                }
            }
            $type="修改";
            $data['updated'] = date('Y-m-d H:i:s');

            $lastId = \DB::table('app_package')
                ->where(['status' => 0, 'type' => $data['type'], 'update_type' => 2])
                ->orderBy('created','DESC')->value('id');

            // 判断是否更新版本号并且为最新的一条
            $generateChannel = $info->version != $data['version'] && $lastId == $id;

            /*============================================================*/

            $re = \DB::table('app_package')->where('id', $id)->update($data);
        } else {  //添加
            $re = \DB::table('app_package')->insertGetId($data);
            /*============================日志操作代码================================*/
            $type ="新增";
            $str  = json_encode($data);
            /*============================================================*/
        }

        if ($generateChannel) {
            $channelList = \DB::table('channel_download')->where('is_delete', 0)->get();
            // 批量生成渠道包
            (new \Logic\Lottery\Package($this->ci))->batchGenerateChannelPackage(showImageUrl($data['bag_url']), $channelList, $data['type']);
        }

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
