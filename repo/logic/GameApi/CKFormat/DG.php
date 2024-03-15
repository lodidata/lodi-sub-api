<?php

namespace Logic\GameApi\CKFormat;

/**
 * DG视讯
 */
class DG extends CKFORMAT
{

    /**
     * 分类ID
     * @var int
     */
    protected $game_id = 103;
    /**
     * 订单表
     * @var string
     */
    protected $order_table = 'game_order_dg';

    protected $table_fields = [
        'orderNumber' => 'order_number',
        'userName' => 'userName',
        'betAmount' => 'betPoints*100',
        'validAmount' => 'betPoints*100',
        'profit' => 'profit*100',
        'whereTime' => 'betTime',
        'startTime' => 'betTime',
        'endTime' => 'calTime',
        'gameRoundId' => 'playId',
        'gameCode' => 'GameId',
        'gameName' => 'tableId'
    ];

    public function getAllGameList()
    {
        return [];
    }


    /**
     * 游戏名称
     * @param $tableid
     * @return mixed|string
     */
    protected function getGameName($tableid)
    {
        $tables = [
            '10101' => 'DG01',
            '10102' => 'DG02',
            '10103' => 'DG03',
            '10104' => 'DG05',
            '10105' => 'DG06',
            '10106' => 'DG07',
            '10107' => 'DG08',
            '10108' => 'DG09',
            '10109' => 'DG10',
            '10802' => 'DG11',
            '10803' => 'DG12',
            '10201' => 'DG13',
            '10202' => 'DG15',
            '10301' => 'DG16',
            '10302' => 'DG17',
            '10701' => 'DG18',
            '11101' => 'DG19',
            '11601' => 'DG20',
            '10401' => 'DG21',
            '10501' => 'DG22',
            '30101' => 'CT01',
            '30102' => 'CT02',
            '30103' => 'CT03',
            '30105' => 'CT05',
            '30301' => 'CT06',
            '30401' => 'CT08',
            '30601' => 'CT10',
            '40101' => 'CT21',
            '40102' => 'CT22',
            '40103' => 'CT28',
            '40501' => 'CT27',
            '50101' => 'E1',
            '50102' => 'E3',
            '50103' => 'E7',
            '50401' => 'R1',
            '70101' => 'GC01',
            '70102' => 'GC02',
            '70103' => 'GC03',
            '70105' => 'GC05',
            '70106' => 'GC06',
            '70301' => 'GC07',
            '70401' => 'GC08',
            '70501' => 'GC09',
            '70701' => 'GC10',
            '71401' => 'GC11',
            '70201' => 'GC13',
            '84101' => 'Q1',
            '84102' => 'Q2',
            '84103' => 'Q3',
            '84104' => 'Q5',
            '84105' => 'Q6',
            '84106' => 'Q7',
        ];
        return $tables[$tableid]?? 'DG';
    }
}