<?php

use Logic\Admin\BaseController;

return new class() extends BaseController
{
    const STATE       = '';
    const TITLE       = '获取社区论坛信息';
    const DESCRIPTION = '获取社区论坛信息';
    
    const QUERY       = [
    ];
    
    const PARAMS      = [
        'id' => 'int #社区ID',
    ];
    const SCHEMAS     = [
        'id' => 'int #社区ID',
        'name' => 'string #社区名称',
        'icon' => 'string #图标地址',
        'jump_url' => 'string #跳转链接',
        'status' => 'int #启动状态 0启用 1停用',
        'created' => 'string #生成时间',
    ];
    //前置方法
    protected $beforeActionList = [
        'verifyToken','authorize'
    ];
    public function run()
    {
        $params = $this->request->getParams();
        if(!isset($params['id']) || !is_numeric($params['id']) || $params['id'] <= 0){
            return $this->lang->set(10010);
        }
        $info = \DB::table('channel_management')->where('id','=', $params['id'])->first();
        if(empty($info)){
            return $this->lang->set(10010);
        }
        $info = (array)$info;

        if(empty($info['url'])){
            //获取系统设置推广地址
            $config = \DB::table('system_config')->where('key','h5_url')->first();
            $h5_url = '';
            if(!empty($config)){
                $h5_url = $config->value;
            }
            $info['url'] = $h5_url;
        }

        $attributes = [];
        return $this->lang->set(0,[],$info,$attributes);
    }
};
