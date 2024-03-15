<?php
use Logic\Admin\BaseController;
use lib\validate\BaseValidate;
return new class() extends BaseController
{

    const TITLE = 'GET 查询 代理申请';

    const DESCRIPTION = '代理申请';

    const QUERY = [

    ];
    const PARAMS = [];
    const SCHEMAS = [
        [
        ],
    ];

    protected $beforeActionList = [
        'verifyToken',
        'authorize'
    ];

    public function run()
    {
        $params = $this->request->getParams();
        $params['page'] = $params['page'] ?? 1;
        $params['size'] = $params['page_size'] ?? 15;
        $params['type'] = $params['type'] ?? 1;//1、1级代理申请 2、下级代理申请
        $params['name'] = $params['name'] ?? '';
        $params['agent_name'] = $params['agent_name'] ?? '';
        $params['contact_type'] = $params['contact_type'] ?? 0;
        $params['status'] = $params['status'] ?? -1;
        $params['start'] = $params['start'] ?? '';
        $params['end'] = $params['end'] ?? '';

        $subQuery = \DB::table('agent_apply as aa')
            ->leftJoin('user as u','aa.user_id','=','u.id')
            ->leftJoin('user_level as ul','u.ranting','=','ul.id')
            ->leftJoin('user_agent as ua','aa.user_id', '=', 'ua.user_id');
        if(!empty($params['name'])){
            $subQuery = $subQuery->where('u.name','like','%'.$params['name'].'%');
        }
        if(!empty($params['agent_name'])){
            $subQuery = $subQuery->where('aa.uid_agent_name','like','%'.$params['agent_name'].'%');
        }
        if($params['contact_type'] > 0){
            $subQuery = $subQuery->where('aa.contact_type','=',$params['contact_type']);
        }
        if($params['status'] != -1){
            $subQuery = $subQuery->where('aa.status','=',$params['status']);
        }
        if(!empty($params['start'])){
            $subQuery = $subQuery->where('aa.created','>=',$params['start']);
        }
        if(!empty($params['end'])){
            $subQuery = $subQuery->where('aa.created','<=',$params['end']);
        }
        if($params['type'] == 1){
            $subQuery = $subQuery->where('aa.uid_agent', 0);
        }else{
            $subQuery = $subQuery->where('aa.uid_agent', '>', 0);
        }
        $total = $subQuery->count();
        $res = $subQuery->select(
            'aa.id',
            'aa.uid_agent_name',
            'u.name',
            'ul.name as level',
            'aa.contact_type',
            'aa.contact_value',
            'aa.reason',
            'aa.created',
            'aa.status',
            'aa.operate_time',
            'aa.reply',
            'aa.remark',
            'aa.operate_uid',
            'aa.deal_user',
            'ua.uid_agent',
            'ua.uid_agent_name'
        )
        ->orderBy('aa.status')
        ->orderBy('aa.created','desc')
        ->forPage($params['page'],$params['size'])
        ->get()->toArray();

        $type_arr = [
            1 => 'Phone',
            2 => 'Line',
            3 => 'Email',
            4 => 'Ws',
            5 => 'FB',
            6 => 'IG',
            7 => 'WeChat',
            8 => 'Viber',
            9 => 'Other',
        ];

        $status_arr = [
            0 => '待审核',
            1 => '拒绝',
            2 => '通过',
        ];

        foreach($res as &$val){
            $val = (array)$val;
            $val['contact_type'] = $type_arr[$val['contact_type']] ?? '';
            $val['admin_user'] = '';
            if($val['deal_user'] == 1){
                $admin_info = \DB::table('admin_user')->where('id',$val['operate_uid'])->first();
                if(!empty($admin_info)){
                    $val['admin_user'] = $admin_info->username;
                }
            }elseif($val['deal_user'] == 2){
                $user_info = \DB::table('user')->where('id',$val['operate_uid'])->first();
                if(!empty($user_info)){
                    $val['admin_user'] = $user_info->name;
                }
            }
        }

        //获取申请记录对应的问题信息
        $idStr = array_column($res, 'id');
        $submitList = DB::table('agent_apply_submit')
                        ->whereIn('apply_id', $idStr)
                        ->selectRaw('apply_id, title, type, required, `option`, selected')
                        ->orderBy('sort', 'asc')
                        ->get()
                        ->toArray();
        $submitArr = [];
        foreach ($submitList as &$value) {
            $submitArr[$value->apply_id][] = [
                'title' => $value->title,
                'type' => $value->type,
                'required' => $value->required,
                'option' => in_array($value->type, [1,2]) ? json_decode($value->option) : ($value->option ?? ""),
                'selected' => in_array($value->type, [1,2]) ? json_decode($value->selected) : ($value->selected ?? "")
            ];
        }

        foreach ($res as $key=>&$val) {
            $res[$key]['question'] = $submitArr[$val['id']] ?? [];
        }

        $attr['total'] = $total;
        $attr['num'] = $params['page'];
        $attr['size'] = $params['page_size'];
        return $this->lang->set(0,[],$res,$attr);
    }
};
