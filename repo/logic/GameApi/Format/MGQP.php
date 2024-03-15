<?php
/**
 * User: ny
 * Date: 2019-05-04
 * Time: 16:01
 * Des :
 */

namespace Logic\GameApi\Format;

/**
 * MG棋牌
 * Class MG
 * @package Logic\GameApi\Format
 */
class MGQP extends MG {

    /**
     * 分类ID
     * @var int
     */
    public $game_id = 119;

    public $gameType = 'qp';
}