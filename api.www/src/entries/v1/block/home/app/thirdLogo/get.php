<?php

use Utils\Www\Action;
use Model\Spread;
use Logic\Define\CacheKey;

return new class extends Action {
    const TAGS = '首页';
    const TITLE = '第三方logo';
    const QUERY = [
    ];

    const SCHEMAS = [
        [
            'name'      => 'string #名称',
            'logo '     => 'string #图标',
        ]
    ];


    public function run() {


        $data = DB::table('third_logo')->orderBy('sort')->get(['name','logo']);
        foreach ($data as $item){
            $item->logo = showImageUrl($item->logo);
        }
        return $this->lang->set(0,[],$data);
    }
};