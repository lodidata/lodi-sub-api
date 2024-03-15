<?php

use Logic\Admin\BaseController;

return new class() extends BaseController {
//    const STATE       = \API::DRAFT;
    const TITLE = 'GET 方法操作';
    const DESCRIPTION = '会员标签列表';

    const QUERY = [
        'page'      => 'int() #页数',
        'page_size' => 'int() #每页显示条数',
    ];
    
    const PARAMS = [];
    const SCHEMAS = [
        [
            'id'          => 'int() #id',
            'name'        => 'string() #标签名称',
            'desc'        => 'string() #描述',
            'creator'     => 'string() #创建者',
            'create_time' => 'int() #创建时间',
        ],
    ];

    protected $beforeActionList = [
        'verifyToken',
        'authorize',
    ];

    /**
     *
     * @param int $page
     * @param int $page_size
     *
     * @return mixed
     */
    public function run() {
        $params = $this->request->getParams();

        $query = DB::table('label as l')
                 ->leftJoin('admin_user as ad', 'l.admin_uid', '=', 'ad.id')
                 ->where('l.id', '!=', 7)
                 ->where('l.id', '!=', 4)
                 ->selectRaw('l.*,ad.username as create_name')
                 ->orderBy('id', 'desc');
       $res =   $query->get()
                 ->forPage($params['page'], $params['page_size'])
                 ->toArray();

        if (!$res) {
            return [];
        }
        $query = clone $query;
        $res = array_values($res);
        $attributes['total'] = $query->count();

        $attributes['number'] = $params['page'];
        $attributes['size'] = $params['page_size'];

        return $this->lang->set(0, [], $res, $attributes);
    }
};
