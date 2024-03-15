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
    protected $title = [
        'name'              => '会员账号',
        'level'             => '会员等级',
        'apply_content'     => '申请内容',
        'created'           => '申请时间',
        'status'            => '状态',
        'operate_time'      => '操作时间',
        'reply'             => '回复消息',
        'remark'            => '备注',
        'admin_user'        => '操作者',
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
        $params['name'] = $params['name'] ?? '';
        $params['contact_type'] = $params['contact_type'] ?? 0;
        $params['status'] = $params['status'] ?? -1;
        $params['start'] = $params['start'] ?? '';
        $params['end'] = $params['end'] ?? '';

        $subQuery = \DB::table('agent_apply as aa')
            ->leftJoin('user as u','aa.user_id','=','u.id')
            ->leftJoin('user_level as ul','u.ranting','=','ul.id')
            ->leftJoin('user_agent as ua','aa.user_id', '=', 'ua.user_id')
            ->leftJoin('admin_user as admin', 'aa.operate_uid', '=', 'admin.id');
        if(!empty($params['name'])){
            $subQuery = $subQuery->where('u.name','like','%'.$params['name'].'%');
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
        $total = $subQuery->count();
        $res = $subQuery->select(
            'aa.id',
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
            'admin.username as admin_user',
            'ua.uid_agent','ua.uid_agent_name'
        )
            ->orderBy('aa.status')
            ->orderBy('aa.created')
//            ->forPage($params['page'],$params['size'])
            ->get()->toArray();

        $type_arr = [
            1 => 'Phone',
            2 => 'Lien',
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
            $val['reason'] = \Utils\Utils::matchChinese($val['reason']);
            $val['remark'] = \Utils\Utils::matchChinese($val['remark']);
            $val['reply'] = \Utils\Utils::matchChinese($val['reply']);
            $val['status']       = $status_arr[$val['status']];
        }

        //获取申请记录对应的问题信息
        $idStr = array_column($res, 'id');
        $submitList = DB::table('agent_apply_submit')
                        ->whereIn('apply_id', $idStr)
                        ->selectRaw('apply_id, title, selected, type')
                        ->orderBy('sort', 'asc')
                        ->get()
                        ->toArray();
        $submitArr = [];
        foreach ($submitList as &$value) {
            $submitArr[$value->apply_id][] = [
                'title' => $value->title,
                'type' => $value->type,
                'selected' => in_array($value->type, [1,2]) ? json_decode($value->selected) : ($value->selected ?? "")
            ];
        }
        foreach ($res as $key=>&$val) {
            $res[$key]['question'] = $submitArr[$val['id']] ?? [];
        }

        $attr['total'] = $total;
        return $this->exportExcel('会员管理列表',$this->title,$res);
    }

    public function exportExcel($file, $title, $data) {
        header('Content-type:application/vnd.ms-excel');
        header('Content-Disposition:attachment;filename=' . $file . '.xls');

        $search = array("<br>","</br>","<br/>","<br/>");
        $replace = '<br style="mso-data-placement:same-cell;" />';
        $content = $td = $tr = '';
        foreach ($title as $tval) {
            $td .= "<td style='border:1px solid #E3E5E8; align:center;'>".$tval."</td>";
        }

        $content .= "\n";
        $keys = array_keys($title);
        if ($data) {
            foreach ($data as $ke=> $val) {
                if ($ke > 49999) {
                    break;
                }
                $val = (array)$val;
                foreach ($keys as $k) {
                    if($k == 'apply_content') {
                        if(empty($val['question'])) {
                            $str1 = "联系类型：".$val['contact_type']."<br>联系方式：".$val['contact_value']."<br>申请理由：".$val['reason'];
                        } else {
                            foreach($val['question'] as $k=>$v) {
                                if($k == 0) {
                                    $str1 = "问题".($k+1)."：".$v['title'];
                                } else {
                                    $str1 .= "<br>问题".($k+1)."：".$v['title'];
                                }
                                if(!empty($v['selected']) && in_array($v['type'], [1,2])) {
                                    foreach($v['selected'] as $vv) {
                                        foreach ((array)$vv as $item) {
                                            $str1 .= "<br>内容".($k+1)."：".$item;
                                        }
                                    }
                                } else {
                                    $selected = empty($v['selected']) ? "" : $v['selected'];
                                    $str1 .= "<br>内容".($k+1)."：".$selected;
                                }
                            }
                        }

                        $str = str_replace($search, $replace, $str1);
                        $str1 = "";
                        $content .= "<td style='border:1px solid #E3E5E8; align:center;'>".$str."</td>";
                    } else {
                        $content .= "<td style='border:1px solid #E3E5E8; align:center;'>".$val[$k]."</td>";
                    }
                }
                $tr .= "<tr>".$content."</tr>";
                $content = "";
            }
        }
        $str = "<meta http-equiv='Content-type' content='text/html;charset=UTF-8' />
                     <table>
                        <tr>". $td ."</tr>
                        ".$tr."
                     </table>";
        echo ($str);
        exit;
    }

};
