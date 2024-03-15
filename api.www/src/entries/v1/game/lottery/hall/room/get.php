<?php
use Utils\Www\Action;
use Model\RoomNotice;
return new class extends Action
{
    const HIDDEN = true;
    const TITLE = "根据彩种某厅房间列表信息";
    const DESCRIPTION = "接口";
    const TAGS = "彩票";
    const QUERY = [
       "lottery_id" => "int(required) #对应彩票id，比如：时时彩、11选5、快三...，见彩种列表接口",
       "hall_id" => "int(required)#厅ID"
   ];
    const SCHEMAS = [
   ];


    public function run() {
        $lotteryId = (int) $this->request->getQueryParam('lottery_id', 0);
        $hallId = (int) $this->request->getQueryParam('hall_id', 0);
        if ($lotteryId == 0 || $hallId == 0) {
            return $this->lang->set(10);
        }
        
        $data = $this->redis->get(\Logic\Define\CacheKey::$perfix['hallMessage'].'_'.$lotteryId.'_'.$hallId);
        if (empty($data) || true) {
            
            $data = RoomNotice::where('lottery_id', $lotteryId)->where('status', 'enable')->select([
                'title','content','hall_id','sleep_time'
            ])->first();

            if (!empty($data)) {
                $data = $data->toArray();
            }

            if (!empty($data) && !in_array($hallId, explode(',', str_replace(' ', '', $data['hall_id'])))) {
                $data = [];
            }



            if (!empty($data)) {
                $this->redis->setex(\Logic\Define\CacheKey::$perfix['hallMessage'].'_'.$lotteryId.'_'.$hallId, 5, json_encode($data));
            }
        } else {
            $data = json_decode($data, true);
        }
        return $data;
    }
};