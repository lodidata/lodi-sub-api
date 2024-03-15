<?php
/**
 * Created by PhpStorm.
 * User: ben
 * Date: 2018/3/27
 * Time: 15:14
 */
use Logic\Admin\Advert as advertLogic;
use Logic\Admin\BaseController;
use lib\validate\admin\TopConfigValidate;
use Logic\Admin\Log;

return new class() extends BaseController {

    const TITLE       = '新增/顶部悬浮配置';
    const DESCRIPTION = '顶部悬浮配置';
    const QUERY       = [];
    
    const PARAMS      = [
          'description' => 'string #文字描述',
          'title '      => 'string #按钮文本',
          'url'         => 'string #跳转链接',
          'logo_img'     => 'string #图片url',
          'download'    => 'int #下载开关 1 开 0 关',
    ];

    const SCHEMAS     = [
    ];

    //前置方法
    protected $beforeActionList = [
        'verifyToken','authorize'
    ];

    public function run() {


        (new TopConfigValidate())->paramsCheck('post',$this->request,$this->response);//参数校验,新增
        $data = $this->request->getParams();
        if (mb_strlen(strip_tags($data['description'])) > 500) return $this->lang->set(10615);
        $insert_list['download']    = $data['download'];
        $insert_list['url']         = $data['url'];
        $insert_list['title']       = $data['title'];
        $insert_list['description'] = mergeImageUrl($data['description']);
        $insert_list['logo_img']    = replaceImageUrl($data['logo_img']);
        $insert_list['jump_type']   = $data['jump_type'];
        $insert_list['created']     = date('Y-m-d H:i:s',time());
        $result = DB::table('top_config')->insert($insert_list);
        if (!$result) {
            return $this->lang->set(-2);
        }

        return $this->lang->set(0);

    }
};