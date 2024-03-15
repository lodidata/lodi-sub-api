<?php

namespace Model;
use Model\Game3th;


class Favorites extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'favorites';

    public static function addFavorite($userId, $gameId)
    {
        $data = [
            'user_id' => $userId,
            'game_id' => $gameId
        ];
        return self::insert($data);
    }

    public static function delFavorite($userId, $gameId)
    {
        $where = [
            'user_id' => $userId,
            'game_id' => $gameId
        ];
        return self::where($where)->delete();
    }

    public static function getId($userId, $gameId){
        $where = [
            'user_id' => $userId,
            'game_id' => $gameId
        ];
        return self::where($where)->value('id');
    }

    /**
     * 用户收藏列表
     * @param $userId
     */
    public static function userFavoriteList($userId){
        $game_id_list = self::getGameIdList($userId);

        $res       = Game3th::whereIn('game_3th.id',$game_id_list)->leftJoin('game_menu as gm','gm.id', '=', 'game_3th.game_id')->where('game_3th.status','enabled')->get(['game_3th.id','gm.pid','game_3th.game_img'])->toArray();
        $game_menu = self::formatFavoriteList();

        $jump_url = '/game/third/app?';  // 固定跳转进入第三方
        $quit_url = '/game/third/quit?';  // 固定跳转退出第三方

        foreach ($game_menu as $k => $v){
            foreach ($res ?? [] as $key => $value){
                if($value['pid'] == $v['id']){
                    unset($value['pid']);
                    $value['url']             = $jump_url . 'play_id=' . $value['id'];
                    $value['quit_url']        = $quit_url . 'play_id=' . $value['id'];
                    $game_menu[$k]['child'][] = $value;
                    unset($res[$key]);
                }
            }
        }

        foreach ($game_menu as $k => $v){
            if(!isset($v['child'])) unset($game_menu[$k]);
        }
        return $game_menu;
    }

    public static function getGameIdList($userId){
        $where = [
            'user_id' => $userId,
        ];
        $res = self::where($where)->get(['game_id'])->toArray();
        $res && $res = array_column($res, 'game_id');
        return $res;
    }

    public static function formatFavoriteList(){
        $game_menu = \Model\GameMenu::formatFavoriteMenu();
        return $game_menu;
    }

}