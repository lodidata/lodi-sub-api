<?php

use Logic\Admin\BaseController;

use lib\validate\BaseValidate;
return new class() extends BaseController
{
    const STATE = '';
    const TITLE = '导出会员操作日志信息';
    const DESCRIPTION = '导出会员操作日志信息';

    const QUERY = [
        'date_start'   => 'datetime(required) #开始日期 默认为当前日期',
        'date_end'     => 'datetime(required) #结束日期 默认为当前日期',
        'agent_id'     => "int() #代理ID号",
        'user_name'     => 'string() #会员账号',
    ];

    const SCHEMAS = [
        'id' => '编号', 'name' => '用户', 'log_ip' => 'ip', 'domain' => '域名', 'log_type' => '操作类型', 'status' => '状态',
        'created' => '操作时间', 'log_value' => '详情信息', 'platform' => '登录端'
    ];

    protected $title = [
        'id' => '编号', 'name' => '用户', 'log_ip' => 'ip', 'domain' => '域名', 'log_type' => '操作类型', 'status' => '状态',
        'created' => '操作时间', 'log_value' => '详情信息', 'platform' => '登录端', 'version' => '版本',
    ];

    protected $en_title = [
        'id' => 'Number', 'name' => 'User', 'log_ip' => 'IP', 'domain' => 'Domain', 'log_type' => 'OperationType',
        'status' => 'Status', 'created' => 'OperationTime', 'log_value' => 'Details', 'platform' => 'Platform', 'version' => 'Version',
    ];

    protected $platform = [
        1=>'PC', 2=>'H5', 3=>'IOS', 4=>'Android'
    ];

    protected $logType = [
        1 => 'Login', 2 => 'Withdrawal application', 3 => 'Recharge application', 4 => 'apply for Activity Award',
        5 => 'modify login password', 6 => 'change the withdrawal password', 7 => 'modify personal information',
        8 => 'member registration', 9 => 'agent registration', 10 => 'transfer', 11 => 'modify bank card information', 12 => 'betting'
    ];

    protected $beforeActionList = [
        'verifyToken','authorize'
    ];
    public function run() {

        $params = $this->request->getParams();
        if(!isset($params['date_begin']) || empty($params['date_begin'])){
            $params['date_begin'] = date('Y-m-d', time());
        }
        if(!isset($params['date_end']) || empty($params['date_end'])){
            $params['date_end'] = date('Y-m-d', time());
        }

        $query = DB::connection('slave')->table('user_logs')->orderBy('created', 'desc');
        !empty($params['type']) && $query->where('log_type', $params['type']);
        isset($params['status']) && is_numeric($params['status']) && $query->where('status', $params['status']);
        !empty($params['username']) && $query->where('name', 'like', "%{$params['username']}%");
        !empty($params['ip']) && $query->whereRaw('trim(log_ip)=?', $params['ip']);
        !empty($params['domain']) && $query->where('domain', 'like', "%{$params['domain']}%");
        !empty($params['date_begin']) && $query->where('created', '>=', $params['date_begin']);
        !empty($params['date_end']) && $query->where('created', '<=', $params['date_end'] . ' 23:59:59');
        !empty($params['platform']) && $query->where('platform', $params['platform']);

        $data = $query->get()->toArray();
        foreach ($data as &$val) {
            $val->platform = $this->platform[$val->platform];
            $logType = $this->logType[$val->log_type];
            $val->log_type = $this->lang->text($logType);
            $val->status = $val->status == 1 ? $this->lang->text('success') : $this->lang->text('fail');
        }

        foreach ($this->en_title as &$value) {
            $value = $this->lang->text($value);
        }
        array_unshift($data,$this->en_title);

        return $this->exportExcel('UserLogsReport',$this->title,$data);
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
            foreach ($data as $ke => $val) {
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
