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
 * FC电子
 * Class FACHAI
 * @package Logic\GameApi\Game
 */
class FACHAI extends \Logic\GameApi\Game\LDAPI
{

    protected $type = 'SLOT';

    protected $orderDataTypes = [
        'FACHAI-SLOT' => [ 'type' => 'FACHAI', 'id' => 171],
        'FACHAI-FISH' => [ 'type' => 'FACHAIBY', 'id' => 181],
        'FACHAI-ARCADE' => [ 'type' => 'FACHAIJJ', 'id' => 182],
        'FACHAI-TABLE' => [ 'type' => 'FACHAITAB', 'id' => 183],
    ];

}

