<?php

namespace LotteryPlay;
require_once 'Functions.php';

/**
 * 业务逻辑类
 * @author ni
 * + 2018-01-14
 */
class Logic {

    /**
     * 彩种分类ID
     * @var integer
     */
    protected $_cid = 0;

    /**
     * 开奖号码 1,2,3
     * @var string
     */
    protected $_periodCode = '';

    /**
     * 玩法ID
     * @var integer
     */
    protected $_playId = 0;

    /**
     * 赔率设定
     * @var array
     */
    protected $_odds = [];

    /**
     * 倍投注数
     * @var integer
     */
    protected $_bet = 1;

    /**
     * 单注金额（分）
     * @var integer
     */
    protected $_betMoney = 100;

    /**
     * 下单号码
     * @var string
     */
    protected $_playNumber;

    /**
     * 最高派奖
     * @var integer
     */
    protected $_maxPrize = 0;

    /**
     * 开奖时间
     * @var string
     */
    protected $_openTime = false;

    /**
     * 配置
     * @var array
     */
    protected $_lotteryConfig = [];

    /**
     * 错误日志记录
     * @var array
     */
    protected $_errors = [];

    protected $_context = [];
    /**
     * 处理类映射
     * @var array
     */
    protected $_maps = [
        1  => '\\LotteryPlay\\xy28',
        5  => '\\LotteryPlay\\q3',
        10 => '\\LotteryPlay\\ssc',
        24 => '\\LotteryPlay\\m11n5',
        39 => '\\LotteryPlay\\pk10',
        51 => '\\LotteryPlay\\lhc',
    ];

    /**
     * 配置文件数据
     * @var array
     */
    protected $_lotteryMaps = [];

    /**
     * 设定彩种分类ID
     *
     * @param [type] $cid [description]
     * @return Logic
     */
    public function setCate($cid) {
        $this->_cid = $cid;
        return $this;
    }

    /**
     * 设定开奖号码
     *
     * @param string $code [description]
     * @return Logic
     */
    public function setPeriodCode($code) {
        $this->_periodCode = $code;
        return $this;
    }

    /**
     * 设定限制规则
     *
     * @param $playId
     * @param $odds
     * @param $bet
     * @param $betMoney
     * @param $playNumber
     * @param integer $maxPrize [description]
     * @param bool $openTime 开奖时间
     * @return Logic
     */
    public function setRules($playId, $odds, $bet, $betMoney, $playNumber, $maxPrize = 0, $openTime = false) {
        $this->_playId      = $playId;//玩法id
        $this->_odds        = (array)$odds;//赔率，一个玩法playId有多个赔率的情况
        $this->_bet         = $bet;//金额倍数
        $this->_betMoney    = $betMoney;//单注金额
        $this->_playNumber  = $playNumber;//下注号码
        $this->_maxPrize    = $maxPrize;
        $this->_openTime    = $openTime;
        return $this;
    }

    /**
     * 数据校验
     *
     * @param  boolean $checkPeriodCode 是否检查开奖结果
     *
     * @return boolean                  [description]
     */
    public function isValid($checkPeriodCode = false) {
        $success = true;
        if (!$this->_checkMaps()) {
            $success = false;
            $this->_errors[] = 'setCate错误';
        }

        if ($success) {
            $this->_loadLotteryConfig($this->_cid);

            if (!isset($this->_lotteryMaps[$this->_cid][$this->_playId])) {
                $success = false;
                $this->_errors[] = '玩法不存在';
            }
        }

        if($this->_cid == 51){
            if(isset($this->_lotteryMaps[$this->_cid][$this->_playId]['format']['args']['range'])){
                $playNumbers = explode('|',$this->_playNumber);
                foreach ($playNumbers as $playNumber){
                    if(!in_array($playNumber,$this->_lotteryMaps[$this->_cid][$this->_playId]['format']['args']['range'],true)){
                        $success = false;
                        $this->_errors[] = '注单号码有误！';
                    }
                }
            }
        }

        if($this->_cid == 51){
            if(isset($this->_lotteryMaps[$this->_cid][$this->_playId]['format']['args']['range'])){
                $playNumbers = explode('|',$this->_playNumber);
                foreach ($playNumbers as $playNumber){
                    if(!in_array($playNumber,$this->_lotteryMaps[$this->_cid][$this->_playId]['format']['args']['range'],true)){
                        $success = false;
                        $this->_errors[] = '注单号码有误！';
                    }
                }
            }
        }

//        if ($success) {
            $this->_context = new Context();
            $this->_context->playId = $this->_playId;//玩法
            $this->_context->periodCode = $this->_periodCode;//开奖号码
            $this->_context->playNumber = $this->_playNumber;//下注号码
            $this->_context->odds = $this->_odds;//赔率
            $this->_context->oddsOrigin = &$this->_odds; //引用传递修改_odds值
            $this->_context->periodCodeFormat = false;
            $this->_context->openTime = $this->_openTime;//开奖时间
            $this->_lotteryConfig = array_merge((isset($this->_lotteryMaps[$this->_cid][0]) ? $this->_lotteryMaps[$this->_cid][0] : []),
                $this->_lotteryMaps[$this->_cid][$this->_playId]);//彩票玩法配置

            $this->_context->lotteryConfig = $this->_lotteryConfig;
//        }

        if ($success) {
            if (!isset($this->_lotteryConfig['name'])
                || !isset($this->_lotteryConfig['result'])
                || !isset($this->_lotteryConfig['format'])
                || !isset($this->_lotteryConfig['valid'])
                || !isset($this->_lotteryConfig['periodCodeFormat'])
                || !isset($this->_lotteryConfig['standardOdds'])
                || !isset($this->_lotteryConfig['standardOddsDesc'])
            ) {
                $success = false;
                $this->_errors[] = '模版配置设置错误';
            }
        }

        if ($success) {
            // 六合彩的，连肖不用判断
            if($this->_cid != 51 || !in_array($this->_playId, [551, 552, 553, 554])){
                if (count($this->_odds) != count($this->_lotteryConfig['standardOdds'])) {
                    $success = false;
                    $this->_errors[] = '赔率设置错误';
                }
            }

            if (count($this->_lotteryConfig['standardOddsDesc']) != count($this->_lotteryConfig['standardOdds'])) {
                $success = false;
                $this->_errors[] = '赔率模版设置错误' . count($this->_lotteryConfig['standardOddsDesc']) . '-' . count($this->_lotteryConfig['standardOdds']);
            }
        }

        if ($checkPeriodCode && $success) {
            $this->_runConfig($this->_context, $this->_cid, $item = 'periodCodeFormat', $this->_lotteryConfig);
            $this->_context->periodCodeFormat = true;
            if (!$this->_context->allowNext()) {
                $success = false;
                $this->_errors[] = $this->_context->getReason();
            }
        }

        if ($success) {
            $this->_runConfig($this->_context, $this->_cid, $item = 'format', $this->_lotteryConfig);
            if (!$this->_context->allowNext()) {
                $success = false;
                $this->_errors[] = $this->_context->getReason();
            }
        }

        if ($success) {
            $this->_runConfig($this->_context, $this->_cid, $item = 'valid', $this->_lotteryConfig);
            if (!$this->_context->allowNext()) {
                $success = false;
                $this->_errors[] = $this->_context->getReason();
            }
        }

        return $success;
    }

    /**
     * 获取错误信息
     * @return array
     */
    public function getErrors() {
        return $this->_errors;
    }

    /**
     * 执行
     * @return array
     */
    public function run() {
        $success = true;
        $money   = 0;

        if (empty($this->_context) || !$this->_context->periodCodeFormat) {
            $success         = false;
            $this->_errors[] = '先运行isValid(true)方法';
        }
        if (!$this->_context->allowNext()) {
            $success         = false;
            $this->_errors[] = $this->_context->getReason();
        }

        if ($success) {
            $this->_runConfig($this->_context, $this->_cid, $item = 'result', $this->_lotteryConfig);//计算是否中奖及中奖注数
            if (!$this->_context->allowNext()) {
                $success         = false;
                $this->_errors[] = $this->_context->getReason();
            }
        }

        //var_dump($this->_odds);

        if ($success) {
            //六合彩的连肖是组合赔率
            if($this->_cid == 51 && in_array($this->_playId, [551, 552, 553, 554])){
                foreach ($this->_context->oddsList as $k => $v) {//根据赔率计算中奖金额
                    if ($this->_context->isWin[$k]) {
                        $money += $v * $this->_bet * $this->_betMoney * $this->_context->winBetCount[$k];
                    }
                }
            }else{
                foreach ($this->_odds as $k => $v) {//根据赔率计算中奖金额
                    if ($this->_context->isWin[$k]) {
                        $money += $v * $this->_bet * $this->_betMoney * $this->_context->winBetCount[$k];
                    }

                    /*
                      var_dump($v);
                      var_dump($this->_bet);
                      var_dump($this->_betMoney);
                      var_dump($this->_context->winBetCount[$k]);
                      var_dump($money);
                    */
                }
            }

            if ($this->_maxPrize > 0 && $money > $this->_maxPrize) {
                $money = $this->_maxPrize;
            }

            // $money = intval($money);
        }

        //var_dump($money);die;
        return [$success, $money];
    }

    /**
     * 取得下订单需要的金额（分）
     * @return array
     */
    public function getOrderMoney() {
        $success = true;
        $money = 0;
        if (!isset($this->_context->betCount)) {
            $success = false;
            $this->_errors[] = '先运行isValid()方法';
        } else {
            $money = $this->_bet * $this->_betMoney * $this->_context->betCount;
        }
        return [$success, $money];
    }

    /**
     * 取结果和值
     * @return integer
     */
    public function getPeriodCodeSum() {
        return array_sum($this->_context->periodCodeList);
    }

    /**
     * 获取中奖项目
     * @return array
     */
    public function getWin() {
        return $this->_context->isWin;
    }

    /**
     * 获取格式化过后的玩法内容
     * @return string
     */
    public function getPlayNumber() {
        return $this->_context->playNumber;
    }

    /**
     * 判断是否有中奖
     * @return boolean
     */
    public function isWin() {
        if (isset($this->_context) && isset($this->_context->isWin)) {
            foreach ($this->_context->isWin as $win) {
                if ($win) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * 获取中奖注数
     * @return array
     */
    public function getWinBetCount() {
        return $this->_context->winBetCount;
    }

    /**
     * 获取中奖显示注数
     * @return array
     */
    public function showWinBetCount() {
        if (!empty($this->_context->winBetCount)) {
            $winBetCount = [];
            //六合彩的连肖是组合赔率
            if($this->_cid == 51 && in_array($this->_playId, [551, 552, 553, 554])){
                return $this->_context->winSx;
            }else{
                foreach ($this->_context->lotteryConfig['standardOddsDesc'] as $k => $v) {
                    if (isset($this->_context->winBetCount[$k]) && $this->_context->winBetCount[$k] > 0) {
                        $winBetCount[$v] = $this->_context->winBetCount[$k];
                    }
                }
            }
            return $winBetCount;
        }
        return [];
    }

    /**
     * 取出前端需要显示的赔率
     * @return array
     */
    public function getShowOdds() {
        if (count($this->_context->odds) == 1 || $this->_context->playId == 2 || $this->_context->playId == 3) {
            return array_combine($this->_context->lotteryConfig['standardOddsDesc'], $this->_context->odds);
        }

        //六合彩的连码三中二，二中特 有两个赔率
        if($this->_cid == 51 && in_array($this->_playId, [546, 549])){
            return array_combine($this->_context->lotteryConfig['standardOddsDesc'], $this->_context->odds);
        }

        //六合彩的连肖是组合赔率
        if($this->_cid == 51 && in_array($this->_playId, [551, 552, 553, 554])){
            return $this->_context->betOdds;
        }

        $odds = [];
        foreach ($this->_context->playNumberList as $playNumbers) {
            foreach ($playNumbers as $playNumber) {
                $index = array_search($playNumber, $this->_context->lotteryConfig['standardOddsDesc']);
                if ($index !== null && isset($this->_context->odds[$index])) {
                    $odds[$playNumber] = $this->_context->odds[$index];
                }
            }
        }

        if (empty($odds)) {
            return array_combine($this->_context->standardOddsDesc, $this->_context->odds);
        } else {
            return $odds;
        }
    }

    /**
     * 获取下单注数
     * @return integer
     */
    public function getBetCount() {
        return isset($this->_context->betCount) ? $this->_context->betCount : 0;
    }

    public function getDebug() {
        return $this->_context->debug;
    }

    /**
     * 获取配置
     *
     * @param  [type] $cid [description]
     *
     * @return array
     */
    public function getLotteryConfig($cid) {
        $this->_loadLotteryConfig($cid);
        return $this->_lotteryMaps[$cid];
    }

    /**
     * 优化显示
     * @param $cid
     * @param $playId
     * @param $playNumber
     * @return array [type] [description]
     */
    public function getPretty($cid, $playId, $playNumber) {
        $this->_cid = $cid;
        $this->getLotteryConfig($cid);

        $this->_lotteryConfig = array_merge((isset($this->_lotteryMaps[$this->_cid][0]) ? $this->_lotteryMaps[$this->_cid][0] : []), $this->_lotteryMaps[$this->_cid][$playId]);
        // $playNumbers = isset($this->_lotteryConfig['pretty']) ? $this->_runConfigSingle($playId, $playNumber, $cid, 'pretty', $this->_lotteryConfig) : $playNumber;
        $playNumbers = $this->_runConfigSingle($playId, $playNumber, $cid, 'pretty', $this->_lotteryConfig);
        return $playNumbers;
    }

    /**
     * 重置数据
     * @return void
     */
    public function clear() {
        $this->_cid = null;
        $this->_periodCode = null;
        $this->_playId = null;
        $this->_odds = null;
        $this->_playNumber = null;
        $this->_maxPrize = null;
        $this->_errors = [];
        $this->_context = null;
        $this->_lotteryConfig = null;
    }

    /**
     * 检查映射关系
     * @return bool
     */
    protected function _checkMaps() {
        return isset($this->_maps[$this->_cid]) ? true : false;
    }

    /**
     * 加载配置
     *
     * @param  [type] $cid      [description]
     * @param  string $fileName [description]
     *
     * @return array
     */
    protected function _loadLotteryConfig($cid, $fileName = 'CoreConfig.php') {
        if (!isset($this->_lotteryMaps[$cid])) {
            $dir = explode('\\', $this->_maps[$cid]);
            $dir = end($dir);
            $this->_lotteryMaps[$cid] = require __DIR__ . DIRECTORY_SEPARATOR . $dir . DIRECTORY_SEPARATOR . $fileName;
        }
        return $this->_lotteryMaps[$cid];
    }

    /**
     * 执行配置
     *
     * @param  [type] $context       [description]
     * @param  [type] $cid           [description]
     * @param  [type] $item          [description]
     * @param  [type] $lotteryConfig [description]
     *
     * @return void
     */
    protected function _runConfig($context, $cid, $item, $lotteryConfig) {
        list($obj, $func) = explode('::', $lotteryConfig[$item]['exec']);

        $lotteryConfig[$item]['args'] = array_merge(['context' => $context], (isset($lotteryConfig[$item]['args']) ? $lotteryConfig[$item]['args'] : []));

        call_user_func_array([$this->_maps[$cid] . '\\' . $obj, $func], $lotteryConfig[$item]['args']);
    }

    /**
     * 执行配置不包含Context
     *
     * @param  [type] $context       [description]
     * @param  [type] $cid           [description]
     * @param  [type] $item          [description]
     * @param  [type] $lotteryConfig [description]
     *
     * @return array
     */
    protected function _runConfigSingle($playId, $playNumber, $cid, $item, $lotteryConfig) {
        if (!isset($lotteryConfig[$item])) {
            return $playNumber;
        }

        list($obj, $func) = explode('::', $lotteryConfig[$item]['exec']);

        $lotteryConfig[$item]['args'] = array_merge(['playId' => $playId, 'playNumber' => $playNumber], (isset($lotteryConfig[$item]['args']) ? $lotteryConfig[$item]['args'] : []));
        $isExistMethod = is_callable([$this->_maps[$cid] . '\\' . $obj, $func]);
        if ($isExistMethod) {
            return call_user_func_array([$this->_maps[$cid] . '\\' . $obj, $func], $lotteryConfig[$item]['args']);
        } else {
            return call_user_func_array([$this->_maps[$cid] . '\\' . $obj, 'prettyDwd'], $lotteryConfig[$item]['args']);
        }
    }
}