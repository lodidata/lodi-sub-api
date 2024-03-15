<?php

use Utils\Www\Action;
use Model\Hall;
use Model\Room;

return new class extends Action
{
    const HIDDEN = true;
    const TITLE = "根据彩种获取厅信息";
    const DESCRIPTION = "接口";
    const TAGS = "彩票";
    const QUERY = [
        "lottery_id" => "int(required) #对应彩票id，比如：时时彩、11选5、快三...，见彩种列表接口",
        "id" => "int #"
    ];
    const SCHEMAS = [
        [
            "id" => "int(option) # id",
            "hall_name" => "string(option) # 房间名称",
            "rebet_desc" => "string(option) # 回水描述"
        ]
    ];


    public function run()
    {
        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }
        $type = !$this->auth->isMobilePlatform() ? 1 : 2;
        $lotteryId = (int)$this->request->getQueryParam('lottery_id', 0);
        $hallId = (int)$this->request->getQueryParam('hall_id', 0);
        if ($lotteryId == 0) {
            return $this->lang->set(10);
        }
        $data = $this->redis->get(\Logic\Define\CacheKey::$perfix['hall'] . '_' . $lotteryId . '_' . $hallId);
        if (empty($data) || true) {
            if ($hallId > 0) {
                $data = Hall::where('lottery_id', $lotteryId)->where('id', $hallId)->get([
                    'id', 'hall_name', 'hall_level', 'rebet_desc', 'min_bet', 'max_bet', 'min_balance', 'rebet_condition', 'rebet_config'
                ])->toArray();
            } elseif ($type == 1) {  //PC房
                $data = Hall::where('lottery_id', $lotteryId)->where('is_pc', 1)->where('hall_level', 4)->get([
                    'id', 'hall_name', 'hall_level', 'rebet_desc', 'min_bet', 'max_bet', 'min_balance', 'rebet_condition', 'rebet_config'
                ])->toArray();
            } else {
                $data = Hall::where('lottery_id', $lotteryId)->where('is_pc', 0)->get([
                    'id', 'hall_name', 'hall_level', 'rebet_desc', 'min_bet', 'max_bet', 'min_balance', 'rebet_condition', 'rebet_config'
                ])->toArray();
            }

            if (empty($data)) {
                return $this->lang->set(61);
            }

            foreach ($data as $k => $v) {
                $data[$k]['min_bet'] = $data[$k]['min_bet'] * 100;
                $data[$k]['min_balance'] = $data[$k]['min_balance'] * 100;
                $data[$k]['max_bet'] = $data[$k]['max_bet'] * 100;
                $data[$k]['room_data'] = Room::where('hall_id', $v['id'])->get(['id', 'room_name', 'number'])->toArray();
            }

            $this->redis->setex(\Logic\Define\CacheKey::$perfix['hall'] . '_' . $lotteryId . '_' . $hallId, 5, json_encode($data));
        } else {
            $data = json_decode($data, true);
        }
        $data = $this->setRoomNum($data);
        return $data;
    }

    public function setRoomNum($data)
    {
        $userId=$this->auth->getUserId();
        foreach ($data as $key => $datum) {
            foreach ($datum['room_data'] as $rKey => $room_datum) {
                $num=$this->redis->exists(\Logic\Define\CacheKey::$perfix['hallNum'] . '_' . $room_datum['id']);
                if($num>0){
                    $this->redis->hDel(\Logic\Define\CacheKey::$perfix['hallNum'] . '_' . $room_datum['id'],'user_id'.$userId);

                    $count=$this->redis->hLen(\Logic\Define\CacheKey::$perfix['hallNum'] . '_' . $room_datum['id']);

                    $datum['room_data'][$rKey]['number'] = $room_datum['number']+$count;
                }

            }
            $data[$key]['room_data'] = $datum['room_data'];
        }
        return $data;
    }
};