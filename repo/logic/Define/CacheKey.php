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
class CacheKey
{

    /**
     * key
     * @var array
     */
    public static $perfix = [
        // deallog
        'fundsDealLogType' => 'funds_deal_log_type_',

        // 密码错误校验前缀
        'pwdErrorLimit' => 'loginpwd_wrong_times_',

        // 全局配置
        'global' => 'system.config.global',

        // 注册
        'registerUser' => 'system.config.reg.user',

        // 第三方客服
        '3thService' => 'system.config.third.service',

        // 用户登录token
        'token' => 'token_',

        // 热门彩期
        'hotLottery' => 'curperiod_hot_all_',

        // 热门彩期
        'hotLotteryNext' => 'nextperiod_hot_all_',

        // 首页缓存
        'appHomePage' => 'app_home_page',

        // notice h5list
        'noticeH5list' => 'notice_h5_list',

        // banner
        'banner' => 'banner',

        // 注册推广引导图片
        'spreadPicList' => 'spread_pic_list_',

        // 彩种
        'simpleList' => 'lottery_list_',

        // 彩种子列表
        'simpleChildList' => 'lottery_child_list',

        // 开奖列表
        'appLotteryList' => 'app_lottery_list',

        // 玩法结构
        'lotteryPlayStruct' => 'lottery_play_struct_',

        // 玩法结构版本
        'lotteryPlayStructVer' => 'lottery_play_struct_ver_',

        // 厅
        'hall' => 'hall_',

        // 厅消息
        'hallMessage' => 'hall_message_',

        // pusherio
        'pusherioHistory' => 'pusherio_history_',

        // 彩期缓存
        'prizeLotteryInfo' => 'prize_lottery_info_',

        // 当前彩期 hash
        'currentLotteryInfo' => 'current_lottery_info',

        // 彩期列表 hash
        'lotteryInfoHistoryList' => 'lottery_info_history_list',

        // 彩期列表 hash period_code_part不为空的
        'lotteryInfoFinishedHistoryList' => 'lottery_info_finished_history_list',

        // 下一期 hash
        'nextLotteryInfo' => 'next_lottery_info',

        // 上一期
        'lastLotteryInfo' => 'last_lottery_info',

        // 彩期期数 hash
        'lotteryPeriodCount' => 'lottery_info_period_count',

        // 结算信息 集合
        'prizeOpenSettle' => 'prize_open_settle_',

        // 追号通知 集合
        'prizeChase' => 'prize_chase_',

        // 彩果结果同步 集合
        'prizeRsync' => 'prize_rsync_',

        // 最后一期结果hash
        'prizeLastPeriods' => 'prize_last_periods',

        // 追号期数100条hash
        'chaseLotteryInfo' => 'chase_lottery_info',

        // 平台结果缓存 
        'commonLotteryPeriod' => 'common_lottery_period_',

        // 平台开奖结果集合
        'commonLotteryPeriodSet' => 'common_lottery_period_set',

        // 结算防重复
        'settleNotify' => 'settle_notify_',

        // 返水防重复(集合)

        'runRebet_3th' => 'runRebet_3th',
        'runRebet' => 'run_rebet',
        'runBkge' => 'run_Bkge',
        'runBkgeActive' => 'run_Bkge_active',
        'runFundsMoney' => 'run_fundsMoney_money',

        // 图形验证码前缀
        'authVCode' => 'auth_vcode:',

        // 短信通知码
        'captchaRefresh' => 'cache_refresh_',

        // 短信通知码
        'captchaText' => 'cache_text_',

        // prizeServer 防多开
        'prizeServer' => 'prize_server',

        // spiderServer 防多开
        'spiderServer' => 'spider_server',

        // 本地测试开奖
        'spiderServerTest' => 'spider_server_test',

        // 本地测试开奖彩期数据
        'spiderServerLotteryTest' => 'spider_server_lottery_test',

        // 活动缓存
        'activeList' => 'activeList_',

        // ip流水防护
        'protectByIP' => 'protect_ip',

        // 根据ip 控制api请求频率
        'blackIp' => 'black_ip:',

        // 冻结的ip列表
        'blackIpLockedList' => 'black_ip_locked_list:',

        // 用户流水防护
        'protectByUser' => 'protect_user',

        // 试玩IP验证
        'tryToPlay' => 'trytoplay:',

        // userSafety
        'userSafety' => 'user_satety_',

        // socketiokey mess
        'socketioMessage' => 'socketio_message',

        // 提现密码修改限制
        'fundsPass' => 'account_wallet_password_',

        // 平台彩种停售标识
        'commonLotterySaleStatus' => 'common_lottery_sale_status_',

        // findpass
        'findPass' => 'find_pass_',

        // 用户在状在线最后时间
        'userOnlineLastTime' => 'user_online_last_time',

        // 用户禁用状态
        'userRejectLoginStatus' => 'user_reject_login_status',

        // chatLotteryRoomHash
        'chatLotteryRoomHash' => 'chat_lottery_room_hash',


        // 趋势图
        'trade' => 'trade',

        // 历史列表
        'longLotteryInfoHistoryList' => 'long_lottery_info_history_list',

        // 历史列表 period_code_part不为空的
        'longLotteryInfoFinishedHistoryList' => 'long_lottery_info_finished_history_list',

        // 活动类型
        'activeTypes' => 'active_types',

        // 第三方列表
        'thirdList' => 'third_list',

        // 第三方游戏列表
        'egame'                      => 'egame',

        //幸运转盘抽奖次数
        'lockyCount'              => 'locky.count.',

        //给指定用户设置某个奖项的中奖次数
        'lockyCountUserList'=>'locky.count.user.list.',

        //当天充值已赠送的次数
        'luckyMoneyCount'=>'lucky.money.count.',

        'luckyCountSum'=>'lucky.count.sum.',

        //回水时间
        'rebot_time' => 'rebot_time',
        'rebot_time_minute' => 'rebot_time_minute',
        //正在直播中的
        'liveLottery' => 'live.lottery',

        'imToken' => 'im_token',

        //游客user_id缓存键
        'visitorUserId' => 'visitor_user_id',


        //客户客服node_id超管配置
        'serviceNodeId' => 'service_node_id',

        'menuList'          => 'menu.list',
        'verticalMenuList'  => 'menu:vertical:list',
        'todayTopGameList'  => 'home:game:todayTop:',
        'todayHotGameList'  => 'home:game:todayHot:',

        'pageHotGameList'  =>  'home:game:pageHot:',

        'allGameList'  =>  'home:game:all',
        'hotGameList'  =>  'home:game:hot',

        'qp_menuList'       => 'menu.qp_list',
        'menuListDetail'    => 'menu.list.detail',

        //缓存用户当天盈利
        'userTodayProfit'   => 'user_today_profit_',

        //客服账号
        'ServiceAccounts'=>'service_accounts',

        //团队列表缓存 ３０秒
        'userAgentList'=>'user_agent_list_',

        //房间人数
        'hallNum'=>'hall_num_',
        //免费玩家限制
        'freePlayer' => 'free_player_',

        //客服游戏user_id  缓存 键
        'Tourist' => 'customer_service_tourist_',

        //需求管理平台进入不验证 IP变动
        'UserAdminAccess' => 'user_admin_access_',

        //语言目录
        'language_dir' => 'language_dir',

        'language_list'  => 'language_list',
        //插入彩票号
        'lotteryInsertNumber' => 'lottery_insert_number_',
        'lotteryInfo' => 'lottery_info',
        //随机插入数字
        'randomInsertNumberLock' => 'random_insert_number_lock',

        //导入彩期锁
        'loadLotteryInfoLock' => 'load_lottery_info_lock',

        //彩票算号码锁
        'openCodeLock' => 'open_code_lock:',

        //插入当前和下一期
        'writeCurrAndNextLotteryInfoLock' => 'write_current_and_next_lotteryinfo_lock',
        'qp_h_menuList' => 'qp_h_menu_list',

        //活动发放时间
        'week_issue_time'=>'week_issue_time',
        'week_issue_day'=>'week_issue_day',
        'month_issue_time'=>'month_issue_time',
        'month_issue_day'=>'month_issue_day',
        'recharge_week_issue_time'=>'recharge_week_issue_time',
        'recharge_week_issue_day'=>'recharge_week_issue_day',

        //返佣活动周期
        'bkge_date'=>"bkge_date",
        //重置抽奖
        'resetLuck'=>'resetLuck',

        //游戏活动
        'gameActivity'=>'gameActivity',
        //充值
        'chargeActivity'=>'chargeActivity:',
        //推广活动
        'spreadActivity'=>'spreadActivity',
        //注单查询统计缓存
        'orderRecord' => 'orderRecord',

        //订单投注对比
        'middleOrder'=>'middleOrder',

        //7天内不能重复设置盈亏占比
        'resetWeek'=>'resetWeek:',
        //进度条
        'progress' =>'progress:',

        //盈亏代理开关
        'profit_loss_switch'=>'profit_loss_switch',
        //谷歌支付刷新令牌 refresh_token
        'google_pay_refresh_token_ncg789' => 'google_pay_refresh_token_ncg789',
        //谷歌支付访问令牌 access_token
        'google_pay_access_token_ncg789' => 'google_pay_access_token_ncg789',
        //奖池彩金
        'gameJackPot' => 'game_jackpot_',
        //直推奖励返水比例
        'direct_return_ratio'            => 'direct_return_ratio_',
        //直推奖励返水比例
        'direct_current_return_ratio'    => 'direct_current_return_ratio_',
        // 顶部飘浮下载按钮
        'topFloatList' => 'top_float_list_',

        //渠道报表统计 总访问次数
        'channelReportVisitTotal' => 'channel_report_visit_total',

        //渠道报表统计 独立访问次数
        'channelReportVisitDistinct' => 'channel_report_visit_distinct',

        //报警缓存
        'backwater_type' => 'middle_order_message',
        //返水明细缓存
        'backwater' => 'backwater_',
        'rebet_history' => 'rebet_history_',
        'backwater_num_' => 'backwater_num_',

        //周薪发放时间
        'week_award_day'     => 'week_award_day_',
        'week_award_time'    => 'week_award_time_',
    ];

}