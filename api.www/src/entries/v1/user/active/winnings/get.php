<?php

use Utils\Www\Action;

return new class() extends Action {
    const TITLE = '批量赠送彩金活动';
    const DESCRIPTION = '用户领取彩金记录';
    const QUERY = [
        'title' => 'string() #活动名称',
        'template_id' => 'integer() #模板ID',
        'status' => 'string() #enabled（启用），disabled（停用）',
    ];
    const SCHEMAS = [];

    public function run()
    {
        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }
        $user_id = $this->auth->getUserId();

        $params = $this->request->getParams();
        $page = $params['page'] ?? 1;
        $page_size = $params['page_size'] ?? 20;

        if (empty($user_id)) {
            return createRsponse($this->response, 200, -2, '缺少user_id');
        }

        $data = DB::table('handsel_receive_log')->whereRaw('user_id=?', [$user_id])->orderBy('id', 'desc')
            ->paginate($page_size,['*'],'page',$page)->toJson();
        $decode_data = json_decode($data, true);
        $attr = [
            'total' => $decode_data['total'] ?? 0,
            'size' => $page_size,
            'number' => $decode_data['last_page'] ?? 0,
            'current_page' => $decode_data['current_page'] ?? 0,    //当前页数
            'last_page' => $decode_data['last_page'] ?? 0,   //最后一页数
        ];
        return $this->lang->set(0, [], $decode_data['data'], $attr);
    }
};
