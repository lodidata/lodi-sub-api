<?php
use LotteryPlay\Logic;
use PHPUnit\Framework\TestCase;

class PregTest extends TestCase {

    public function testCode() {
        $matches = null;
        $subject = '1,2,3,4,5';
        preg_match('/^([0-9]{1}),([0-9]{1}),([0-9]{1}),([0-9]{1}),([0-9]{1})$/', $subject, $matches);
        $this->assertEquals(true, isset($matches[0]), $subject);

        $subject = '1,2,3,4,5,6';
        preg_match('/^([0-9]{1}),([0-9]{1}),([0-9]{1}),([0-9]{1}),([0-9]{1})$/', $subject, $matches);
        $this->assertEquals(false, isset($matches[0]), $subject);

        $subject = '11,2,3,4,5';
        preg_match('/^([0-9]{1}),([0-9]{1}),([0-9]{1}),([0-9]{1}),([0-9]{1})$/', $subject, $matches);
        $this->assertEquals(false, isset($matches[0]), $subject);

        $subject = '1,2,3,4,5,';
        preg_match('/^([0-9]{1}),([0-9]{1}),([0-9]{1}),([0-9]{1}),([0-9]{1})$/', $subject, $matches);
        $this->assertEquals(false, isset($matches[0]), $subject);

        $subject = '1,2,3,4';
        preg_match('/^([0-9]{1}),([0-9]{1}),([0-9]{1}),([0-9]{1}),([0-9]{1})$/', $subject, $matches);
        $this->assertEquals(false, isset($matches[0]), $subject);


        $subject = '1 ,2,3,4,5';
        preg_match('/^([0-9]{1}),([0-9]{1}),([0-9]{1}),([0-9]{1}),([0-9]{1})$/', $subject, $matches);
        $this->assertEquals(false, isset($matches[0]), $subject);
    }

    public function testCode2() {
        $matches = null;
        $subject = '1|2|3|4|5|6|7|8|9,2,3,4,5';
        preg_match('/^(([0-9|]){1,17}),([0-9|]{1,17}),([0-9|]{1,17}),([0-9|]{1,17}),([0-9|]{1,17})$/', $subject, $matches);
        $this->assertEquals(true, isset($matches[0]), $subject);

        // $subject = '1|2|3|4|5|6|7|8|9,1|2|3|4|5|6|7|8|9,1|2|3|4|5|6|7|8|9,1|2|3|4|5|6|7|8|9,1|2|3|4|5|6|7|8|9';
        // preg_match('/^([0-9|]{17}),([0-9|]{17}),([0-9|]{17}),([0-9|]{17}),([0-9|]{17})$/', $subject, $matches);
        // $this->assertEquals(true, isset($matches[0]), $subject);
    }

    public function testCode3() {
        // print_r(preg_split("/[|,]+/", '6|7|8|9|10,1|2|3|4|5,1|3|5|7|9,2|4|6|8|10,6|7|8|9|10,1|2|4|5,1|3|7|9,1|2|4|5,1|2|3', -1, PREG_SPLIT_NO_EMPTY));
        $this->assertEquals(1, 1);
    }
}