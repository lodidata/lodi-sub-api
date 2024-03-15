<?php
use Logic\Admin\BaseController;

return new class() extends BaseController {
    const TITLE       = '客服列表';
    const DESCRIPTION = '客服列表';
    const PARAMS       = [
    ];
    const SCHEMAS     = [

    ];
    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize',
    ];
    public function run() {
        $stime = $this->request->getParam('start_time','');
        $etime = $this->request->getParam('end_time','');
        $name = $this->request->getParam('name', '');
        $page = $this->request->getParam('page', 1);
        $page_size = $this->request->getParam('page_size', 10);

        //获取客服
        $kefu_user_obj = DB::connection('slave')->table('kefu_user');
        if(!empty($name)){
            $kefu_user_obj = $kefu_user_obj->where('name', $name);
        }
        if(!empty($stime)){
            $kefu_user_obj = $kefu_user_obj->where('put_time', '>=', $stime);
        }
        if(!empty($etime)){
            $kefu_user_obj = $kefu_user_obj->where('put_time', '<=', $etime);
        }
        $attr['total'] = $kefu_user_obj->count();
        $kefu_user = $kefu_user_obj->orderBy('id','desc')->forPage($page, $page_size)->get()->toArray();

        $data = [];
        foreach($kefu_user as $key => $val){
            $data[] = [
                'kefu_id'  => $val->id,
                'name'     => $val->name,
                'put_time' => $val->put_time,
            ];
        }
        $attr['num'] = $page;
        $attr['size'] = $page_size;

        return $this->lang->set(0, '', $data, $attr);
    }
};