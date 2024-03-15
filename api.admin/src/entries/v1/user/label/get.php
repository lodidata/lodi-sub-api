<?php

use Logic\Admin\BaseController;
return new class() extends BaseController
{
//    const STATE       = \API::DRAFT;
    const TITLE       = 'GET 方法操作';
    const DESCRIPTION = '会员标签列表';
    
    const QUERY       = [
        'page'      => 'int() #页数',
        'page_size' => 'int() #每页显示条数',
    ];
    
    const PARAMS      = [];
    const SCHEMAS     = [
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
        'authorize'
    ];

    /**
     *
     * @param int $page
     * @param int $page_size
     * @return mixed
     */
    public function run()
    {
        $data = $this->request->getParams();

        $res = DB::table('label')->forPage($data['page'],$data['page_size'])->get()->toArray();
        if(!$res){
            return [];
        }
        $temp = [];
        var_dump($res);die;
        foreach ($res as $key=>$val){
            if ($val['title'] == '测试' || $val['title'] == '试玩') {
                echo 1;die;
                unset($val);
            }
            $temp[] = $val;
        }
        $attributes['total'] = DB::table('label')->count();
        $attributes['number'] = $data['page'] ?? 1;
        $attributes['size'] = $data['page_size'];

        return $this->lang->set(0,[],$temp,$attributes);
    }
};
