<?php
namespace Utils;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Exception;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class Phpexcel {
    //表格坐标
    private static $cellIndex = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ'];


    /**
     * 导出excel
     * @throws Exception
     */
    public static function exportExcel($file_name,$title,$exp_data,$format="Xlsx"){
        if (empty($file_name) || empty($title)) {
            return "缺少参数";
        }
        $columns = count($title);   //总列数

        ob_end_clean();
        $sps = new Spreadsheet();
        $sheet = $sps->getActiveSheet();
        $sheet->setTitle($file_name);

        //常规设置单元格内容
        foreach ($title as $index => $name) {
            $sheet->setCellValue(self::$cellIndex[$index] . '1', $name);
        }

        //通过列位置、行位置来设置单元格内容
        $baseRow = 2;
        foreach ($exp_data as $index => $data) {
            $i = $index + $baseRow;
            for ($k = 0; $k <= $columns - 1; $k++) {
                $item = $data[$k];
                $sheet->setCellValue(self::$cellIndex[$k] . $i, ' ' . $item);
                //中文设置表格宽度
//                if (preg_match("/[\x7f-\xff]/", $data[$k])) {
//                    $sheet->getColumnDimension(self::$cellIndex[$k])->setWidth(strlen($item));
//                } else {   //非中文自动设置宽度
//                    $sheet->getColumnDimension(self::$cellIndex[$k])->setAutoSize(true);
//                }
            }
        }
        //计算自动调整列的宽度
        $sheet->calculateColumnWidths();

        if ($format == "Xlsx") {
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        } else {
            header('Content-Type: application/vnd.ms-excel');
        }
        $file_txt = $file_name.'.'.$format;
        header('Content-Disposition: attachment;filename="' . $file_txt .'"');
        header('Cache-Control: max-age=0');
        $writer = IOFactory::createWriter($sps, $format);
        $writer->save('php://output');
        exit;
    }
}