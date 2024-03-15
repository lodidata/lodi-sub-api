<?php
use Logic\Admin\BaseController;
return new class() extends BaseController
{
    const STATE       = '';
    const TITLE       = '获取包状态';
    const PARAMS = [
    ];
    const SCHEMAS = [
    ];
    public function run()
    {
        $type    = $this->request->getParam('type');
        $version = $this->request->getParam('version');

        $info = \DB::table('app_package')->select('status')
                                ->where('type',$type)
                                ->where('version',$version)
                                ->orderBy('version','desc')
                                ->first();
        $data = [];
        if(!empty($info)){
            $data = (array)$info;
        }

        return $this->lang->set(0,[],$data);
    }
};
