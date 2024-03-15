<?php

namespace Logic\Lottery;

/**
 * 直推返水比例修改
 */
class DirectBkge extends \Logic\Logic
{
    const MODIFY_DIRECT_BKGE_MQ_KEY = 'user_direct_bkge_increase';

    public function sendMQ()
    {
        $mqMsg = [];
        \Utils\MQServer::send(self::MODIFY_DIRECT_BKGE_MQ_KEY, $mqMsg);
        return true;
    }
}