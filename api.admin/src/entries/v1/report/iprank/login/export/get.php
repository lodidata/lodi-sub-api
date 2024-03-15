<?php

use Logic\Admin\BaseController;

return new class() extends BaseController {
    const TITLE = '登录IP排行榜';
    const DESCRIPTION = '登录IP排行榜';
    
    const QUERY = [
    ];

    const PARAMS = [
    ];
    const SCHEMAS = [
    ];

    protected $title=[
        'login_ip'=>'登录IP',
        'num'=>'同IP登录人数',
    ];

    //前置方法
    protected $beforeActionList = [
        'verifyToken', 'authorize',
    ];

    public function run() {
        $date_start = $this->request->getParam('date_start',date('Y-m-d'));
        $date_end = $this->request->getParam('date_end',date('Y-m-d'));

        $field_id = $this->request->getParam('field_id', 'num');
        $sort_way = $this->request->getParam('sort_way', 'desc');
        if(!in_array($sort_way, ['asc', 'desc'])) $sort_way = 'desc';
        $sort_way = ($sort_way == 'asc') ? "ASC" : "DESC";

        $query = DB::connection('slave')->table('user');
        if(!empty($date_start)){
            $date_start = strtotime($date_start);
            $query = $query->where('last_login','>=',$date_start);
        }
        if(!empty($date_end)){
            $date_end = strtotime($date_end) +86399;
            $query = $query->where('last_login','<=',$date_end);
        }

        $query = $query->selectRaw('inet6_ntoa(login_ip) as login_ip,count(login_ip) as num')->groupBy(DB::Raw('inet6_ntoa(login_ip)'));
        $user_list = $query->orderBy($field_id,$sort_way)
                            ->get()->toArray();


        return $this->exportExcel('loginIPReport',$this->title,$user_list);
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