<?php
/**
 * User: ny
 * Date: 2019-05-04
 * Time: 16:01
 * Des :
 */

namespace Logic\GameApi\Format;

/**
 * WMATCH棋牌
 * Class WMATCHQP
 * @package Logic\GameApi\Format
 */
class WMATCHQP extends WMATCH {

    /**
     * 分类ID
     * @var int
     */
    public $game_id = 142;

    public $gameType = 'qp';

}