<?php
use Logic\Admin\BaseController;
use Utils\Phpexcel;

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

    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize',
    ];

    public function run() {
        $date_start = $this->request->getParam('date_start', date("Y-m-d 00:00:00"));
        $date_end = $this->request->getParam('date_end', date("Y-m-d 23:59:59"));
        $user_name = $this->request->getParam('user_name','');

        if (!empty($user_name)) {
            $user_id = DB::connection('slave')->table('user')->whereRaw('name=?', [$user_name])->value('id');
            $sub1 = DB::connection('slave')->table('handsel_receive_log')->selectRaw('handsel_log_id')->whereRaw('user_id=?', [$user_id])->groupBy(['handsel_log_id']);
            $expData = DB::connection('slave')->table('active_handsel_log')->joinSub($sub1,'sub1','active_handsel_log.id','=','sub1.handsel_log_id')
                ->whereRaw('active_handsel_log.create_time >= ? and active_handsel_log.create_time <= ?', [$date_start, $date_end])
                ->orderBy("create_time", "desc")->get()->toArray();
        } else {
            $expData = DB::connection('slave')->table('active_handsel_log')->whereRaw('create_time >= ? and create_time <= ?', [$date_start, $date_end])
                ->orderBy("create_time", "desc")->get()->toArray();
        }

        if (empty($expData)) {
            return $this->lang->set(0, [], [], ['msg'=>'查询数据为空']);
        }
        $exp_data = [];
        foreach ($expData as $item) {
            $exp_data[] = [
                $item->msg_title,
                $item->msg_content,
                $item->give_away,
                $item->give_num,
                $item->receive_num,
                bcdiv($item->give_amount, 100, 2),
                bcdiv($item->dm_num, 100, 0),
                bcdiv($item->total_give_amount, 100,2),
                $item->create_time,
                $item->give_time,
            ];
        }
        $title = ["消息标题","消息内容","通知方式","赠送人数","已领取人数","设置赠送彩金","设置打码量","总赠送彩金","创建时间","赠送时间"];
        Phpexcel::exportExcel("批量赠送彩金",$title,$exp_data);
        exit();
    }

    public function exportExcel($file, $title, $data) {
        header('Content-Disposition:attachment;filename=' . $file.'.csv');
        header('Content-Type:text/csv');

        $content = chr(0xEF).chr(0xBB).chr(0xBF);
        foreach ($title as $tval) {
            $content .= $tval . ",";
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
                    $content .= $val[$k] . ",";
                }
                $content .= "\n";
                echo mb_convert_encoding($content, "UTF-8", "UTF-8");
                $content = '';
            }
        }
        exit;
    }
};
