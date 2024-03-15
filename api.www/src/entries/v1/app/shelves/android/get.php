<?php
use Utils\Www\Action;
return new class extends Action
{
    const HIDDEN = true;
    const TITLE       = '上架包信息';
    const DESCRIPTION = '';
    const QUERY       = [
        'channel_id'    => 'string(, 30) #渠道ID',
        'longitude'     => 'float(, 30) #经度',
        'latitude'      => 'float(, 30) #b纬度',
    ];
    const SCHEMAS     = [
        'channel_id'    => 'string(, 30) #渠道ID',
        'name'          => 'string() #架包名称',
        'status'        => 'int() #上架包状态 0审核中 ',
    ];
    public function run()
    {
        $channel_id = $this->request->getParam('channel_id');
        $longitude = $this->request->getParam('longitude');
        $latitude = $this->request->getParam('latitude');
        $device = $this->request->getHeaders()['HTTP_UUID'] ?? '';
        $result =  array(
            'channel_id'=>'',
            'name'=>'',
            'status'=>0,
        );
        try {
            if (!$channel_id) {
                return $result;
            }
            $android = \Model\AppBag::where('channel_id', '=', $channel_id)->where('type', '=', 3)->where('delete', '=', 0)->first();
            if (!$android) {
                return $result;
            }
            $channel = substr($channel_id, strrpos($channel_id, '_') + 1) ?? substr($channel_id, strrpos($channel_id, '-') + 1);
            $channel = $android->channel ? $android->channel : $channel;
            $result['status'] = $android->status;
            $result['channel_id'] = $android->channel_id;
            $result['name'] = $android->name;

            if ($android->status == 9) {  //若为9表示抓取IP  审核状态并返回 0
                $android->status = $result['status'] = 0;
                $location = (new \Model\AppBag())->getAreaByIp();
                $data = [
                    'ip' => \Utils\Client::getIp(),
                    'channel_id' => $channel_id,
                    'channel' => $channel,
                    'area' => $location['country'] ?? '',
                ];
                try {
                    \DB::table('app_black_ip')->insert($data);
                } catch (\Exception $e) {
                    return $result;
                }
            } elseif ($android->status) {
                //状态规则 {"start_time":"","end_time":""}  只有时分秒
                $date = date('H:i:s');
                $status_rule = json_decode($android->status_rule,true);
                if(isset($status_rule['start_time']) && !empty($status_rule['start_time']) &&
                    isset($status_rule['end_time']) && !empty($status_rule['end_time'])) {
                    if($date >= $status_rule['start_time'] && $date <= $status_rule['end_time'])
                        $result['status'] = 0;

                }
                $result['status'] = $result['status'] ? (new \Model\AppBag())->isBlackArea($channel_id, $channel, $android->status,$longitude,$latitude) : 0;
            }
            //是否是第一次打开该包,记录激活日志
            try {
                $data = [
                    'ip' => \Utils\Client::getIp(),
                    'channel_id' => $channel_id,
                    'channel' => $channel,
                    'app' => $android->name,
                    'device' => is_array($device) ? implode(',',$device) : $device,
                ];
                \DB::table('app_active_log')->insert($data);
            } catch (\Exception $e) {
                return $result;
            }
        }catch (\Exception $e){
            return array(
                'channel_id'=>'',
                'name'=>'',
                'status'=>0,
            );
        }
        return $result;
    }
};
