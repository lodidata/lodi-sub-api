<?php
use LotteryPlay\Struct;
use PHPUnit\Framework\TestCase;

class StructTest extends TestCase {

    public function testRun() {
        $struct = new Struct;
        // $data = $struct->setCate(10);

        $data = $struct->run(10);

        $data = $struct->run(1);

        $data = $struct->run(39);

        $data = $struct->run(5);

        $data = $struct->run(24);
        // print_r($data);
        $this->assertEquals(1, 1);
    }

    public function testOdds() {
        $struct = new Struct;
        // $data = $struct->setCate(10);
        $data = $struct->odds(10);

        $data = $struct->odds(1);

        $data = $struct->odds(39);

        $data = $struct->odds(5);

        $data = $struct->odds(24);
        // print_r($data);
        $this->assertEquals(1, 1);
    }

    public function testMerge() {
        $struct = new Struct;
        // $data = $struct->setCate(10);
        $odds = $struct->odds(10);
        $structs = $struct->run(10);
        $data = $struct->merge(10, $structs, $odds);
        // print_r($data);
        $this->assertEquals(1, 1);
    }

    public function testData() {
        $struct = new Struct;
        $list = [
            // 10, 1, 39 ,5 ,24

            1
        ];
        $data = [];

        $oldData = [];
        if (is_file('lottery_play_struct.csv')) {
            $file = fopen("lottery_play_struct.csv","r");
            while(! feof($file))
            {
                $csvData[] = fgetcsv($file);
            }
            // $csvData = fgetcsv($file);
            fclose($file);
            // print_r($csvData);

            
            foreach ($csvData as $v) {
                if (empty($v)) {
                    continue;
                }
                if (!isset($oldData[$v[1]])) {
                    $oldData[$v[1]] = [];
                }

                if ($v[10] != '直播') {
                    continue;
                }
                $oldData[$v[1]][] = ['plid'=>$v[9], 'model' => $v[10], 'name' => $v[2],'pltx1' => $v[11], 'pltx2' => $v[12]];
            }
        }

        foreach ($list as $pid) {
            $data = array_merge($data, $struct->sqlData($pid, isset($oldData[$pid]) ? $oldData[$pid] : []));
        }
        // return;
        $contents = $struct->dataToInsert('lottery_play_struct', $data);
        file_put_contents('lottery_play_struct.sql', $contents);

        $data = [];
        foreach ($list as $pid) {
            $data = array_merge($data, $struct->odds($pid));
        }
        $contents = $struct->dataToInsert('lottery_play_base_odds', $data);
        file_put_contents('lottery_play_base_odds.sql', $contents);
        $this->assertEquals(1, 1);
    }

    public function testChatRun() {
        $struct = new Struct;
        $structs = $struct->chatRun(10);
        $this->assertEquals(1, 1);
    }

    public function testVedioRun() {
        $struct = new Struct;
        $structs = $struct->vedioRun(1);
        // print_r($structs);
        $this->assertEquals(1, 1);
    }
}