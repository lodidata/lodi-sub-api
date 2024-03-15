<?php

use Utils\Www\Action;
use Model\LotteryInfo;

return new class extends Action
{
    const HIDDEN = true;
    const TITLE = "GET 查询彩种开奖历史 只查当天的";
    const HINT = "开发的技术提示信息";
    const DESCRIPTION = "查询彩种开奖历史   \r\n返回attributes[number =>'第几页', 'size' => '记录条数'， total=>'总记录数'] \r\n Exam: /lottery/historylist?id=6&page=2&page_size=30";
    const TAGS = "彩票";
    const QUERY = [
        "id" => "int(required) #彩票id",
        'page'  => "int(,1) #第几页 默认为第1页",
        "page_size" => "int(,10) #分页显示记录数 默认10条记录"
    ];
    const SCHEMAS = [
        [
            "lottery_type" => "int() #彩种ID",
            "lottery_number" => "int() #彩票期号",
            "end_time" => "int() #结束时间",
            "period_result" => "string() #开奖结果",
            "period_code" => "string() #开奖号码",
            "official_time" => "int() #开奖时间",
        ],
    ];


    public function run($aid = 0)
    {
        if ($aid > 0) {
            $id = (int)$aid;
        } else {
            $id = (int)$this->request->getQueryParam('id', 0);
        }

        $page     = (int)$this->request->getQueryParam('page', 1);
        $pageSize = (int)$this->request->getQueryParam('page_size', 10);

        if ($id == 0 || $pageSize > 100) {
            return $this->lang->set(13);
        }
        $lotterCommon = new  \Logic\Lottery\Common();
        $date = date('Y-m-d 0:0:0');
        $time = strtotime($date);

        // 10页的类型直接请求核心历史缓存
        if ($page == 1 && $pageSize == 10) {
            $data = LotteryInfo::getCacheFinishedHistory($id);
            $data && $data = array_reverse($data);
            foreach ($data as $key => $val) {

                if ($val['period_code'] && $id == 52) {
                    $period_code_arr = explode(',', $val['period_code']);
                    $sx = [];
                    foreach ($period_code_arr as $v) {
                        $sx[] = $lotterCommon->getSx($v);
                    }
                    $data[$key]['sx'] = implode(',', $sx);

                } else {
                    $data[$key]['sx'] = null;
                }

            }
            return $this->lang->set(0, [], $data, ['number' => $page, 'size' => $pageSize, 'total' => count($data)]);
        }

        // page小于3的使用缓存
        if (($page <= 3 && $pageSize <= 30) || ($page == 1 && $pageSize == 100)) {
            $history = LotteryInfo::getCacheFinishedHistory($id);
            $history && $history = array_reverse($history);
            $data = $this->redis->get(\Logic\Define\CacheKey::$perfix['longLotteryInfoFinishedHistoryList'] . '_' . $id);
            $data = !empty($data) ? json_decode($data, true) : [];
            $isRefresh = false;

            // 判断是否强制刷新
            /*if (isset($data['data']) && !empty($data['data'])) {
                $end = $data['data'][0]['lottery_number'];
                if (isset($history[0]) && $history[0]['lottery_number'] > $end) {
                    $isRefresh = true;
                }
            }*/

            // 写入前三页数据缓存
            if (empty($data) || $isRefresh) {
                $data = LotteryInfo::where('lottery_type', $id)
                    ->where('period_code', '!=', '')
                    ->where('period_code_part', '!=', '')
                    ->where('end_time','>', $time)
                    ->orderby('lottery_number', 'asc')
                    ->select([
                        'lottery_type', 'pid', 'lottery_name',
                        $this->db->getConnection()
                            ->raw('UNIX_TIMESTAMP(NOW()) AS now_time'),
                        'period_result',
                        'period_code',
                        'end_time',
                        'official_time',
                        'lottery_number',
                    ])
                    ->paginate(100, ['*'], 'page');
                $total = $data->total();
                $data = $data->toArray()['data'];
                $this->redis->setex(\Logic\Define\CacheKey::$perfix['longLotteryInfoFinishedHistoryList'] . '_' . $id, 60, json_encode(compact('total', 'data')));
                $data = array_slice($data, ($page - 1) * $pageSize, $pageSize);
            } else {
                $total = $data['total'];
                $data = array_slice($data['data'], ($page - 1) * $pageSize, $pageSize);
            }
        } else {
            $data = LotteryInfo::where('lottery_type', $id)
                ->where('period_code', '!=', '')
                ->where('period_code_part', '!=', '')
                ->where('end_time','>', $time)
                ->orderby('lottery_number', 'asc')
                ->select([
                    'lottery_type', 'pid', 'lottery_name',
                    $this->db->getConnection()
                        ->raw('UNIX_TIMESTAMP(NOW()) AS now_time'),
                    'period_result',
                    'period_code',
                    'end_time',
                    'official_time',
                    'lottery_number',
                ])
                ->paginate($pageSize, ['*'], 'page', $page);
            $total = $data->total();
            $data = $data->toArray()['data'];
        }
        foreach ($data as $key => $val) {
            if ($val['period_code'] && $id == 52) {
                $period_code_arr = explode(',', $val['period_code']);
                $sx = [];
                foreach ($period_code_arr as $v) {
                    //$sx[] = $this->getSx($v);
                    $sx[] = $lotterCommon->getSx($v);
                }
                if (empty($val['period_code'])) {
                    $data[$key]['sx'] = null;
                } else {
                    $data[$key]['sx'] = implode(',', $sx);
                }

            } else {
                $data[$key]['sx'] = null;
            }
        }
        return $this->lang->set(0, [], $data, ['number' => $page, 'size' => $pageSize, 'total' => $total]);
    }
    /*public function run($aid = 0)
    {
        if ($aid > 0) {
            $id = (int)$aid;
        } else {
            $id = (int)$this->request->getQueryParam('id', 0);
        }

        $page     = (int)$this->request->getQueryParam('page', 1);
        $pageSize = (int)$this->request->getQueryParam('page_size', 10);

        if ($id == 0 || $pageSize > 100) {
            return $this->lang->set(13);
        }
        $lotterCommon = new  \Logic\Lottery\Common();
        // 10页的类型直接请求核心历史缓存
        if ($page == 1 && $pageSize == 10) {
            $data = LotteryInfo::getCacheFinishedHistory($id);
            $data && $data = array_reverse($data);
            foreach ($data as $key => $val) {

                if ($val['period_code'] && $id == 52) {
                    $period_code_arr = explode(',', $val['period_code']);
                    $sx = [];
                    foreach ($period_code_arr as $v) {
                        $sx[] = $lotterCommon->getSx($v);
                    }
                    $data[$key]['sx'] = implode(',', $sx);

                } else {
                    $data[$key]['sx'] = null;
                }

            }
            return $this->lang->set(0, [], $data, ['number' => $page, 'size' => $pageSize, 'total' => count($data)]);
        }

        // page小于3的使用缓存
        if (($page <= 3 && $pageSize <= 30) || ($page == 1 && $pageSize == 100)) {
            $history = LotteryInfo::getCacheFinishedHistory($id);
            $history && $history = array_reverse($history);
            $data = $this->redis->get(\Logic\Define\CacheKey::$perfix['longLotteryInfoFinishedHistoryList'] . '_' . $id);
            $data = !empty($data) ? json_decode($data, true) : [];
            $isRefresh = false;

            // 判断是否强制刷新
            if (isset($data['data']) && !empty($data['data'])) {
                $end = $data['data'][0]['lottery_number'];
                if ($history[0]['lottery_number'] > $end) {
                    $isRefresh = true;
                }
            }

            // 写入前三页数据缓存
            if (empty($data) || $isRefresh) {
                $data = LotteryInfo::where('lottery_type', $id)
                    ->where('period_code', '!=', '')
                    ->where('period_code_part', '!=', '')
                    ->orderby('lottery_number', 'asc')
                    ->select([
                        'lottery_type', 'pid', 'lottery_name',
                        $this->db->getConnection()
                            ->raw('UNIX_TIMESTAMP(NOW()) AS now_time'),
                        'period_result',
                        'period_code',
                        'end_time',
                        'official_time',
                        'lottery_number',
                    ])
                    ->paginate(100, ['*'], 'page', $page);
                $total = $data->total();
                $data = $data->toArray()['data'];
                $this->redis->setex(\Logic\Define\CacheKey::$perfix['longLotteryInfoFinishedHistoryList'] . '_' . $id, 60, json_encode(compact('total', 'data')));
                $data = array_slice($data, ($page - 1) * $pageSize, $pageSize);
            } else {
                $total = $data['total'];
                $data = array_slice($data['data'], ($page - 1) * $pageSize, $pageSize);
            }
        } else {
            $data = LotteryInfo::where('lottery_type', $id)
                ->where('period_code', '!=', '')
                ->where('period_code_part', '!=', '')
                ->orderby('lottery_number', 'asc')
                ->select([
                    'lottery_type', 'pid', 'lottery_name',
                    $this->db->getConnection()
                        ->raw('UNIX_TIMESTAMP(NOW()) AS now_time'),
                    'period_result',
                    'period_code',
                    'end_time',
                    'official_time',
                    'lottery_number',
                ])
                ->paginate($pageSize, ['*'], 'page', $page);
            $total = $data->total();
            $data = $data->toArray()['data'];
        }
        foreach ($data as $key => $val) {
            if ($val['period_code'] && $id == 52) {
                $period_code_arr = explode(',', $val['period_code']);
                $sx = [];
                foreach ($period_code_arr as $v) {
                    //$sx[] = $this->getSx($v);
                    $sx[] = $lotterCommon->getSx($v);
                }
                if (empty($val['period_code'])) {
                    $data[$key]['sx'] = null;
                } else {
                    $data[$key]['sx'] = implode(',', $sx);
                }

            } else {
                $data[$key]['sx'] = null;
            }
        }
        return $this->lang->set(0, [], $data, ['number' => $page, 'size' => $pageSize, 'total' => $total]);
    }*/
};