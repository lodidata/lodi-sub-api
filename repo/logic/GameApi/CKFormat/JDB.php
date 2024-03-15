<?php

namespace Logic\GameApi\CKFormat;

use Logic\GameApi\GameToken;
use Model\User;

/**
 * JDB电子
 * Class JDB
 */
class JDB extends CKFORMAT
{
    protected $game_id = 70;
    public $order_table = "game_order_jdb";
    public $game_type = 'JDB';
    protected $field_category_name = 'gType';
    protected $field_category_value ="'0'";

    protected $table_fields = [
        'orderNumber' => 'seqNo',
        'userName' => 'playerId',
        'betAmount' => 'abs(bet)',
        'validAmount' => 'abs(bet)',
        'profit' => 'total',
        'whereTime' => 'gameDate',
        'startTime' => 'gameDate',
        'endTime' => 'gameDate',
        'gameRoundId' => 'roundSeqNo',
        'gameCode' => 'mtype',
    ];

    public function getUserIdByUserAccount($user_account)
    {
        $tid = $this->ci->get('settings')['app']['tid'];
        $site_type = $this->ci->get('settings')['website']['site_type'];
        $user_id = intval(str_replace($tid . ($site_type == 'lodi' ? 'o': 'n' ), '', $user_account));
        return $user_id;
    }

    public function getGameAccountByUserId($user_id)
    {
        $tid = $this->ci->get('settings')['app']['tid'];
        $site_type = $this->ci->get('settings')['website']['site_type'];
        return $tid.($site_type == 'lodi' ? 'o': 'n' ).$user_id;
    }

    public function getGameAccountByUserName($user_name)
    {
        $user_id = \DB::connection('slave')->table('user')->where('name', $user_name)->value('id');
        return $this->getGameAccountByUserId($user_id);
    }

    public function getGameUserNameByAccount($user_account)
    {
        $user_id = $this->getUserIdByUserAccount($user_account);
        $userLogic = new \Logic\User\User($this->ci);
        return $userLogic->getGameUserNameById($user_id);
    }
}