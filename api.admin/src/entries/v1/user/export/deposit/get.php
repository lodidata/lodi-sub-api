<?php

use Logic\Admin\BaseController;

use lib\validate\BaseValidate;
return new class() extends BaseController
{
    const STATE       = '';
    const TITLE       = '导出充值用户信息';
    const DESCRIPTION = '导出充值用户信息';
    
    const QUERY       = [

    ];
    protected $title = [
                '交易订单号', '会员等级', '上级代理', '用户名', '存款方式',
                '存入银行账号', '会员银行账号', '存入金额', '优惠金额',
                '是否首存','申请时间', '过期时间','状态',
                '操作者','操作时间','备注'
            ];
    //前置方法
   protected $beforeActionList = [
'verifyToken','authorize'
   ];
    public function run()
    {
        exit;
//        $this->exportExcel();exit;
//        (new BaseValidate(
//            [
//                'line_num' =>'require|egt:20',
//                'offset' =>'require',
//                'title' =>'require',
//                'is_charge'=>'require|boolean'
//            ]
//        ))->paramsCheck('',$this->request,$this->response);
        $params = $this->request->getParams();

        $this->getDepositUserInfo($params);exit;


    }

    protected function getDepositUserInfo($params){

        $query = DB::connection('master')
            ->table('user')
//            ->leftJoin('funds_deposit as deposit','deposit.user_id','=','user.id')
            ->selectRaw('user.id,user.name,user.mobile')
            ->where('user.mobile','<>','')
            ->where('user.id','>',130000)
            ->where('user.id','<=',240000)
            ->whereRaw("not exists (select 1 from funds_deposit where user.id=funds_deposit.user_id) ")
            ;


//        if(isset($params['is_charge']) &&  $params['is_charge']){
//
//            $query = $query->where('deposit.status','paid');
//        }else{
//            $query = $query->where('deposit.status','<>','paid') ;
//        }
//        $query = isset($params['is_charge']) &&  $params['is_charge'] ? $query->where('deposit.status','paid') : $query ;

        $query
//            ->groupBy('user.id')
            ->orderBy('user.id')->chunk(2000, function($deposit) {

                foreach($deposit as &$val){
                    $val->mobile = \Utils\Utils::RSADecrypt($val->mobile);
                }

                $GLOBALS['depositUsers'][] = DB::resultToArray($deposit->toArray());
            });

//        print_r($GLOBALS['depositUsers']);exit;
        $data = [];
        if(count($GLOBALS['depositUsers']) >= 2){
            foreach ($GLOBALS['depositUsers'] as $depositUser){
                $data  = array_merge($data,$depositUser);
            }
        }else{
            $data = $GLOBALS['depositUsers'][0];
        }
        $title = [1=>'用户ID',2=>'用户名',3=>'手机号'];
        $this->exportExcel($title,$data,$params['title'],true);
        exit;
//        print_r($data);exit;



         \DB::table('funds_deposit as deposit')
            ->leftJoin('user','deposit.user_id','=','user.id')
            ->selectRaw('distinct deposit.user_id,user.name,user.mobile')
            ->where('deposit.status','paid')
            ->where('deposit.id','>',$params['offset'])
            ->orderBy('deposit.id')->chunk($params['line_num'], function($deposit) {

                foreach($deposit as &$val){
                    $val->mobile = \Utils\Utils::RSADecrypt($val->mobile);
                }

                 $GLOBALS['depositUserInfo'][] = DB::resultToArray($deposit->toArray());
            });
         $data = [];
         if(count($GLOBALS['depositUserInfo']) >= 2){
             foreach ($GLOBALS['depositUserInfo'] as $depositInfo){
                 $data += $depositInfo;
             }
         }else{
             $data = $GLOBALS['depositUserInfo'][0];
         }
        print_r($data);exit;
//        \Utils\Utils::exportExcel('DepositUserInfo','导出充值用户信息',$GLOBALS['depositUserInfo']);
        $title = [1=>'用户ID',2=>'用户名',3=>'手机号'];
        $this->exportExcel($title,$data,$params['title'],true);
    }


    /**
     *  数据导入
     * @param string $file excel文件
     * @param string $sheet
     * @return string   返回解析数据
     * @throws PHPExcel_Exception
     * @throws PHPExcel_Reader_Exception
     */
    public function importExecl($file='', $sheet=0){
        $file = iconv("utf-8", "gb2312", $file);   //转码
        if(empty($file) OR !file_exists($file)) {
            die('file not exists!');
        }
//        include('PHPExcel.php');  //引入PHP EXCEL类
        $objRead = new PHPExcel_Reader_Excel2007();   //建立reader对象
        if(!$objRead->canRead($file)){
            $objRead = new PHPExcel_Reader_Excel5();
            if(!$objRead->canRead($file)){
                die('No Excel!');
            }
        }

        $cellName = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ');

        $obj = $objRead->load($file);  //建立excel对象
        $currSheet = $obj->getSheet($sheet);   //获取指定的sheet表
        $columnH = $currSheet->getHighestColumn();   //取得最大的列号
        $columnCnt = array_search($columnH, $cellName);
        $rowCnt = $currSheet->getHighestRow();   //获取总行数

        $data = array();
        for($_row=1; $_row<=$rowCnt; $_row++){  //读取内容
            for($_column=0; $_column<=$columnCnt; $_column++){
                $cellId = $cellName[$_column].$_row;
                $cellValue = $currSheet->getCell($cellId)->getValue();
                //$cellValue = $currSheet->getCell($cellId)->getCalculatedValue();  #获取公式计算的值
                if($cellValue instanceof PHPExcel_RichText){   //富文本转换字符串
                    $cellValue = $cellValue->__toString();
                }

                $data[$_row][$cellName[$_column]] = $cellValue;
            }
        }

        return $data;
    }

    /**
     * 数据导出
     * @param array $title   标题行名称
     * @param array $data   导出数据
     * @param string $fileName 文件名
     * @param string $savePath 保存路径
     * @param $type   是否下载  false--保存   true--下载
     * @return string   返回文件全路径
     * @throws PHPExcel_Exception
     * @throws PHPExcel_Reader_Exception
     */
    function exportExcel($title=array(), $data=array(), $fileName='', $savePath='./', $isDown=true)
    {
//        include('PHPExcel.php');
        $obj = new PHPExcel();

        //横向单元格标识
        $cellName = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ');

        $obj->getActiveSheet(0)->setTitle('sheet1');   //设置sheet名称
        $_row = 1;   //设置纵向单元格标识
        if ($title) {
            $_cnt = count($title);
            $obj->getActiveSheet(0)->mergeCells('A' . $_row . ':' . $cellName[$_cnt - 1] . $_row);   //合并单元格
            $obj->setActiveSheetIndex(0)->setCellValue('A' . $_row, '数据导出：' . date('Y-m-d H:i:s'));  //设置合并后的单元格内容
            $_row++;
            $i = 0;
            foreach ($title AS $v) {   //设置列标题
                $obj->setActiveSheetIndex(0)->setCellValue($cellName[$i] . $_row, $v);
                $i++;
            }
            $_row++;
        }

        //填写数据
        if ($data) {
            $i = 0;
            foreach ($data AS $_v) {
                $j = 0;
                foreach ($_v AS $_cell) {
                    $obj->getActiveSheet(0)->setCellValue($cellName[$j] . ($i + $_row), $_cell);
                    $j++;
                }
                $i++;
            }
        }

        //文件名处理
        if (!$fileName) {
            $fileName = uniqid(time(), true);
        }

        $objWrite = PHPExcel_IOFactory::createWriter($obj, 'Excel2007');

        if ($isDown) {   //网页下载
            header('pragma:public');
            header("Content-Disposition:attachment;filename=$fileName.xls");
            $objWrite->save('php://output');
            exit;
        }

        $_fileName = iconv("utf-8", "UTF-8", $fileName);   //转码
        $_savePath = $savePath . $_fileName . '.xlsx';
        $objWrite->save($_savePath);

        return $savePath . $fileName . '.xlsx';
    }

    };
