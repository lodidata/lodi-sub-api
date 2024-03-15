<?php
namespace Logic\GameApi\CKFormat;
use Psr\Container\ContainerInterface;

class CKFORMAT
{
    /**
     * @var ContainerInterface
     */
    protected $ci;
    /**
     * @var string 注单表名称
     */
    protected $order_table;
    /**
     * @var int 分类ID
     */
    protected $game_id;
    /**
     * @var string 分类字段名称
     */
    protected $field_category_name = '';
    /**
     * @var string 分类字段值
     */
    protected $field_category_value = '';
    /**
     * @var array 查询对应字段
     * 'orderNumber' => 'OCode',//单号
     * 'userName' => 'Username',//账号
     * 'betAmount' => 'betAmount',//投注
     * 'validAmount' => 'betAmount',//实际投注
     * 'profit' => 'income',//盈亏
     * 'whereTime' => 'gameDate',//搜索时间
     * 'startTime' => 'gameDate',//投注时间
     * 'endTime' => 'gameDate',//结算时间
     * 'gameCode' => "''", //游戏码
     * 'gameRoundId' => "''", //桌号
     * 'gameName' => "''",//游戏名称
     */
    protected $table_fields = [];

    /**
     * @var array 查询扩展字段
     */
    protected $table_fields_ext = [];

    /**
     * @var string 默认游戏名称
     */
    protected $game_name = '';
    /**
     * @var bool 是否为体育
     */
    protected $is_sport = false;
    /**
     * @var string 图片域名
     */
    protected $pictureDoman;
    /**
     * @var bool CK表游戏账号是否大写
     */
    protected $user_account_is_upper = false;

    public function __construct()
    {
        global $app;
        $this->ci = $app->getContainer();
        //$this->order_table = $this->order_table.'_local';
        $this->pictureDoman = $this->ci->get('settings')['upload']['dsn'][$this->ci->get('settings')['upload']['useDsn']]['domain'];
    }

    /**
     * 将订单状态 转换为前端可以查看的
     * @param float $profit 盈亏
     * @param string $gameEndTime 游戏结束时间
     * @return array
     */
    public function parseRecordsState(float $profit, string $gameEndTime)
    {
        $result = [];
        if ($gameEndTime == null || strlen($gameEndTime) == 0) {
            $result['state'] = $this->ci->lang->text("state.created");
        } else if ($profit > 0) {
            $result['state'] = $this->ci->lang->text("state.winning");
        } else {
            $result['state'] = $this->ci->lang->text("state.lose");
        }
        // 模式
        $result['mode'] = $this->ci->lang->text("Third party");
        return $result;
    }

    /**
     * 获取全部游戏列表
     */
    public function getAllGameList()
    {
        $list = $this->ci->redis->hGet('game_list_format', $this->game_id);
        if(!is_null($list)){
            return json_decode($list, true);
        }
        $list = \DB::connection('slave')->table('game_3th')
            ->where('game_3th.game_id', '=', $this->game_id)
            ->select(['id as game_id', 'game_name', 'kind_id','game_img'])->orderBy('sort')->get()->toArray();
        $this->ci->redis->hSet('game_list_format', $this->game_id, json_encode($list, JSON_UNESCAPED_UNICODE));
        $this->ci->redis->expire('game_list_format', 84600);
        return $list;
    }

    /**
     * 过滤条件详情
     * @param bool $isShowState
     * @return array
     */
    public function filterDetail(bool $isShowState)
    {
        // 过滤彩种
        $filter = [];
            $data = self::getAllGameList();
            $filter[] = ['key' => '', 'name' => $this->ci->lang->text('All')];
            foreach ($data ?? [] as $v) {
                $v = (array)$v;
                $filter[] = [
                    // id
                    'key' => (string)$v['kind_id'],
                    // 游戏名
                    'name' => $v['game_name'],
                ];
            }
        if ($isShowState) {
            // 状态
            $state = OrderRecordCommon::TYPE_TARGET_STATE['default'];
            $result[] = [
                'title' => $this->ci->lang->text('State'),
                'category' => 'state',
                'list' => $state,
            ];
        }
        $result[] = [
            'title' => $this->ci->lang->text('Game type'),
            'category' => 'kind_id',
            'list' => $filter,
        ];
        return $result;
    }

    /**
     * 查看订单记录
     * @param array $condition
     * @return mixed
     */
    public function getRecord($condition)
    {
        $default_fields = [
            'orderNumber' => 'OCode',//单号
            'userName' => 'Username',//账号
            'betAmount' => 'betAmount',//投注
            'validAmount' => 'betAmount',//实际投注
            'profit' => 'income',//盈亏
            'whereTime' => 'gameDate',//搜索时间
            'startTime' => 'gameDate',//投注时间
            'endTime' => 'gameDate',//结算时间
            'gameCode' => "''", //游戏码
            'gameRoundId' => "''", //桌号
            'gameName' => "''",//游戏名称
        ];

        $fields = array_merge($default_fields, $this->table_fields);

        $where = [];
        $tid = $this->ci->get('settings')['app']['tid'];
        $where[] = "tid=" . $tid;
        if(isset($this->field_category_name) && $this->field_category_name){
            $where[] = $this->field_category_name . " IN (" . $this->field_category_value. ")";
        }

        // 状态
        if (isset($condition['state'])) {
            $where[] = $fields['profit'] . (($condition['state']=='winning') ? '>' : '<=') . '0';
        }
        // last_id 筛选
        isset($condition['last_id']) && $where[] = 'id' . '>' . $condition['last_id'];
        // id 筛选
        isset($condition['id']) && $where[]= 'id' . '=' . $condition['id'];

        // KindID 筛选
        isset($condition['kind_id']) && $where[]= $fields['gameCode'] . "='" . $condition['kind_id'] . "'";

        // 订单筛选
        if (isset($condition['order_number'])) {
            $where[]= $fields['orderNumber'] . "='" . $condition['order_number']."'";
        } else {
            // 订单时间过滤
            if (isset($condition['start_time']) && $condition['start_time']) {
                $where[] = $fields['whereTime'] . ">='" . $condition['start_time'] . "'";
            } else {
                $where[] = $fields['whereTime'] . ">='" . date('Y-m-d') . "'";
            }

            if (isset($condition['end_time']) && $condition['end_time']) {
                $where[] = $fields['whereTime'] . "<='" . $condition['end_time'] . "'";
            } else {
                $where[] = $fields['whereTime'] . "<='" . date('Y-m-d H:i:s') . "'";
            }
        }

        //查用户
        if (isset($condition['user_name'])) {
            $user_account = $this->getGameAccountByUserName($condition['user_name']);
            if($this->user_account_is_upper){
                $user_account = strtoupper($user_account);
            }
            $where[] = $fields['userName'] . "='" . $user_account . "'";
        } elseif (isset($condition['user_id'])) {
            $user_account = $this->getGameAccountByUserId($condition['user_id']);
            if($this->user_account_is_upper){
                $user_account = strtoupper($user_account);
            }
            $where[] = $fields['userName'] . "='" . $user_account . "'";
        }


        // 统计相关,只要几个简单字段
        if (isset($condition['statistics'])) {
            // 统计
            $select = [
                // 总下注
                'all_bet' => "sum(".$fields['betAmount'].") as total_bet",
                // 有效投注
                'cell_score' => "sum(".$fields['validAmount'].") as total_available_bet",
                // 用户数量
                'Username' => "count(DISTINCT ".$fields['userName'].") as total_user_count",
                //派彩金额
                'send_prize' => "sum(".$fields['profit'].") as total_send_prize",
                //总数
                'order_count' => "count(0) as total_order_count"
            ];

        } else {
            // 常规操作
            $select = [
                'id' => 'id',
                //订单号
                'order_number' => $fields['orderNumber'] . ' as order_number',
                // 用户id
                'user_account' => $fields['userName'] . ' as user_account',
                // 盈亏
                'income' => $fields['profit'] . ' as income',
                // 下注
                'gameBetAmount' => $fields['betAmount'] . ' as gameBetAmount',
                //有效下注
                'validAmount' => $fields['validAmount'] . ' as validAmount',
                // 开始时间
                'GameStartTime' => $fields['startTime'] . ' as GameStartTime',
                //结算时间
                'GameEndTime' => $fields['endTime'] . ' as GameEndTime',
                //游戏代码
                'gameCode' => $fields['gameCode'] . ' as kind_id',
                //座位号
                'gameRoundId' => $fields['gameRoundId'] . ' as gameRoundId',
                //游戏名称
                'gameName' => $fields['gameName'] . ' as gameName',
            ];

            //TODO 扩展字段查询
            if(!empty($this->table_fields_ext)){
                $select = array_merge($select, $this->table_fields_ext);
            }
        }
        $field = join(',', array_values($select));
        $where = join(' and ', array_values($where));
        $sql = "SELECT {$field} FROM {$this->order_table} WHERE {$where}";

        //是否排序
        if (!isset($condition['statistics'])) {
            //排序默认结算时间
            if (isset($condition['orderby'])) {
                $order_field = $fields['whereTime'];
                switch ($condition['orderby']) {
                    case 'CellScore':
                        $order_field = 'validAmount';
                        break;
                    case 'pay_money':
                        $order_field = 'gameBetAmount';
                        break;
                    case 'Profit':
                        $order_field = 'income';
                        break;
                }
                $sql .= " ORDER BY {$order_field} " . (isset($condition['orderbyasc']) && $condition['orderbyasc'] == 'asc' ? 'asc' : 'desc');
            }
        }
        return $sql;
    }

    /**
     * 查询订单详情
     * @param $condition
     * @return mixed
     */
    public function getRecordInfo($condition)
    {
        $sql = $this->getRecord($condition);
        $website = $this->ci->get('settings')['website'];
        if (isset($website['DBLog']) && $website['DBLog']) {
            $this->ci->logger->error('[CKSQL] ' . $sql);
        }
        return $this->ci->ckdb->select($sql)->fetchOne();
    }

    /**
     * 查询订单记录
     * @param  [type]  $condition [description]
     * @param integer $page [页]
     * @param integer $pageSize [每页几项]
     * @return mixed [type]            [description]
     */
    public function getRecords($condition, $page = 1, $pageSize = 20)
    {
        $sql = $this->getRecord($condition);
        $start_page = ($page-1) * $pageSize;
        $sql .= ' LIMIT ' . $start_page . ',' . $pageSize;
        $website = $this->ci->get('settings')['website'];
        if (isset($website['DBLog']) && $website['DBLog']) {
            $this->ci->logger->error('[CKSQL] ' . $sql);
        }
        //echo $sql;die;
        $db = $this->ci->ckdb->select($sql);
        return $db->rows();
    }

    /**
     * 查询全部订单
     * @param $condition
     * @return mixed
     */
    public function getRecordAll($condition)
    {
        $sql = $this->getRecord($condition);
        $website = $this->ci->get('settings')['website'];
        if (isset($website['DBLog']) && $website['DBLog']) {
            $this->ci->logger->error('[CKSQL] ' . $sql);
        }
        return $this->ci->ckdb->select($sql)->rows();
    }

    /**
     * 统计信息
     * @param array $condition
     * @return array
     */
    public function statistics(array $condition)
    {
        $data = $this->getRecordInfo($condition);
        $result = [
            'all_bet' => $data['total_bet'],
            'cell_score' => $data['total_available_bet'],
            'user_count' => $data['total_user_count'],
            'send_prize' => $data['total_send_prize'],
            'total_count' => $data['total_order_count'],
        ];
        return $result;
    }

    /**
     * 订单列表
     * @param $condition
     * @param int $page
     * @param int $pageSize
     * @return array
     */
    public function records($condition, $page = 1, $pageSize = 20)
    {
        // 订单记录
        $data = $this->getRecords($condition, $page, $pageSize);
        // 列表详情
        $isShowUsername = isset($condition['user_agent_ids']);
        $result = [];
        //游戏列表
        $gameList = $this->getAllGameList();
        $games = [];
        if($gameList) {
            foreach ($gameList as $value) {
                $value = (array)$value;
                $games[$value['kind_id']] = $value['game_name'];
            }
        }
        foreach ($data ?? [] as $k => $v) {
            $result[$k] = isset($result[$k]) ? $result[$k] : [];
            $result[$k]['id'] = $v['id'];
            // 订单号
            $result[$k]['order_number'] = $v['order_number'];
            // 游戏名
            $result[$k]['game_name'] = $this->game_name ?: ($v['gameName'] ? $this->getGameName($v['gameName']) : ($games[$v['kind_id']] ?? '-'));
            // 座位号
            $result[$k]['roundId'] = $v['gameRoundId'];
            // 玩法名
            $result[$k]['play_name'] = '-';
            // 盈亏
            $result[$k]['profit'] = $v['income'];
            // 投注金额 以分为单位
            $result[$k]['pay_money'] = $v['gameBetAmount'];
            // 显示用户名
            if ($isShowUsername) {
                $result[$k]['user_name'] = $this->getGameUserNameByAccount($v['user_account']);
            }
            // item 类型
            $result[$k]['type_name'] = $condition['type_name'];
            // 创建时间
            $result[$k]['create_time'] = $v['GameStartTime'];
            // 描述
            $result[$k] += self::parseRecordsState($v['income'], $v['GameStartTime']);
        }
        return $result;

    }

    /**
     * 订单列表 -> admin
     * @param $condition
     * @param int $page
     * @param int $pageSize
     * @return array
     */
    public function recordsAdmin($condition, $page = 1, $pageSize = 20)
    {
        $data = $this->getRecords($condition, $page, $pageSize);
        $result = [];
        //游戏列表
        $gameList = $this->getAllGameList();
        $games = [];
        if($gameList){
            foreach ($gameList as $value){
                $value = (array)$value;
                $games[$value['kind_id']] = $value['game_name'];
            }
        }
        foreach ($data ?? [] as $k => $v){
            $result[$k] = isset($result[$k]) ? $result[$k] : [];
            $result[$k]['id'] = $v['id'];
            // 用户id
            $result[$k]['user_id'] = $this->getUserIdByUserAccount($v['user_account']);
            // 订单号
            $result[$k]['order_number'] = $v['order_number'];
            // 会员账号
            $result[$k]['user_name'] = $this->getGameUserNameByAccount($v['user_account']);
            // 投注时间
            $result[$k]['GameStartTime'] = $v['GameStartTime'];
            // 结算时间
            $result[$k]['GameEndTime'] = $v['GameEndTime'];
            // 游戏名
            $result[$k]['game_name'] = $this->game_name ?: ($v['gameName'] ? $this->getGameName($v['gameName']) : ($games[$v['kind_id']] ?? '-'));
            // 桌子号 > 椅子号
            $result[$k]['TableID'] = $v['gameRoundId'];
            // 投注金额
            $result[$k]['pay_money'] = $v['gameBetAmount'];
            // 有效投注
            $result[$k]['CellScore'] = $v['validAmount'];
            // 派奖/输赢
            $result[$k]['Profit'] = $v['income'];
            // 抽水
            $result[$k]['Revenue'] = '-';

            //TODO 体育注单
            if($this->is_sport){
                // 结果
                $result[$k]['outcome'] = $this->getResultDetail($v);
                $result[$k]['desc'] = $this->getBetDetail($v);
            }
        }
        return $result;
    }


    /**
     * 订单详情
     * @param $condition
     * @return array
     * @throws \Exception
     */
    public function detail($condition)
    {
        $data = $this->getRecordInfo($condition);
        if (!isset($data)) {
            return [];
        }
        //游戏列表
        $gameList = $this->getAllGameList();
        $game_img = $game_name = '';
        if($gameList) {
            foreach ($gameList as $value) {
                $value = (array)$value;
                if ($data['kind_id'] == $value['kind_id']) {
                    $game_img = $value['game_img'] ? $this->pictureDoman . $value['game_img'] : '';
                    $game_name = $value['game_name'];
                    break;
                }
            }
        }

        $result = [
            // logo
            'logo' => $game_img,
            // 彩种名
            'game_name' => $this->game_name ?: ($data['gameName'] ? $this->getGameName($data['gameName']) :  $game_name),
            // 期号
            'sub_game_name' => '',
            // 投注金额 转为真实金额
            'pay_money' => $data['gameBetAmount'],
            // 中奖金额
            'send_money' => (string)($data['income'] < 0 ? 0 : $data['income']),
        ];
        $result += self::parseRecordsState($data['income'], $data['GameStartTime']);
        // 方案详情
        $detail = [
            [
                'key' => $this->ci->lang->text('Note No') . ':',
                'value' => $data['order_number']
            ],
            [
                'key' => $this->ci->lang->text('Bet amount') . ':',
                'value' => bcdiv($data['gameBetAmount'], 100, 2)
            ],
            [
                'key' => $this->ci->lang->text('Profit and loss amount') . ':',
                'value' => bcdiv($data['income'], 100, 2)
            ],
            [
                'key' => $this->ci->lang->text('Start time') . ':',
                'value' => $data['GameStartTime']
            ],
            [
                'key' => $this->ci->lang->text('End time') . ':',
                'value' => $data['GameEndTime']
            ],
        ];

        if($data['gameRoundId']){
            $detail[] = [
                'key' => $this->ci->lang->text('round id') . ':',
                'value' => $data['gameRoundId']
            ];
        }
        //TODO 扩展字段
        if(!empty($this->table_fields_ext)){
            $detail_ext = $this->getExtDetail($data);
            if($detail_ext){
                foreach ($detail_ext as $item) {
                    $detail[] = $item;
                }
            }
        }

        //TODO 投注详情
        if($this->is_sport) {
            $code_list = $this->getBetDetail($data);
            if($code_list){
                if(is_array($code_list)){
                    foreach ($code_list as $item) {
                        $detail[] = $item;
                    }
                }
                else{
                    $detail[] = $code_list;
                }
            }
        }
        $result['detail'] = $detail;
        return $result;
    }

    /**
     * 游戏名称转换
     * @param $gameName
     * @return mixed
     */
    protected function getGameName($gameName)
    {
        return $gameName;
    }

    /**
     * 结果详情
     * @param array $data
     * @return array
     */
    protected function getResultDetail($data)
    {
    }

    /**
     * 投注详情
     * @param $data
     * @return array
     */
    protected function getBetDetail($data)
    {
    }

    /**
     * 扩展字段
     * @param $data
     * @return array
     */
    protected function getExtDetail($data)
    {
    }

    /**
     * 根据游戏账号查找用户ID
     * @param $user_account
     * @return mixed
     */
    public function getUserIdByUserAccount($user_account)
    {
        $user_id = $this->ci->redis->hGet('game_user_id_by_account', $user_account);
        if(is_null($user_id)){
            $user_id = \DB::connection('slave')
                ->table('game_user_account')
                ->where('user_account', $user_account)
                ->value('user_id');
            $this->ci->redis->hSet('game_user_id_by_account', $user_account, $user_id);
            $this->ci->redis->expire('game_user_id_by_account', 86400);
        }
        return $user_id;
    }

    /**
     * 根据游戏账号查找用户ID
     * @param $user_id
     * @return mixed
     */
    public function getGameAccountByUserId($user_id)
    {
        $user_account = $this->ci->redis->hGet('game_user_account_by_id', $user_id);
        if(is_null($user_account)){
            $user_account = \DB::connection('slave')
                ->table('game_user_account')
                ->where('user_id', $user_id)
                ->value('user_account');
            $this->ci->redis->hSet('game_user_account_by_id', $user_id, $user_account);
            $this->ci->redis->expire('game_user_account_by_id', 86400);
        }
        return $user_account;
    }

    /**
     * 根据用户名查找游戏账号
     * @param $user_name
     * @return mixed
     */
    public function getGameAccountByUserName($user_name)
    {
        $user_account = $this->ci->redis->hGet('game_user_account_by_name', $user_name);
        if(is_null($user_account)){
            $user_account = \DB::connection('slave')
                ->table('game_user_account')
                ->where('user_name', $user_name)
                ->value('user_account');
            $this->ci->redis->hSet('game_user_account_by_name', $user_name, $user_account);
            $this->ci->redis->expire('game_user_account_by_name', 86400);
        }
        return $user_account;
    }

    /**
     * 根据游戏账号查找用户
     * @param $user_account
     * @return mixed
     */
    public function getGameUserNameByAccount($user_account){
        $user_name = $this->ci->redis->hGet('game_user_name_by_account', $user_account);
        if(is_null($user_name)){
            $user_name = \DB::connection('slave')
                ->table('game_user_account')
                ->where('user_account', $user_account)
                ->value('user_name');
            $this->ci->redis->hSet('game_user_name_by_account', $user_account, $user_name);
            $this->ci->redis->expire('game_user_name_by_account', 86400);
        }
        return $user_name;
    }
}