<?php
use PHPUnit\Framework\TestCase;
use Hashids\Hashids;

class HashidsTest extends TestCase {

    public function testHash() {


        $hashids = new Hashids('abc', 8, 'abcdefghijklmnopqrstuvwxyz');

        // echo PHP_EOL;
        // echo $hashids->encode(123);
        // echo PHP_EOL;
        $this->assertEquals('ovgrjogw', $hashids->encode(123));
    }
}