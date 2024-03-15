<?php
use Logic\Admin\BaseController;

return new class() extends BaseController {
    const TITLE = '等级列表';
    const QUERY = [

    ];
    const SCHEMAS = [];
    public function run() {
        $list = DB::table('user_level')->select(['name','level'])->get()->toArray();
        $resData = [];
        if (!empty($list)) {
            foreach ($list as $item) {
                $resData[] = ['name'=>$item->name, 'level'=>$item->level];
            }
        }
        return $this->lang->set(0, '', $resData, []);
    }
};