<?php

use Utils\Www\Action;

return new class() extends Action {
    const TITLE = '直推banner列表';
    const DESCRIPTION = '获取直推banner列表';
    const QUERY = [

    ];
    const SCHEMAS = [];

    public function run()
    {
        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }
        $user_id = $this->auth->getUserId();
        if (empty($user_id)) {
            return $this->lang->set(11, [], [], []);
        }

//        $params = $this->request->getParams();
//        $page = $params['page'] ?? 1;
//        $page_size = $params['page_size'] ?? 20;

        $banners = DB::connection('slave')
                     ->table('direct_imgs')
                     ->whereNotIn('type',['bkge_rule', 'user_promotion', 'be_pushed', 'register_finish', 'index_promotion'])
                     ->where('status', 'enabled')
                     ->get()
                     ->toArray();
        $banner_arr = [];
        if (!empty($banners)) {
            foreach ($banners as $b) {
                $arr_b = (array)$b;
                $arr_b['img'] = showImageUrl($arr_b['img']);
                $banner_arr[] = $arr_b;
            }
            //检测当前用户是否有绑定过上级代理，有则不展示【直推绑定上级banner】
            $agent = DB::connection('slave')->table('user_agent')->selectRaw('uid_agent')->whereRaw('user_id=?',[$user_id])->first();
            if (isset($agent->uid_agent) && !empty($agent->uid_agent)) {
                foreach ($banner_arr as $k=>$v) {
                    if ($v['type'] == 'no_bind') {
                        unset($banner_arr[$k]);
                    }
                }
                //如果用户绑定了上级，且充值了配置表中指定的数量，则不展示【直推充值banner】
                $config = \Logic\Set\SystemConfig::getModuleSystemConfig('direct');
                if (!isset($config['cash_be_pushed_recharge']['recharge_amount'])) {
                    return createRsponse($this->response, 200, -2, "系统配置数据为空", [], []);
                }
                $min = $config['cash_be_pushed_recharge']['recharge_amount'];    //最小充值金额金额
                $ck_fd = DB::table('funds_deposit')->selectRaw('money')->whereRaw('user_id=? and status=? and money>=?',[$user_id,'paid',$min])->first();
                if (!empty($ck_fd)) {
                    foreach ($banner_arr as $k=>$v) {
                        if ($v['type'] == 'no_recharge') {
                            unset($banner_arr[$k]);
                        }
                    }
                }
            }
        }
        $banner_arr = array_values($banner_arr);

        return $this->lang->set(0, [], $banner_arr, []);
    }
};
