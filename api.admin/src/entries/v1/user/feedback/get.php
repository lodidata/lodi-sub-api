<?php
use Logic\Admin\BaseController;

return new class() extends BaseController {
    const TITLE       = '意见与反馈';
    const DESCRIPTION = '意见与反馈';
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
        $mobile = $this->request->getParam('mobile','');
        $type = $this->request->getParam('type',0);
        $status = trim($this->request->getParam('status',''));
        $page = $this->request->getParam('page', 1);
        $page_size = $this->request->getParam('page_size', 10);

        //获取客服
        $subQuery = DB::connection('slave')->table('user_feedback');
        if(!empty($name)){
            $subQuery = $subQuery->where('user_name', 'like', '%'.$name.'%');
        }
        if(!empty($stime)){
            $subQuery = $subQuery->where('created', '>=', $stime.' 00:00:00');
        }
        if(!empty($etime)){
            $subQuery = $subQuery->where('created', '<=', $etime.' 23:59:59');
        }
        if(!empty($mobile)){
            $mobile = \Utils\Utils::RSAEncrypt($mobile);
            $subQuery = $subQuery->where('mobile', $mobile);
        }
        if(!empty($type)){
            $subQuery = $subQuery->where('type', $type);
        }
        if (strlen($status)) {
            $subQuery = $subQuery->where('status', $status);
        }
        $attr['total'] = $subQuery->count();
        $feedback_list = $subQuery->orderBy('status')->orderBy('created')->forPage($page, $page_size)->get()->toArray();

        $origins = [1=>'pc',2=>'h5',3=>'ios',4=>'android'];
        foreach($feedback_list as $key => &$val){
            $val = (array)$val;
            $val['mobile'] = \Utils\Utils::RSADecrypt($val['mobile']);
            $admin_user = DB::table('admin_user')->where('id',$val['operate_uid'])->first();
            $val['admin_user'] = '';
            if(!empty($admin_user)){
                $val['admin_user'] = $admin_user->username;
            }
            $val['origin'] = isset($origins[$val['origin']]) ? $origins[$val['origin']] : '';
            $val['img'] = showImageUrl($val['img'], true);
        }
        $attr['num'] = $page;
        $attr['size'] = $page_size;

        return $this->lang->set(0, '', $feedback_list, $attr);
    }
};