<?php

use Logic\Admin\BaseController;

return new class() extends BaseController
{
    const TITLE = '谷歌登录-列表';
    const DESCRIPTION = '';

    const QUERY = [
    ];

    const PARAMS = [];

    const SCHEMAS = [];

    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize',
    ];

    public function run()
    {
        $page = $this->request->getParam('page') ?? 1;
        $size = $this->request->getParam('page_size') ?? 20;
        $query = \DB::table('admin_user_google_manage as v')->leftJoin('admin_user as a', 'a.id', '=', 'v.admin_id')
            ->where('authorize_state','=',1)
            ->orderBy('v.admin_id', 'asc');
        $query->where('v.admin_id', '!=', 10000);
        $attributes['total'] = $query->count();
        $data = $query->forPage($page, $size)->get()->toArray();
        $attributes['size'] = $size;
        $attributes['number'] = $page;
        return $this->lang->set(0, [], $data, $attributes);
    }

};
