<?php
namespace LotteryPlay\ssc;
use LotteryPlay\BaseFormat;

class SSCFormat extends BaseFormat {


    public static function formatComb60($context, $oneLength = 1, $twoLength = 3) {
        $rules = [
            2 => '/^(([0-9|]){1,19}),([0-9|]{1,19})$/',
        ];

        if (self::checkCode($context, $context->playNumber)) {
            preg_match($rules[2], $context->playNumber, $matches);
            if (!isset($matches[0])) {
                return $context->stopNext()->setReason('下注号码格式不正确#formatComb60');
            }

            $lists = explode(',', $context->playNumber);
            $context->playNumberFirstList = array_unique(explode('|', $lists[0]));
            $context->playNumberTwoList = array_unique(explode('|', $lists[1]));
            
            self::checkNumbers($context, $context->playNumberFirstList);
            self::checkNumbers($context, $context->playNumberTwoList);
            if (count($context->playNumberFirstList) < $oneLength) {
                return $context->stopNext()->setReason('号码格式错误@1 '.count($context->playNumberFirstList).'#'.$oneLength);
            }

            if (count($context->playNumberTwoList) < $twoLength) {
                return $context->stopNext()->setReason('号码格式错误@2 '.count($context->playNumberTwoList).'#'.$oneLength);
                 
            }
        }
    }

    /**
     * 百家乐
     * @param  [type] $context [description]
     * @return [type]          [description]
     */
    public static function formatBaccarat($context) {
        $rules = [
            0 => '/^([(庄)(庄对子)(庄豹子)(庄天王)(闲)(闲对子)(闲豹子)(闲天王)|]{1,})$/',
        ];

        if (self::checkCode($context, $context->playNumber)) {
            preg_match($rules[0], $context->playNumber, $matches);
            if (!isset($matches[0])) {
                return $context->stopNext()->setReason('下注号码格式不正确#formatBaccarat');
            }
            $context->playNumberList = array_unique(explode('|', $context->playNumber));
        }
    }


}