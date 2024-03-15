<?php

namespace Logic\GameApi\Game;

use Utils\Client;

/**
 * BG捕鱼
 * Class BGBY
 * @package Logic\GameApi\Game
 */
class BGBY extends BG
{
    //进入游戏
    public function getJumpUrl(array $params = [])
    {
        //检测并创建账号
        $account = $this->getGameAccount();
        if (!$account) {
            return [
                'status' => 133,
                'message' => $this->lang->text(133),
                'url' => ''
            ];
        }
        $back_url = $this->ci->get('settings')['website']['game_back_url'] ?? $this->ci->get('settings')['website']['game_back_index'];
        $data = [
            'loginId' => $account['account'],
            'lang' => $this->langs[LANG]?? $this->langs['en-us'],
            'isMobileUrl' => 1,
            'returnUrl' => urlencode($back_url),
            'gameType' => intval($params['kind_id']) ?? 105,    //默认105捕鱼大师
            'fromIp' => Client::getIp()
        ];
        $res = $this->requestParam('open.game.bg.url', $data, false, true, []);
        if ($res['responseStatus']) {
            //余额转入第三方
            $result = $this->rollInThird();
            if (!$result['status']) {
                return [
                    'status' => 886,
                    'message' => $result['msg'] ?? 'roll in error',
                    'url' => ''
                ];
            }
            return [
                'status' => 0,
                'url' => $res['result'],
                'message' => 'ok'
            ];
        } else {
            return [
                'status' => -1,
                'message' => isset($res['error']) && isset($res['error']['message']) && $res['error']['message'] ?? 'login error',
                'url' => ''
            ];
        }
    }
}
