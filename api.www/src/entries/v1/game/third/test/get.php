<?php

use Logic\GameApi\GameApi;
use Utils\Curl;
use Utils\Utils;
use Utils\Www\Action;

return new class extends Action {
    const HIDDEN = true;
    const TITLE = "TEST 第三方游戏";


    const SCHEMAS = [
    ];

    protected $beforeActionList = [
        'verifyRunMode'
    ];

    public function run()
    {
        $this->addEvoRTImgs();
        die;
    }

    private function addEVOGames()
    {
        $objReader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');
        $objPHPExcel = $objReader->load(ROOT_PATH . '/data/game/evo.xlsx');
        $sheet = $objPHPExcel->getSheet(0);   //excel中的第一张sheet
        $highestRow = $sheet->getHighestRow();       // 取得总行数
        for ($j = 1; $j <= $highestRow; $j++) {
            if ($j == 1) {
                continue;
            }
            $kind_id = trim($objPHPExcel->getActiveSheet()->getCell("F" . $j)->getValue());
            if (is_null($kind_id) || empty($kind_id)) {
                continue;
            }
            $type_name = $objPHPExcel->getActiveSheet()->getCell("D" . $j)->getValue();
            if ($type_name == 'instant game') {
                $game_id = 127;
                $game_type = 'LIVE';
            } elseif ($type_name == 'table game') {
                $game_id = 126;
                $game_type = 'TABLE';
            } else {
                $game_id = 125;
                $game_type = 'Slot';
            }


            $game_name = $objPHPExcel->getActiveSheet()->getCell("A" . $j)->getValue();
            $data = [
                'kind_id' => $kind_id,
                'game_id' => $game_id,
                'game_name' => $game_name,
                'type' => $game_type,
                'alias' => str_replace(' ', '', $game_name),
                'sort' => $j,
                'rename' => $game_name,
            ];

            //echo json_encode($data, JSON_UNESCAPED_UNICODE).PHP_EOL;
            //print_r($data);
            $this->db->table('game_3th')->updateOrInsert(['kind_id' => $data['kind_id'], 'game_id' => $data['game_id']], $data);
        }
        die;
    }

    private function addYGGGames()
    {
        $objReader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');
        $objPHPExcel = $objReader->load(ROOT_PATH . '/data/game/ygg.xlsx');
        $sheet = $objPHPExcel->getSheet(0);   //excel中的第一张sheet
        $highestRow = $sheet->getHighestRow();       // 取得总行数
        for ($j = 1; $j <= $highestRow; $j++) {
            $kind_id = trim($objPHPExcel->getActiveSheet()->getCell("E" . $j)->getValue());
            if (is_null($kind_id)) {
                continue;
            }
            if ($j == 1) {
                continue;
            }

            $data = [
                'kind_id' => $kind_id,
                'game_id' => 108,
                'game_name' => $objPHPExcel->getActiveSheet()->getCell("A" . $j)->getValue(),
                'type' => 'Slot',
                'alias' => str_replace(' ', '', $objPHPExcel->getActiveSheet()->getCell("A" . $j)->getValue()),
                'sort' => $j,
                'rename' => $objPHPExcel->getActiveSheet()->getCell("B" . $j)->getValue(),
            ];

            //echo json_encode($data, JSON_UNESCAPED_UNICODE).PHP_EOL;
            //print_r($data);
            $this->db->table('game_3th')->updateOrInsert(['kind_id' => $data['kind_id'], 'game_id' => $data['game_id']], $data);
        }
        die;
    }

    private function addAWSGames()
    {
        $objReader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');
        $objPHPExcel = $objReader->load(ROOT_PATH . '/data/game/aws.xlsx');
        $sheet = $objPHPExcel->getSheet(0);   //excel中的第一张sheet
        $highestRow = $sheet->getHighestRow();       // 取得总行数
        for ($j = 1; $j <= $highestRow; $j++) {
            $type = trim($objPHPExcel->getActiveSheet()->getCell("B" . $j)->getValue());
            if (is_null($type)) {
                continue;
            }
            if ($type == 'SLOT') {
                $game_type = 'SLOT';
                $game_id = '106';
            } else {
                $game_type = 'TABLE';
                $game_id = '107';
            }

            $data = [
                'kind_id' => $objPHPExcel->getActiveSheet()->getCell("C" . $j)->getValue(),
                'game_id' => $game_id,
                'game_name' => $objPHPExcel->getActiveSheet()->getCell("D" . $j)->getValue(),
                'type' => $game_type,
                'alias' => str_replace(' ', '', $objPHPExcel->getActiveSheet()->getCell("D" . $j)->getValue()),
                'sort' => $j,
                'rename' => $objPHPExcel->getActiveSheet()->getCell("E" . $j)->getValue(),
            ];

            //echo json_encode($data, JSON_UNESCAPED_UNICODE).PHP_EOL;
            //print_r($data);
            $this->db->table('game_3th')->updateOrInsert(['kind_id' => $data['kind_id'], 'game_id' => $data['game_id']], $data);
        }
        die;
    }

    private function addAviaGames()
    {
        $objReader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');
        $objPHPExcel = $objReader->load(ROOT_PATH . '/data/game/avia.xlsx');
        $sheet = $objPHPExcel->getSheet(0);   //excel中的第一张sheet
        $highestRow = $sheet->getHighestRow();       // 取得总行数
        for ($j = 1; $j <= $highestRow; $j++) {
            $type = trim($objPHPExcel->getActiveSheet()->getCell("A" . $j)->getValue());
            if (is_null($type)) {
                continue;
            }

            $data = [
                'kind_id' => $objPHPExcel->getActiveSheet()->getCell("A" . $j)->getValue(),
                'game_id' => 96,
                'game_name' => $objPHPExcel->getActiveSheet()->getCell("C" . $j)->getValue(),
                'type' => 'ESPORTS',
                'alias' => str_replace(' ', '', $objPHPExcel->getActiveSheet()->getCell("C" . $j)->getValue()),
                'sort' => $j,
                'rename' => $objPHPExcel->getActiveSheet()->getCell("B" . $j)->getValue(),
            ];

            //echo json_encode($data, JSON_UNESCAPED_UNICODE).PHP_EOL;
            //print_r($data);
            $this->db->table('game_3th')->updateOrInsert(['kind_id' => $data['kind_id'], 'game_id' => $data['game_id']], $data);
        }
        die;
    }

    private function addUGGames()
    {
        $objReader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');
        $objPHPExcel = $objReader->load(ROOT_PATH . '/data/game/ug.xlsx');
        $sheet = $objPHPExcel->getSheet(0);   //excel中的第一张sheet
        $highestRow = $sheet->getHighestRow();       // 取得总行数
        for ($j = 1; $j <= $highestRow; $j++) {
            $type = trim($objPHPExcel->getActiveSheet()->getCell("A" . $j)->getValue());
            if (is_null($type)) {
                continue;
            }

            $data = [
                'kind_id' => $objPHPExcel->getActiveSheet()->getCell("A" . $j)->getValue(),
                'game_id' => 95,
                'game_name' => $objPHPExcel->getActiveSheet()->getCell("B" . $j)->getValue(),
                'type' => 'SPORT',
                'alias' => str_replace(' ', '', $objPHPExcel->getActiveSheet()->getCell("B" . $j)->getValue()),
                'sort' => $j,
                'rename' => $objPHPExcel->getActiveSheet()->getCell("C" . $j)->getValue(),
            ];

            //echo json_encode($data, JSON_UNESCAPED_UNICODE).PHP_EOL;
            //print_r($data);
            $this->db->table('game_3th')->updateOrInsert(['kind_id' => $data['kind_id'], 'game_id' => $data['game_id']], $data);
        }
        die;
    }

    private function addFCGames()
    {
        $objReader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');
        $objPHPExcel = $objReader->load(ROOT_PATH . '/data/game/fc.xlsx');
        $sheet = $objPHPExcel->getSheet(0);   //excel中的第一张sheet
        $highestRow = $sheet->getHighestRow();       // 取得总行数
        for ($j = 2; $j <= $highestRow; $j++) {
            $kind_id = $objPHPExcel->getActiveSheet()->getCell("A" . $j)->getValue();
            if ($kind_id < 21000 || $kind_id > 22036) {
                continue;
            }
            if ($kind_id < 22000) {
                $game_id = 94;
                $type = 'fish';
            } else {
                $game_id = 93;
                $type = 'slot';
            }
            $data = [
                'kind_id' => $kind_id,
                'game_id' => $game_id,
                'game_name' => $objPHPExcel->getActiveSheet()->getCell("C" . $j)->getValue(),
                'type' => $type,
                'alias' => str_replace([' ', '-', '_', '\''], '', $objPHPExcel->getActiveSheet()->getCell("C" . $j)->getValue()),
                'sort' => $j,
                'rename' => $objPHPExcel->getActiveSheet()->getCell("B" . $j)->getValue(),
            ];
            if (empty($data['kind_id'])) {
                continue;
            }
            //echo json_encode($data, JSON_UNESCAPED_UNICODE).PHP_EOL;
            //print_r($data);
            $this->db->table('game_3th')->updateOrInsert(['kind_id' => $data['kind_id'], 'game_id' => $data['game_id']], $data);
        }
        die;
    }

    private function addCGGames()
    {
        $objReader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');
        $objPHPExcel = $objReader->load(ROOT_PATH . '/data/game/cg.xlsx');
        $sheet = $objPHPExcel->getSheet(0);   //excel中的第一张sheet
        $highestRow = $sheet->getHighestRow();       // 取得总行数
        $typeArrays = [
            '老虎机' => ['game_id' => 91, 'type' => 'slot'],
            '棋牌' => ['game_id' => 92, 'type' => 'pvp']
        ];
        for ($j = 2; $j <= $highestRow; $j++) {
            $type = $objPHPExcel->getActiveSheet()->getCell("B" . $j)->getValue();
            $data = [
                'kind_id' => $objPHPExcel->getActiveSheet()->getCell("D" . $j)->getValue(),
                'game_id' => $typeArrays[$type]['game_id'],
                'game_name' => $objPHPExcel->getActiveSheet()->getCell("E" . $j)->getValue(),
                'type' => $typeArrays[$type]['type'],
                'alias' => str_replace([' ', '-', '_', '\''], '', $objPHPExcel->getActiveSheet()->getCell("e" . $j)->getValue()),
                'sort' => $j,
                'rename' => $objPHPExcel->getActiveSheet()->getCell("C" . $j)->getValue(),
            ];
            if (empty($data['kind_id'])) {
                continue;
            }
            //echo json_encode($data, JSON_UNESCAPED_UNICODE).PHP_EOL;
            //print_r($data);
            $this->db->table('game_3th')->updateOrInsert(['kind_id' => $data['kind_id'], 'game_id' => $data['game_id']], $data);
        }
        die;
    }

    private function addATGames()
    {
        $objReader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');
        $objPHPExcel = $objReader->load(ROOT_PATH . '/data/game/at.xlsx');
        $sheet = $objPHPExcel->getSheet(0);   //excel中的第一张sheet
        $highestRow = $sheet->getHighestRow();       // 取得总行数
        $highestColumn = $sheet->getHighestColumn();   // 取得总列数
        $typeArrays = ['fish' => 89, 'slot' => 88, 'coc' => 90];
        for ($j = 1; $j <= $highestRow; $j++) {
            $type = trim($objPHPExcel->getActiveSheet()->getCell("H" . $j)->getValue());
            if (!in_array($type, array_keys($typeArrays)) || is_null($type)) {
                continue;
            }

            $data = [
                'kind_id' => $objPHPExcel->getActiveSheet()->getCell("A" . $j)->getValue(),
                'game_id' => $typeArrays[$type],
                'game_name' => $objPHPExcel->getActiveSheet()->getCell("C" . $j)->getValue(),
                'type' => $type,
                'alias' => str_replace(' ', '', $objPHPExcel->getActiveSheet()->getCell("C" . $j)->getValue()),
                'sort' => $j,
                'rename' => $objPHPExcel->getActiveSheet()->getCell("B" . $j)->getValue(),
            ];
            /*if(empty($data['game_name'])){
                $data['game_name'] = $objPHPExcel->getActiveSheet()->getCell("C" . $j)->getValue();
            }*/
            if ($data['kind_id'] == 'au10001' || $data['kind_id'] == 'lobby01') {
                continue;
            }
            //echo json_encode($data, JSON_UNESCAPED_UNICODE).PHP_EOL;
            //print_r($data);
            $this->db->table('game_3th')->updateOrInsert(['kind_id' => $data['kind_id'], 'game_id' => $data['game_id']], $data);
        }
        die;
    }

    private function addKMQMGames()
    {
        $objReader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');
        $objPHPExcel = $objReader->load(ROOT_PATH . '/data/game/kmqm.xlsx');
        $sheet = $objPHPExcel->getSheet(0);   //excel中的第一张sheet
        $highestRow = $sheet->getHighestRow();       // 取得总行数
        for ($j = 2; $j <= $highestRow; $j++) {
            $type = trim($objPHPExcel->getActiveSheet()->getCell("A" . $j)->getValue());
            if (is_null($type)) {
                continue;
            }

            $data = [
                'kind_id' => $objPHPExcel->getActiveSheet()->getCell("D" . $j)->getValue(),
                'game_id' => 77,
                'game_name' => $objPHPExcel->getActiveSheet()->getCell("B" . $j)->getValue(),
                'type' => 'QP',
                'alias' => str_replace([' ', '-', '_', '\''], '', $objPHPExcel->getActiveSheet()->getCell("B" . $j)->getValue()),
                'sort' => $j,
                'rename' => $objPHPExcel->getActiveSheet()->getCell("C" . $j)->getValue(),
            ];

            //echo json_encode($data, JSON_UNESCAPED_UNICODE).PHP_EOL;
            //print_r($data);
            $this->db->table('game_3th')->updateOrInsert(['kind_id' => $data['kind_id'], 'game_id' => $data['game_id']], $data);
        }
        die;
    }

    private function replaceImage()
    {
        $dir = ROOT_PATH . '/data/images/kmqm/';
        if ($handle = opendir($dir)) {
            while (false !== ($file = readdir($handle))) {
                if ($file != "." && $file != "..") {
                    $newfile = str_replace([' ', '_', '\'', '-'], '', $file);
                    echo $newfile . PHP_EOL;
                    rename($dir . $file, $dir . $newfile);
                }
            }
            closedir($handle);
        }
        die;
    }

    //PG电子
    private function addPgGames()
    {
        $objReader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');
        $objPHPExcel = $objReader->load(ROOT_PATH . '/data/game/pg.Xlsx');
        $sheet = $objPHPExcel->getSheet(0);   //excel中的第一张sheet
        $highestRow = $sheet->getHighestRow();       // 取得总行数
        $highestColumn = $sheet->getHighestColumn();   // 取得总列数

        for ($j = 2; $j <= $highestRow; $j++) {
            $kind_id = $objPHPExcel->getActiveSheet()->getCell("F" . $j)->getValue();
            if (is_null($kind_id) || $kind_id == 0) {
                continue;
            }
            $data = [
                'kind_id' => $kind_id,
                'game_id' => 76,
                'game_name' => $objPHPExcel->getActiveSheet()->getCell("B" . $j)->getValue(),
                'type' => 'Slot',
                'alias' => str_replace(' ', '', $objPHPExcel->getActiveSheet()->getCell("B" . $j)->getValue()),
                'sort' => $j,
                'rename' => $objPHPExcel->getActiveSheet()->getCell("C" . $j)->getValue(),
            ];
            if (empty($data['game_name'])) {
                $data['game_name'] = $data['rename'];
            }
            //echo json_encode($data, JSON_UNESCAPED_UNICODE).PHP_EOL;
            //print_r($data);die;
            $this->db->table('game_3th')->updateOrInsert(['kind_id' => $data['kind_id'], 'game_id' => $data['game_id']], $data);
        }
        die;
    }

    private function addCq9Games()
    {
        $objReader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');
        $objPHPExcel = $objReader->load(ROOT_PATH . '/data/game/cq9.Xlsx');
        $sheet = $objPHPExcel->getSheet(0);   //excel中的第一张sheet
        $highestRow = $sheet->getHighestRow();       // 取得总行数
        $highestColumn = $sheet->getHighestColumn();   // 取得总列数
        $typeArrays = ['Fishing' => 75, 'RNG' => 74, 'Arcade' => 85, 'Table' => 86];
        for ($j = 3; $j <= $highestRow; $j++) {
            $type = trim($objPHPExcel->getActiveSheet()->getCell("J" . $j)->getValue());
            if (!in_array($type, array_keys($typeArrays)) || is_null($type)) {
                continue;
            }

            $data = [
                'kind_id' => $objPHPExcel->getActiveSheet()->getCell("G" . $j)->getValue(),
                'game_id' => $typeArrays[$type],
                'game_name' => $objPHPExcel->getActiveSheet()->getCell("C" . $j)->getValue(),
                'type' => $type,
                'alias' => str_replace([' ', '-', '_', '\''], '', $objPHPExcel->getActiveSheet()->getCell("C" . $j)->getValue()),
                'sort' => $j,
                'rename' => $objPHPExcel->getActiveSheet()->getCell("D" . $j)->getValue(),
            ];

            //echo json_encode($data, JSON_UNESCAPED_UNICODE).PHP_EOL;
            //print_r($data);
            $this->db->table('game_3th')->updateOrInsert(['kind_id' => $data['kind_id'], 'game_id' => $data['game_id']], $data);
        }
        die;
    }

    public function registerSboAgent()
    {
        $type = 'SBO';
        $gameClass = \Logic\GameApi\GameApi::getApi($type, 0);
        $gameClass->registerAgent();
    }

    public function soap()
    {
        $fields = [
            'ExternalUserId' => 'game164e432',
            'Nickname' => 'game26tes3ee13',
            'Currency' => 'THB',
            'Country' => 'TH',
            'Birthdate' => '2000-01-01',
            'Registration' => '2021-01-01',
            'BrandId' => 'test',
            'Language' => 'th-TH',
            'IP' => '',
            'Locked' => false,
            'Gender' => 'm',
        ];
        /*$fields = [
            'ExternalUserId' => 'game164e432',
        ];*/
        $login = 'qgtapi';
        $password = 'XmizdyrOpWIzYtkzYXsNkuyWn';
        $wsdl = "https://agastage2.playngonetwork.com:48961/CasinoGameService?singlewsdl";
        $wsdl2 = "https://agastage2.playngonetwork.com:48961/CasinoGameService";

        try {
            $options["location"] = $wsdl2;
            //$options["uri"] = $wsdl;
            $options['trace'] = 1;
            $options["login"] = $login;
            $options["password"] = $password;
            //$options["soap_version"] = SOAP_1_1;

            $client = new \SoapClient($wsdl, $options);

            // $types = $client->__getTypes();var_dump($types);die;

            //$result = $client->RegisterUser(['UserInfo'=>$fields]);
            //$result = $client->Balance($fields);
            $result = $client->__soapCall('RegisterUser', [['UserInfo' => $fields]]);
            //$result = $client->__soapCall('Balance', [$fields]);

            /* $xml2 = str_replace('s:','', $result);
             $xml = simplexml_load_string($xml2)->children();
             $json = json_encode($xml);
             $result = json_decode($json, true);*/
            var_dump($client->__getLastResponseHeaders());
            var_dump($result);
            die;
            //var_dump($client->__getLastRequest());die;

        } catch (SoapFault $fault) {
            var_dump($client->__getLastResponseHeaders());
            var_dump($client->__getLastRequest());
            var_dump($client->__getLastResponse());
            //echo  $fault->faultcode,$fault->faultstring;
            var_dump($fault->getMessage());
            die;
        }
        exit;
    }


    private function addJDBGames()
    {
        $objReader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');
        $objPHPExcel = $objReader->load(ROOT_PATH . '/data/game/JDB.Xlsx');
        $sheet = $objPHPExcel->getSheet(0);   //excel中的第一张sheet
        $highestRow = $sheet->getHighestRow();       // 取得总行数
        $gTypeArr = [
            0 => ['game_id' => 70, 'type' => 'Slot'],
            7 => ['game_id' => 71, 'type' => 'Fishing'],
            9 => ['game_id' => 97, 'type' => 'Arcade'],
            18 => ['game_id' => 87, 'type' => 'qp'],
        ];
        for ($j = 6; $j <= $highestRow; $j++) {
            if ($j > 160) {
                break;
            }
            $gtype = $objPHPExcel->getActiveSheet()->getCell("B" . $j)->getValue();
            if (!in_array($gtype, [0, 7, 9, 18]) || is_null($gtype)) {
                continue;
            }
            $data = [
                'kind_id' => $objPHPExcel->getActiveSheet()->getCell("C" . $j)->getValue(),
                'game_id' => $gTypeArr[$gtype]['game_id'],
                'game_name' => $objPHPExcel->getActiveSheet()->getCell("E" . $j)->getValue(),
                'type' => $gTypeArr[$gtype]['type'],
                'alias' => str_replace([' ', '-', '_', '\''], '', $objPHPExcel->getActiveSheet()->getCell("E" . $j)->getValue()),
                'sort' => $j,
                'rename' => $objPHPExcel->getActiveSheet()->getCell("D" . $j)->getValue(),
            ];
            //echo json_encode($data, JSON_UNESCAPED_UNICODE).PHP_EOL;
            //print_r($data);die;
            $this->db->table('game_3th')->updateOrInsert(['kind_id' => $data['kind_id'], 'game_id' => $data['game_id']], $data);
        }
        die;
    }

    /**
     * PNG电子
     */
    private function addPngGames()
    {
        $ids = '100004,100005,100040,100099,100105,100106,100107,100108,100196,100199,100200,100225,100237,100238,100241,100242,100243,100245,100246,100250,100251,100253,100259,100262,100277,100278,100282,100283,100284,100285,100286,100287,100288,100290,100291,100292,100293,100294,100297,100298,100300,100302,100303,100304,100305,100307,100309,100310,100311,100312,100321,100325,100326';
        $idsArr = explode(',', $ids);

        $objReader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');
        $objPHPExcel = $objReader->load(ROOT_PATH . '/data/game/png.xlsx');
        $sheet = $objPHPExcel->getSheet(0);   //excel中的第一张sheet
        $highestRow = $sheet->getHighestRow();       // 取得总行数
        // $highestColumn = $sheet->getHighestColumn();   // 取得总列数
        $str = '';
        for ($j = 3; $j <= $highestRow; $j++) {
            $game_id = $objPHPExcel->getActiveSheet()->getCell("E" . $j)->getValue();
            if (!in_array($game_id, $idsArr)) {
                continue;
            }
            $data = [
                'kind_id' => $game_id,//$objPHPExcel->getActiveSheet()->getCell("F" . $j)->getValue(),
                'game_id' => 68,
                'game_name' => $objPHPExcel->getActiveSheet()->getCell("AE" . $j)->getValue(),
                'type' => 'Slot',
                'alias' => str_replace(' ', '', $objPHPExcel->getActiveSheet()->getCell("A" . $j)->getValue()),
                'sort' => $j,
                'rename' => $objPHPExcel->getActiveSheet()->getCell("AC" . $j)->getValue(),
            ];

            $this->db->table('game_3th')->insert($data);
        }
    }

    private function addEovGames()
    {
        echo 'addEovGames' . PHP_EOL;
        return $this->lang->set(0);
    }

    //PP游戏入库
    private function addPPGames()
    {

        //超管
        $db = \DB::connection('default');

        $type = 'PP';
        $gameClass = \Logic\GameApi\GameApi::getApi($type, 0);
        $menuList = $gameClass->getListGames();
        //print_r($menuList);die;
        if ($menuList['error'] == 0) {
            /*
             * {
                    "gameID": "vs20olympgate",
                    "gameName": "Gates of Olympus",
                    "gameTypeID": "vs",
                    "typeDescription": "Video Slots",
                    "technology": "html5",
                    "platform": "MOBILE,DOWNLOAD,WEB",
                    "demoGameAvailable": true,
                    "aspectRatio": "16:9",
                    "technologyID": "H5",
                    "gameIdNumeric": 1605284987,
                    "jurisdictions": ["RS","X1","IT","UA", "MT","RO","EE","DE","IM","GR","LV","GG","ZA","99","UK","CO","BG","ES","NL","LT","DK","SE","PT","66","CH","BY","CZ","ON"],
                    "frbAvailable": true,
                    "variableFrbAvailable": true,
                    "lines": 20,
                    "dataType": "RNG"
                },
              游戏产品组合类型
               可用选项：
                RNG - 主产品组合游戏（视频老虎机、经典老虎机等）
                LC – 真人娱乐场产品组合
                R2 – 捕鱼游戏产品组合
                VSB - 虚拟体育博彩产品组合
                R6 - R6 游戏产品组合
             */
            $type = [
                'RNG' => ['type' => 'Slot', 'id' => 64],
                'R2' => ['type' => 'Fishing', 'id' => 65],
                'LC' => ['type' => 'ECasino', 'id' => 66],
            ];
            foreach ($menuList['gameList'] as $key => $menu) {
                if (!in_array($menu['dataType'], ['RNG', 'LC', 'R2'])) {
                    continue;
                }
                $data = [
                    'kind_id' => $menu['gameID'],
                    'game_id' => $type[$menu['dataType']]['id'],
                    'game_name' => $menu['gameName'],
                    'type' => $type[$menu['dataType']]['type'],
                    'alias' => str_replace(' ', '', $menu['gameName']),
                    'game_img' => 'https://api.prerelease-env.biz/game_pic/square/200/' . $menu['gameID'] . '.png',
                    'sort' => $key
                ];

                $data['rename'] = $menu['gameName'];
                //$db->table('game_3th')->insert($data);
                //echo $key.PHP_EOL;
            }
        }
        return $this->lang->set(0);
    }

    //JILI游戏入库
    private function addJILIGames()
    {

        $db = \DB::connection('default');

        $type = 'JILI';
        $gameClass = \Logic\GameApi\GameApi::getApi($type, 0);
        $menuList = $gameClass->getListGames();
        if ($menuList['ErrorCode'] == 0) {
            /*
             * {
                    "GameId": 1,
                    "name": {
                        "en-US": "Royal Fishing",
                        "zh-CN": "钱龙捕鱼",
                        "zh-TW": "錢龍捕魚"
                    },
                    "GameCategoryId": 5 游戏类型：1: 电子 5: 捕鱼
                },
             */
            foreach ($menuList['Data'] as $key => $menu) {
                if (!in_array($menu['GameCategoryId'], [1, 5])) {
                    continue;
                }
                $db->table('game_3th')->where(['kind_id' => (string)$menu['GameId']])->whereIn('game_id', [62, 63])->update(['game_name' => $menu['name']['en-US']]);
                /*$data = [
                    'kind_id'   => $menu['GameId'],
                    'game_id'   => $menu['GameCategoryId'] == 1 ? 62 : 63,
                    'game_name' => $menu['name']['zh-CN'],
                    'type'      => $menu['GameCategoryId'] == 1 ? 'Slot' : 'Fishing',
                    'alias'     => str_replace(' ', '', $menu['name']['en-US']),
                    'sort'      => $menu['GameId']
                ];

                $data['rename'] = $menu['name']['zh-CN'];
                $db->table('game_3th')->insert($data);*/
            }
        }
        return $this->lang->set(0);
    }

    private function getPPImages()
    {

    }

    private function download($url, $path = 'images/')
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        $file = curl_exec($ch);
        curl_close($ch);
        $filename = pathinfo($url, PATHINFO_BASENAME);
        $resource = fopen(ROOT_PATH . '/data/' . $path . $filename, 'a');
        fwrite($resource, $file);
        fclose($resource);
    }

    private function addHBGames()
    {
        $objReader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');
        $objPHPExcel = $objReader->load(ROOT_PATH . '/data/game/hb.xlsx');
        $sheet = $objPHPExcel->getSheet(0);   //excel中的第一张sheet
        $highestRow = $sheet->getHighestRow();       // 取得总行数
        $highestColumn = $sheet->getHighestColumn();   // 取得总列数
        $typeArrays = ['Slot Game' => 128, 'Table Game' => 129, 'Video Poker' => 130];
        for ($j = 3; $j <= $highestRow; $j++) {
            $type = trim($objPHPExcel->getActiveSheet()->getCell("E" . $j)->getValue());
            if (!in_array($type, array_keys($typeArrays)) || is_null($type)) {
                continue;
            }
            $data = [
                'kind_id' => $objPHPExcel->getActiveSheet()->getCell("A" . $j)->getValue(),
                'game_id' => $typeArrays[$type],
                'game_name' => $objPHPExcel->getActiveSheet()->getCell("B" . $j)->getValue(),
                'type' => $type,
                'alias' => str_replace(' ', '', $objPHPExcel->getActiveSheet()->getCell("B" . $j)->getValue()),
                'sort' => $j,
                'rename' => $objPHPExcel->getActiveSheet()->getCell("C" . $j)->getValue(),
            ];
            $this->db->table('game_3th')->updateOrInsert(['kind_id' => $data['kind_id'], 'game_id' => $data['game_id']], $data);
        }
        die;
    }

    /**
     * 上传hb图片
     */
    private function addHBImgs()
    {
        $data = DB::table('game_3th')->whereIn('game_id', [128, 129, 130])->select('kind_id')->get()->toArray();
        foreach ($data as $val) {
            \DB::table('game_3th')->where('kind_id', $val->kind_id)->update(['game_img' => 'https://img.caacaya.com/lodi/game/vert/hb/' . $val->kind_id . '.png']);
        }
    }

    /**
     * 上传EVORT图片
     */
    private function addEvoRTImgs()
    {
        $data = DB::table('game_3th')->where('game_id', 138)->select('kind_id', 'game_name')->get()->toArray();
        foreach ($data as $val) {
            $name = str_replace(' ', '_', strtolower($val->game_name));
            $name = str_replace(':', '', $name);
            $name = str_replace('\'', '_', $name);
            \DB::table('game_3th')->where('kind_id', $val->kind_id)->update(['game_img' => 'https://img.caacaya.com/lodi/game/vert/evort/' . $name . '.png']);
        }
    }

};