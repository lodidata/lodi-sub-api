<?php
use LotteryPlay\Logic;
use PHPUnit\Framework\TestCase;

class RangeTest extends TestCase {


    public function matrix($playNumbers, $index = 0) {
        $list = [];        
        if (is_array($playNumbers[$index])) {
            foreach ($playNumbers[$index] as $val) {
                if (isset($playNumbers[$index + 1])) {
                    $res = $this->matrix($playNumbers, $index + 1);
                    if (!empty($res)) {
                        foreach ($res as $v) {
                            $list[] = array_merge([$val], (is_array($v) ? $v : [$v]));
                        }
                    } else {
                        $list[] = [$val];
                    }
                } else {
                    $list[] = [$val];
                }
            }
        }
        return $list;
    }

    public function testMatrix() {
        $playNumbers = [
            [1, 2, 3, 4],
            [0],
            [0],
            [1,2,3],
            [0],
        ];

        $list = $this->matrix($playNumbers);
        // print_r($list);
        $this->assertEquals(12, count($list));
    }

    public function testArrayDiff() {
        $a = [1, 2, 3];
        $b = [1, 2, 3];

        $this->assertEquals(true, empty(array_diff($a, $b)));

        $a = [1, 2, 3];
        $b = [3, 2, 1];

        $this->assertEquals(true, empty(array_diff($a, $b)));


        $a = [1, 2, 3, 5];
        $b = [3, 2, 1, 4];
        $this->assertEquals(false, empty(array_diff($a, $b)), print_r(array_diff($a, $b), true));


        $a = [1, 2, 3];
        $b = [3, 2, 1, 4, 5];
        $this->assertEquals(true, empty(array_diff($a, $b)), print_r(array_diff($a, $b), true));

        $a = [0, 2, 3];
        $b = [3, 2, 1, 4, 5];
        $this->assertEquals(true, !empty(array_diff($a, $b)), print_r(array_diff($a, $b), true));
    }

    public function testHaveCount() {
        $a = [1 ,2, 3 ,3, 0];
        $list = array_count_values($a);

        $this->assertEquals(2, $list[3]);
        $this->assertEquals(0, isset($list[5]) ? $list[5] : 0);
    }


    public function testChunkSplit() {
        $a = '1234';
        $a = explode('|', chunk_split($a, 1, '|'));
        array_pop($a);
        // echo $a, PHP_EOL;
        $this->assertEquals(4, count($a));
    }
}