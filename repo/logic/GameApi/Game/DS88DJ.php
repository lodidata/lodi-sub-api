<?php

namespace Logic\GameApi\Game;

use Logic\Define\Cache3thGameKey;
use Logic\GameApi\GameApi;
use Logic\GameApi\GameToken;
use Model\FundsChild;
use Utils\Client;
use Utils\Curl;
use Model\user as UserModel;

/**
 * DS88斗鸡
 * Class DS88DJ
 * @package Logic\GameApi\Game
 */
class DS88DJ extends \Logic\GameApi\Game\LDAPI
{

    protected $type = 'SABONG';

    protected $orderDataTypes = [
        'DS88DJ-SABONG' => [ 'type' => 'DS88DJ', 'id' => 184],
    ];

}

