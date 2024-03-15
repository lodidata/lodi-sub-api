<?php
use Logic\Admin\BaseController;

return new class() extends BaseController
{
    const TITLE = '下载文件模板';
    const DESCRIPTION = '下载批量上传手机号的文件模板';
    const QUERY = [];
    const PARAMS = [];

    public function run() {
        //下载.txt模板
        $array = ["mobile","8976676752","8956676752","8976676751"];
        header("Content-type:application/octet-stream");
        header("Accept-Ranges:bytes");
        header("Content-Disposition:attachment;filename=批量赠送彩金模板.txt");
        header("Expires: 0");
        header("Cache-Control:must-revalidate,post-check=0,pre-check=0");
        header("Pragma:public");
        foreach ($array as $k => $v) {
            echo "$v\r\n";
        }
        exit();

        //下载.excel模板
//        $title = ['mobile'=>'mobile'];
//        $exp = [
//            ['mobile'=>18742563656],
//            ['mobile'=>13642563656],
//            ['mobile'=>18542563656],
//        ];
//        $this->exportExcel("批量赠送彩金",$title, $exp);
//        exit();
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