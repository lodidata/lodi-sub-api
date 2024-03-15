<?php

use Logic\Admin\BaseController;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

return new class() extends BaseController {
    const TITLE = '用户申请列表';
    const DESCRIPTION = '用户申请列表';
    const HINT = 'url的?\d替换成记录ID值';
    const QUERY = [
    ];
    const PARAMS = [];
    const SCHEMAS = [
        [],
    ];

    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize',
    ];

    public function run()
    {
        $params = $this->request->getParams();
        $this->getActieApplys($params);
    }

    protected function getActieApplys($params)
    {
        $query = DB::connection('slave')->table('active_apply as ap')
            ->leftJoin('active as a', 'ap.active_id', '=', 'a.id')
            ->leftJoin('active_template as at', 'a.type_id', '=', 'at.id')
            ->leftJoin('user as u', 'u.id', '=', 'ap.user_id')
            ->leftJoin('admin_user as au', 'au.id', '=', 'ap.created_uid')
            ->select(DB::raw(
                'ap.id,a.type_id,a.active_type_id,ap.user_name,ap.user_id,
                    ap.active_id,a.title,a.name as active_name,
                    ap.deposit_money,ap.coupon_money as money,
                    ap.withdraw_require,ap.apply_time,
                    ap.updated,ap.content,ap.memo,at.name as template,
                    ap.status,ap.state,ap.process_time,
                    a.begin_time,a.end_time,ap.reason,ap.apply_pic,au.username as operator_name',
            ))
            ->whereNotIn('u.tags', [4, 7])
            ->where('a.state', 'apply');


        if (!empty($params['user_name'])) {
            $user_name = $params['user_name'];
            $query = $query->where('ap.user_name', "$user_name");
        }

        if (!empty($params['active_name'])) {
            $active_name = $params['active_name'];
            $query = $query->where('a.name', "$active_name");
        }

        if (!empty($params['active_type_id'])) {
            $query = $query->where('a.active_type_id', $params['active_type_id']);
        }

        if (!empty($params['start_time'])) {
            $query = $query->where('ap.apply_time', '>=', $params['start_time']);
        }

        if (!empty($params['end_time'])) {
            $query = $query->where('ap.apply_time', '<=', $params['end_time']);
        }

        if (!empty($params['status'])) {
            $status = $params['status'];
            $query = $query->where('ap.status', "$status");
        }

        $res = $query->orderBy('ap.created', 'desc')
            ->orderBy('ap.id')
            ->get()
            ->toArray();

        $activeTypeArr = DB::connection('slave')->table('active_type')->pluck('name', 'id')->toArray();

        $user_ids = (array_unique(array_column($res, 'user_id')));
        $staticData = [];
        foreach ($user_ids as $value) {
            //当日统计
            $today_static = DB::table('rpt_user')
                ->where('user_id', $value)
                ->where('count_date', date('Y-m-d'))
                ->select(['deposit_user_amount', 'bet_user_amount'])
                ->first();
            $staticData[$value]['today_recharge_amount'] = $today_static ? $today_static->deposit_user_amount : 0;
            $staticData[$value]['today_bet_amount'] = $today_static ? $today_static->bet_user_amount : 0;
            //总计统计
            $total_static = DB::table('rpt_user')
                ->where('user_id', $value)
                ->select(DB::raw('sum(deposit_user_amount) as deposit_user_amount,sum(bet_user_amount) as bet_user_amount'))
                ->first();
            $staticData[$value]['total_recharge_amount'] = $total_static ? $total_static->deposit_user_amount : 0;
            $staticData[$value]['total_bet_amount'] = $total_static ? $total_static->bet_user_amount : 0;

            //昨日充值金额
            $yesterday_static = DB::table('rpt_user')
                ->where('user_id', $value)
                ->where('count_date', date("Y-m-d", strtotime("-1 day")))
                ->value('deposit_user_amount');
            $staticData[$value]['yesterday_recharge_amount'] = $yesterday_static ?? 0;

            //用户申请统计
            $success = DB::table('active_apply')
                ->where('user_id', $value)
                ->where('status', 'pass')
                ->count();
            $sum = DB::table('active_apply')
                ->where('user_id', $value)
                ->count();
            $staticData[$value]['total_apply'] = intval($sum) ?? 0;
            $staticData[$value]['success_apply'] = intval($success) ?? 0;
        }
        foreach ($res as $v) {
            $v->today_recharge_amount = $staticData[$v->user_id]['today_recharge_amount'] ?? 0;
            $v->today_bet_amount = $staticData[$v->user_id]['today_bet_amount'] ?? 0;
            $v->total_recharge_amount = $staticData[$v->user_id]['total_recharge_amount'] ?? 0;
            $v->yesterday_recharge_amount = $staticData[$v->user_id]['yesterday_recharge_amount'] ?? 0;
            $v->total_bet_amount = $staticData[$v->user_id]['total_bet_amount'] ?? 0;
            $v->total_apply = $staticData[$v->user_id]['total_apply'];
            $v->success_apply = $staticData[$v->user_id]['success_apply'];
        }

//        $title = [
//            "user_name" => $this->lang->text('username'),
//            "apply_time" => $this->lang->text('apply_time'),
//            "active_name" => $this->lang->text('active_name'),
//            "type_id" => $this->lang->text('type_id'),
//            "start_end_time" => $this->lang->text('start_end_time'),
//            "reason" => $this->lang->text('reason'),
//            "today_recharge_amount" => $this->lang->text('today_recharge_amount'),
//            "today_bet_amount" => $this->lang->text('today_bet_amount'),
//            "total_recharge_amount" => $this->lang->text('total_recharge_amount'),
//            "total_bet_amount" => $this->lang->text('total_bet_amount'),
//            "status" => $this->lang->text('State'),
//            "process_time" => $this->lang->text('process_time'),
//            "created_uid" => $this->lang->text('created_uid'),
//            "memo" => $this->lang->text('memo'),
//        ];
        $title = [
            $this->lang->text('total_apply'),
            $this->lang->text('success_apply'),
            $this->lang->text('username'),
            $this->lang->text('apply_time'),
            $this->lang->text('active_name'),
            $this->lang->text('type_id'),
            $this->lang->text('start_end_time'),
            $this->lang->text('reason'),
            $this->lang->text('yesterday_recharge_amount'),
            $this->lang->text('today_recharge_amount'),
            $this->lang->text('today_bet_amount'),
            $this->lang->text('total_recharge_amount'),
            $this->lang->text('total_bet_amount'),
            $this->lang->text('State'),
            $this->lang->text('bonus_amount'),
            $this->lang->text('process_time'),
            $this->lang->text('created_uid'),
            $this->lang->text('memo'),
        ];
        $exp = [];
        foreach ($res as $item) {
//            $exp[] = [
//                "user_name" => $item->user_name,
//                "apply_time" => $item->apply_time,
//                "active_name" => $item->active_name,
//                "type_id" => $item->template,
//                "start_end_time" => $item->begin_time . '-' . $item->end_time,
//                "reason" => $item->reason,
//                "today_recharge_amount" => $item->today_recharge_amount,
//                "today_bet_amount" => $item->today_recharge_amount,
//                "total_recharge_amount" => $item->total_recharge_amount,
//                "total_bet_amount" => $item->total_bet_amount,
//                "status" => $item->status == "pass" ? "已通过" : ($item->status == "rejected" ? "已拒绝" : "待审批"),
//                "process_time" => $item->process_time,
//                "created_uid" => $item->operator_name,
//                "memo" => $item->memo,
//            ];
            $exp[] = [
                $item->total_apply,
                $item->success_apply,
                $item->user_name,
                $item->apply_time,
                $item->active_name,
                $activeTypeArr[$item->active_type_id] ?? '',
                $item->begin_time . '-' . $item->end_time,
                $item->reason,
                $item->yesterday_recharge_amount,
                $item->today_recharge_amount,
                $item->today_recharge_amount,
                $item->total_recharge_amount,
                $item->total_bet_amount,
                $item->status == "pass" ? "已通过" : ($item->status == "rejected" ? "已拒绝" : "待审批"),
                bcdiv($item->money, 100),
                $item->process_time,
                $item->operator_name,
                $item->memo,
            ];
        }
//        $this->exportExcel('用户申请列表', $title, $exp);
        $this->exportExcel("用户申请列表", $title, $exp);
        exit();
    }

    public function exportExcel($file, $title, $data)
    {
        header('Content-type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition:attachment;filename=' . $file . '.xlsx');
        $spreadsheet = new Spreadsheet();
        $activeWorksheet = $spreadsheet->getActiveSheet();

        $row = 1;
        foreach ($title as $tmpKey => $tval) {
            $activeWorksheet->setCellValueByColumnAndRow($tmpKey + 1, $row, $tval);
        }
        $keys = array_keys($title);
        ++$row;
        if ($data) {
            foreach ($data as $ke => $val) {
                if ($ke > 49999) {
                    break;
                }
                $val = (array)$val;
                foreach ($keys as $k) {
                    $activeWorksheet->setCellValueExplicitByColumnAndRow($k + 1, $row, $val[$k], DataType::TYPE_STRING);
                }
                $row++;
            }
        }
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('php://output');
        exit;
    }
};