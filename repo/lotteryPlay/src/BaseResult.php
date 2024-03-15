<?php 

namespace LotteryPlay;

abstract class BaseResult {
    // 初始化
    protected static function _init($context) {
        $context->winBetCount = $context->isWin = [];
        foreach ($context->odds as $key => $odds) {
            $context->isWin[$key] = false;
            $context->winBetCount[$key] = 0;
        }
    }
}