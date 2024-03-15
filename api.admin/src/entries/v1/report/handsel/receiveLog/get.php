<?php
use Logic\Admin\BaseController;

return new class() extends BaseController {
    const TITLE = '批量赠送彩金报表';
    const DESCRIPTION = '批量赠送彩金记录表';
    const QUERY = [
        'page' => 'int(required) #当前页',
        'page_size' => 'int(required) #每页数量',
        'date_start' => 'string() #开始日期',
        'date_end' => 'string() #结束日期',
        'user_name' => 'string() #查询账号名称'
    ];

    const PARAMS = [];
    const SCHEMAS = [
        [
            'msg_title' => '消息标题',
            'msg_content' => '消息内容',
            'give_away' => '赠送方式',
            'notice_away' => '通知方式',
            'give_num' => '赠送人数',
            'give_amount' => '设置的赠送彩金',
            'dm_num' => '设置的打码量',
            'total_give_amount' => '总赠送彩金',
            'create_time' => '创建时间',
            'give_time' => '赠送时间'
        ],
    ];

    protected $beforeActionList = [
        'verifyToken',
        'authorize'
    ];

    public function run()
    {
        $date_start = $this->request->getParam('date_start');
        $date_end = $this->request->getParam('date_end');
        $page = $this->request->getParam('page',1);
        $page_size = $this->request->getParam('page_size',20);
        $user_name = $this->request->getParam('user_name','');
        $id = $this->request->getParam('id', 0);    //active_handsel_log表的主键id
        $export = $this->request->getParam('export',0);        //是否导出数据：1-是

        if ($export == 1) {
            if (!empty($user_name)) {
                $data = DB::connection('slave')->table('handsel_receive_log')->whereRaw('status=? and handsel_log_id=? and user_name=? and receive_time>=? and receive_time<=?',[1,$id,$user_name,$date_start,$date_end])
                    ->selectRaw('user_id,user_name,status,receive_time,receive_amount')->get()->toArray();
            } else {
                $data = DB::connection('slave')->table('handsel_receive_log')->whereRaw('status=? and handsel_log_id=? and receive_time>=? and receive_time<=?',[1,$id,$date_start,$date_end])
                    ->selectRaw('user_id,user_name,status,receive_time,receive_amount')->get()->toArray();
            }
            if (empty($data)) {
                return createRsponse($this->response, 200, -2, '数据为空');
            }
            $exp = [];
            foreach ($data as $item) {
                $exp[] = [
                    'user_name' => $item->user_name,
                    'receive_time' => $item->receive_time,
                    'receive_amount' => bcdiv($item->receive_amount, 100,2),
                ];
            }
            $title = ['user_name'=>'会员账号','receive_amount'=>'领取金额','receive_time'=>'领取时间'];
            $this->exportExcel("批量赠送彩金",$title, $exp);
            exit();
        } else {
            if (!empty($user_name)) {
                $data = DB::connection('slave')->table('handsel_receive_log')->whereRaw('status=? and handsel_log_id=? and user_name=? and receive_time>=? and receive_time<=?',[1,$id,$user_name,$date_start,$date_end])
                    ->paginate($page_size,['user_id','user_name','status','receive_time','receive_amount'],'page',$page)->toJson();
            } else {
                $data = DB::connection('slave')->table('handsel_receive_log')->whereRaw('status=? and handsel_log_id=? and receive_time>=? and receive_time<=?',[1,$id,$date_start,$date_end])
                    ->paginate($page_size,['user_id','user_name','status','receive_time','receive_amount'],'page',$page)->toJson();
            }
            $ex_data = json_decode($data, true);
            $attr = [
                'total' => $ex_data['total'] ?? 0,
                'size' => $ex_data,
                'number' => $ex_data['last_page'] ?? 0,
                'current_page' => $ex_data['current_page'] ?? 0,    //当前页数
                'last_page' => $ex_data['last_page'] ?? 0,   //最后一页数
            ];
            return $this->lang->set(0, [], $ex_data['data'], $attr);
        }
    }

    public function exportExcel($file, $title, $data) {
        header('Content-type:application/vnd.ms-excel');
        header('Content-Disposition:attachment;filename=' . $file . '.xls');
        $content = '';
        foreach ($title as $tval) {
            $content .= $tval . "\t";
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
                    $content .= $val[$k] . "\t";
                }
                $content .= "\n";
                echo mb_convert_encoding($content, "UTF-8", "UTF-8");
                $content = '';
            }
        }
        exit;
    }
};
