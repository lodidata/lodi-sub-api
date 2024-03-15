<?php
use Logic\Admin\BaseController;
return new class() extends BaseController
{
    const STATE       = '';
    const TITLE       = '获取IOS包信息';
    const DESCRIPTION = '';
    
    const QUERY       = [
        'channel_id' => 'string(, 30)',
        'name' => 'string(require, 50)',#APP Name,
        'status' => 'int()#审核不通过0，通过1',
        'page'         => 'int()   #页码',
        'page_size'    => 'int()    #每页大小',
    ];
    
    const PARAMS      = [];
    const SCHEMAS     = [
    ];

    //前置方法
    protected $beforeActionList = [
        'verifyToken','authorize'
    ];

    public function run()
    {
        $channel_id = $this->request->getParam('channel_id');//渠道ID
        $name = $this->request->getParam('name');//APP名称
        $status = $this->request->getParam('status');//审核结果
        $page = $this->request->getParam('page',1);//页数
        $page_size = $this->request->getParam('page_size',20);//每页记录数

        //查询APP包及对应的管理员id，
        //type 1：IOS马甲包，2：IOS企业包，3：Android上架包
        //delete 0：正常，1：删除
        $query= \DB::table('app_bag as bag')
            ->leftJoin('admin_user as admin','bag.update_uid','=','admin.id')
            ->orderBy('id','DESC')
            ->where('delete','=',0)
            ->where('type','=',3);
        //是否存在审核结果查询
        if(!is_null($status)) {
            $query->where('bag.status','=',$status);
        }
        //是否存在APP名称查询
        $name && $query->where('bag.name','=',$name);
        //是否存在渠道ID
        $channel_id && $query->where('bag.channel_id','=',$channel_id);
        //查询记录总数
        $total = $query->count();
        $data = $query->forPage($page,$page_size)->get([
                'bag.id',
                'bag.channel',
                'bag.channel_id',
                'bag.name',
                'bag.status',
                'bag.force_update',
                'bag.name_update',
                'bag.url_update',
                'admin.username',
                'bag.create_date',
                'bag.update_date',
                'bag.status_rule',
            ])->toArray();
        $tmp = [
            'start_time'=>  '',
            'end_time'  =>  '',
        ];
        foreach ($data as &$val) {
            $val = (array)$val;
            $json = json_decode($val['status_rule'],true);
            $val['status_rule'] = is_array($json) ? $json : $tmp;
        }
        $attr = [
            'number' => $page,
            'size' => $page_size,
            'total' => $total,
        ];
        return $this->lang->set(0,[],$data,$attr);
    }
};
