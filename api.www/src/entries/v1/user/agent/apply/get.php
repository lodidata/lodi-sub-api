<?php
use Utils\Www\Action;
use Respect\Validation\Validator as V;
use Logic\User\Agent;

return new class extends Action
{
    const TOKEN = true;
    const TITLE = "获取代理申请列表";
    const DESCRIPTION = "";
    const TAGS = "";
    const PARAMS = [
    ];
    const SCHEMAS = [
    ];


    public function run() {
        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }
        $userId = $this->auth->getUserId();
        $params = $this->request->getParams();
        $params['page'] = $params['page'] ?? 1;
        $params['size'] = $params['page_size'] ?? 15;

        //待审核人数
        $pendNum = \DB::table('agent_apply')->where('status',0)->where('uid_agent',$userId)->count();

        $subQuery = \DB::table('agent_apply as aa')
            ->leftJoin('user as u','aa.user_id','=','u.id')
            ->leftJoin('user_level as ul','u.ranting','=','ul.id')
            ->leftJoin('admin_user as admin', 'aa.operate_uid', '=', 'admin.id')
            ->where('uid_agent',$userId);
        $total = $subQuery->count();
        $res = $subQuery->select(
            'aa.id',
            'aa.user_id',
            'u.name',
            'ul.name as level',
            'aa.contact_type',
            'aa.contact_value',
            'aa.reason',
            'aa.created',
            'aa.status',
            'aa.operate_time',
            'aa.remark',
            'aa.reply',
            'admin.username as admin_user',
            'aa.uid_agent','aa.uid_agent_name'
        )
            ->orderBy('aa.status')
            ->orderBy('aa.created')
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

        foreach($res as &$val){
            $val = (array)$val;
            $val['contact_type'] = $type_arr[$val['contact_type']] ?? '';
            $val['question'] = $submitArr[$val['id']] ?? [];
        }

        $attr['total'] = $total;
        $attr['num'] = $params['page'];
        $attr['size'] = $params['page_size'];
        $attr['pend_num'] = $pendNum;
        return $this->lang->set(0,[],$res,$attr);
    }
};