<?php

use Logic\Admin\BaseController;

return new class extends BaseController {

    const TITLE       = '获取';
    const DESCRIPTION = '第三方logo';
    const QUERY       = [];

    const PARAMS      = [
        'name'        => 'string #名称',
        'logo'        => 'string #Icon',
    ];



    public function run() {


        $data = DB::table('third_logo')->orderBy('sort')->get();
        foreach ($data as $item){
            $item->logo = showImageUrl($item->logo);
        }
        return $this->lang->set(0,[],$data);
    }
};