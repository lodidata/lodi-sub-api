<?php

use Logic\Admin\BaseController;
use lib\validate\admin\TopConfigValidate;

return new class() extends BaseController {

    const TITLE       = '修改/顶部悬浮配置';
    const DESCRIPTION = '顶部悬浮配置';
    const QUERY       = [];

    const PARAMS      = [
        'description' => 'string #文字描述',
        'title '      => 'string #按钮文本',
        'url'         => 'string #跳转链接',
        'logo_img'     => 'string #图片url',
        'download'    => 'int #下载开关 1 开 0 关',
    ];

    const SCHEMAS = [
    ];

    protected $beforeActionList = [
        'verifyToken', 'authorize',
    ];

    public function run() {


        $config = DB::table('top_config')->first();
        (new TopConfigValidate())->paramsCheck('post',$this->request,$this->response);//参数校验,新增
        $data = $this->request->getParams();
        if (mb_strlen(strip_tags($data['description'])) > 500) return $this->lang->set(10615);
        if($config){
            $insert_list['download']    = $data['download'];
            $insert_list['url']         = $data['url'] ?? '';
            $insert_list['title']       = $data['title'];
            $insert_list['description'] = mergeImageUrl($data['description']);
            $insert_list['logo_img']    = replaceImageUrl($data['logo_img']);
            $insert_list['jump_type']   = $data['jump_type'];
            $insert_list['commit']      = $data['commit'];
            DB::table('top_config')->where('id',$config->id)->update($insert_list);
            $this->redis->del(\Logic\Define\CacheKey::$perfix['topFloatList']);
        }else{
            $insert_list['download']    = $data['download'];
            $insert_list['url']         = $data['url'] ?? '';
            $insert_list['title']       = $data['title'];
            $insert_list['jump_type']   = $data['jump_type'];
            $insert_list['description'] = mergeImageUrl($data['description']);
            $insert_list['logo_img']    = replaceImageUrl($data['logo_img']);
            $insert_list['commit']      = $data['commit'];
            $insert_list['created']     = date('Y-m-d H:i:s',time());
            DB::table('top_config')->insert($insert_list);
        }

        return $this->lang->set(0);

    }
};