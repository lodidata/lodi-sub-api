<?php
/**
 * Created by PhpStorm.
 * User:
 * Date: 2018/3/21
 * Time: 10:28
 */

namespace Logic\Define;
/**
 * Class CacheKey
 * 缓存key定义 前缀定义类
 * @package Logic\Define
 */
class Cache3thGameKey
{

    /**
     * key
     * @var array
     */
    public static $perfix = [

        // 第三方游戏列表
        'gameList' => 'game_3th_list',

        // 第三方游戏列表
        'gameJumpUrl' => 'game_jump_url',
        'gameApiList' => 'game_api_list',

        // 转入转出 金额问题，若某一次中途出问题，保障金额的唯一
        'gameBalanceRollIn' => 'game_balance_in_unique_',
        'gameBalanceRollOut' => 'game_balance_out_unique_',
        'gameBalanceLastRollOut' => 'game_balance_last_out_',
        'gameBalanceLastRollIn' => 'game_balance_last_in_',

        'gameGetOrderLastTime' => 'game_get_order_last_time_',
        'gameGetOrderLastTimeLock' => 'game_get_order_last_time_lock_',

        //  每次请求转入转出以防频繁转入转出，请求响应时长导致错乱，按序转入转出
        'gameBalanceLockUser' => 'game_balance_user_lock_request',

        //  每次请求转入转出以防频繁转入转出，请求响应时长导致错乱，按序转入转出
        'gameUserCacheLast' => 'game_user_cache_lasst_msg',

        //支付列表
        'payConfig' => 'pay_config_list',


        //游戏校验是否执行,执行时间
        'gameOrderCheckGo' => 'game_order_check_go_',
        'gameOrderCheckTime' => 'game_order_check_',
        'gameOrderMaxId' => 'game_order_max_id',

    ];

}