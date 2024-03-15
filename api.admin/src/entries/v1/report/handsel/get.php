<?php
use Logic\Admin\BaseController;

return new class() extends BaseController
{
    const TITLE = '批量赠送彩金';
    const DESCRIPTION = '批量赠送彩金记录表';
    const QUERY = [
        'page' => 'int(required) #当前页',
        'page_size' => 'int(required) #每页数量',
        'date_start' => 'string() #开始日期',
        'date_end' => 'string() #结束日期',
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

    public function run() {
        $date_start = $this->request->getParam('date_start');
        $date_end = $this->request->getParam('date_end');
        $page = $this->request->getParam('page',1);
        $page_size = $this->request->getParam('page_size',10);
        $export = $this->request->getParam('export',0);  //是否导出数据：1-是
        $user_name = $this->request->getParam('user_name','');

        if ($export == 1) {
            $query = DB::connection('slave')
                        ->table('active_handsel_log as ahl');

            if(!empty($user_name)) {
                $query = $query->leftJoin('handsel_receive_log as hrl', 'ahl.id','=','hrl.handsel_log_id')
                               ->where('hrl.user_name', '=', $user_name);
            }

            $expData = $query->where('ahl.create_time','>=', $date_start)
                             ->where('ahl.create_time', '<=', $date_end)
                             ->selectRaw('
                                ahl.id as id,
                                ahl.active_handsel_id,
                                ahl.msg_title,
                                ahl.msg_content,
                                ahl.give_away,
                                ahl.notice_away,
                                ahl.give_num,
                                ahl.receive_num,
                                ahl.give_amount,
                                ahl.dm_num,
                                ahl.total_give_amount,
                                ahl.create_time,
                                ahl.give_time,
                                ahl.valid_time
                            ')
                             ->orderBy("ahl.create_time", "desc")
                             ->get()
                             ->toArray();
            if (empty($expData)) {
                return $this->lang->set(0, [], [], ['msg'=>'查询数据为空']);
            }
            $title = [
                'msg_title' => '消息标题',
                'msg_content' => '消息内容',
                'notice_away' => '通知方式',
                'give_num' => '赠送人数',
                'give_amount' => '设置赠送彩金',
                'dm_num' => '设置打码量',
                'total_give_amount' => '总赠送彩金',
                'create_time' => '创建时间',
                'give_time' => '赠送时间',
            ];
            $exp = [];
            foreach ($expData as $item) {
                $exp[] = [
                    'msg_title' => $item->msg_title,
                    'msg_content' => $item->msg_content,
                    'notice_away' => $item->give_away,
                    'give_num' => $item->give_num,
                    'give_amount' => $item->give_amount,
                    'dm_num' => $item->dm_num,
                    'total_give_amount' => $item->total_give_amount,
                    'create_time' => $item->create_time,
                    'give_time' => $item->give_time,
                ];
            }
            $this->exportExcel("批量赠送彩金",$title, $exp);
            exit();
        } else {
            $query = DB::connection('slave')
                        ->table('active_handsel_log as ahl');

            if(!empty($user_name)) {
                $query = $query->leftJoin('handsel_receive_log as hrl', 'ahl.id','=','hrl.handsel_log_id')
                               ->where('hrl.user_name', '=', $user_name);
            }

            $data = $query->where('ahl.create_time','>=', $date_start)
                          ->where('ahl.create_time', '<=', $date_end)
                          ->selectRaw('
                            ahl.id as id,
                            ahl.active_handsel_id,
                            ahl.msg_title,
                            ahl.msg_content,
                            ahl.give_away,
                            ahl.notice_away,
                            ahl.give_num,
                            ahl.receive_num,
                            ahl.give_amount,
                            ahl.dm_num,
                            ahl.total_give_amount,
                            ahl.create_time,
                            ahl.give_time,
                            ahl.valid_time
                          ')
                          ->orderBy("ahl.create_time", "desc")
                          ->paginate($page_size, ['*'], 'page', $page)
                          ->toJson();
            $parseData = json_decode($data, true);
            $resData = $parseData['data'];
            foreach ($resData as $k => $item){
                $resData[$k]['receive_total_amount'] = DB::connection('slave')
                                                            ->table('handsel_receive_log')
                                                            ->where('handsel_log_id',$item['id'])
                                                            ->where('status','1')
                                                            ->sum('receive_amount');

                //非固定彩金的设置赠送彩金、设置打码量 不显示
                $unfixed = DB::connection('slave')
                                ->table('active_handsel')
                                ->where('id',$item['active_handsel_id'])
                                ->value('unfixed_url');
                if(!empty($unfixed)) {
                    $resData[$k]['give_amount'] = $resData[$k]['dm_num'] = '/';
                }
            }
            $attr = [
                'total' => isset($parseData['total']) ? $parseData['total'] : 0,
                'size' => $page_size,
                'number' => isset($parseData['last_page']) ? $parseData['last_page'] : 0,
                'current_page' => isset($parseData['current_page']) ? $parseData['current_page'] : 0,    //当前页数
                'last_page' => isset($parseData['last_page']) ? $parseData['last_page'] : 0,   //最后一页数
            ];
            return $this->lang->set(0, [], $resData, $attr);
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