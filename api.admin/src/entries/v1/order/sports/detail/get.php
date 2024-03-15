<?php
/**
 * vegas2.0
 *
 * @auth *^-^*<dm>
 * @copyright XLZ CO.
 * @package
 * @date 2017/4/25 20:26
 */

use Logic\Admin\BaseController;
use lib\validate\BaseValidate;
use Logic\GameApi\GameApi;

return new class() extends BaseController {
    const TITLE = '体育注单详情';
    const DESCRIPTION = '体育注单详情';

    const QUERY = [
        'id'       => 'int #记录id',
        'bet_type' => 'int #投注类型',
    ];

    const PARAMS = [];
    const SCHEMAS = [
        'data' => [
            'id'               => '1',
            'user_name'        => 'string #名称，eg:用户名',
            'account'          => 'string #名称，eg:第三方用户名',
            'type_name'        => 'string #描述，eg:初始默认层级',
            'bet_content'      => 'int #会员人数，eg:1',
            'win_loss'         => 'int #输赢',
            'prize'            => 'int #派奖金额',
            'money'            => 'int #投注金额',
            'valid_money'      => 'int #有效投注金额',
            '3th_order_number' => 'int #第三方注单号',
            'order_number'     => 'int #注单号',
            'game_name'        => 'string #体育类型',
            'bet_type'         => 'string #投注类型',
            'bet_value'        => 'string #投注对象',
            'date'             => 'datetime #投注时间。eg:2017-08-31 02:56:26',
            'ParlayData'       => [
                [
                    'bet_type'    => '全场，标准盘',
                    'game_name'   => '足球',
                    'bet_value'   => '主队',
                    'home_team'   => '科梅瑟洛',
                    'home_score'  => 1,
                    'away_team'   => '皇家加西拉索',
                    'away_score'  => 0,
                    'league_name' => '秘鲁甲组联赛',
                    'odds'        => 1.36,
                ],
                [
                    'bet_type'    => '全场，标准盘',
                    'game_name'   => '足球',
                    'bet_value'   => '客队',
                    'home_team'   => '费古埃伦斯SC U23',
                    'home_score'  => 0,
                    'away_team'   => '巴拉纳体育会 U23',
                    'away_score'  => 1,
                    'league_name' => '巴西阿斯匹兰特斯锦标赛U23',
                    'odds'        => 1.38,
                ],
            ],
        ],
    ];

    //前置方法
    protected $beforeActionList = [
        'verifyToken', 'authorize',
    ];

    public function run($id = '') {

        $this->checkID($id);
//        (new BaseValidate([
//            'bet_type'=>'require',
//        ]))->paramsCheck('',$this->request,$this->response);
//        $params = $this->request->getParams();
        $gameService = new GameApi($this->ci);
        $games = $gameService->sbGames();
        $playTypes = $gameService->sbPlayTypes();
        $sbGameService = $gameService->getApi('沙巴体育', '');
        $data = DB::connection('slave')->table('order_3th')
                  ->where('id', $id)
                  ->selectRaw('id,user_name,type_name,account,bet_content, game_name, date , order_number,3th_order_number, money, valid_money, prize, win_loss,status,extra')
                  ->first();
        if (empty($data))
            return [];
        $data->game_name = empty($data->game_name) ? '混合' : $data->game_name;
        $data->bet_content = json_decode($data->bet_content, true);
        $data->ParlayData = [];
        $ParlayData = [];
        $extra = json_decode($data->extra, true);
        if ($data->bet_content['bet_type'] == '串关') {
            $data->bet_type = '串关';
            $ParlayData = $extra['ParlayData'];
        } else {
            $data->bet_type = '单关';
            $ParlayData[] = json_decode($data->extra, true);
        }
        $data->odds_type = $sbGameService->getOddsType($extra['odds_type']);
        $data->combo_type = isset($extra['combo_type']) ? $sbGameService->getComboType($extra['combo_type']) : '';
        unset($data->extra);
        unset($data->bet_content);
        foreach ($ParlayData as $k => $v) {
            $data->ParlayData[$k]['bet_type'] = $playTypes[$v['bet_type']];
            $data->ParlayData[$k]['game_name'] = isset($games[$v['sport_type']]) ? $games[$v['sport_type']]: '未知' ;
            $data->ParlayData[$k]['bet_value'] = $gameService->getSbBetValue($v['bet_type'], $v['bet_team']);
            $data->ParlayData[$k]['home_team'] = $sbGameService->getTeamName($v['home_id'], $v['bet_type']);
            $data->ParlayData[$k]['home_score'] = $v['home_score'];
            $data->ParlayData[$k]['away_team'] = $sbGameService->getTeamName($v['away_id'], $v['bet_type']);
            $data->ParlayData[$k]['away_score'] = $v['away_score'];
            $data->ParlayData[$k]['league_name'] = $sbGameService->getLeaguName($v['league_id']);
            $data->ParlayData[$k]['odds'] = $v['odds'];
        }
        unset($ParlayData);
        return (array)$data;

    }
};
