<?php

namespace Model;

use Utils\Client;
use Utils\IpLocation;

class AppBag extends \Illuminate\Database\Eloquent\Model {
    protected $table = 'app_bag';

    public function ipLocationStatus($status) {
        global $app;

        $logger = $app->getContainer()->logger;
        $data = \DB::table('app_kf')
                   ->first();

        if ($data->switch) {
            $ip = new  IpLocation();
            $ip->init();
            $location = $ip->getlocation();
            $ip->close();
            $country = $location['country'];
            $log = [
                'area'    => $data->area,
                'ip'      => Client::getIp(),
                'country' => $country,
                'before'  => $status,
            ];
            if (strstr($country, $data->area)) {
                $status = 0;
            }
            $log['after'] = $status;
            $logger->info('IP地区屏蔽', $log);
        }

        return $status;
    }

    public function getAreaByIp($ip = null) {
        $loc = new  IpLocation();
        $loc->init();
        $location = $loc->getlocation($ip);
        return $location;
    }

    /**
     * 基于余弦定理求两经纬度距离
     * @param $channel_id 渠道ID
     * @param $channel 渠道
     * @param $status 当前状态
     * @param $longitude 当前经度
     * @param $latitude 当前纬度
     * @return 返回状态
     * */
    public function isBlackArea($channel_id, $channel, $status,$longitude = null,$latitude = null) {
        global $app;

        $logger = $app->getContainer()->logger;
        $re = $status;
        $data = \DB::table('app_kf')
                   ->first();

        if ($data && $data->switch && $channel ||true) {
            $lo = new  IpLocation();
            $lo->init();
            $ip = Client::getIp();
//            $ip = '104.27.128.182';//美国
//            $ip = '27.109.112.0';//柬埔寨
//            $ip = '59.125.39.5';//台湾省
//            $ip = '216.58.221.228';//美国
//            $ip = '98.137.246.8';//美国
//            $ip = '47.52.61.245';//香港
//            $ip = '183.14.31.219';//广东省深圳市
//            $ip = '27.109.128.1';//澳门
//            $ip = '157.80.255.255';//日本
//            $ip = '103.11.183.255';//中国
//            $ip = '123.125.71.38';//北京市
            $location = $lo->getlocation($ip);
            $lo->close();
            $country = $location['country'] ?? '';
            //总屏蔽地区
            if ($data->area && strstr($country, $data->area)) {
                $re = 0;
            } else {
                //是否在IP黑名单中
                $black = \DB::table('app_black_ip')
                            ->where('ip', '=', $ip)
                            ->where('channel', '=', $channel)
                            ->first();
                if ($black) {
                    $re = 0;
                }

                //是否在地区黑名单中
                $area = $re ? \DB::table('app_black_area')
                           ->where('channel', '=', $channel)
                           ->value('area') : '';
                //屏蔽海外地区时，找不到中国，找不到省，找不到市 ，代表是海外
                if(strpos($area,'海外') !== false && $re) {
                    if(
                       !strstr($country, '中国') &&
                       !strstr($country, '澳门') &&
                       !strstr($country, '香港') &&
                       !strstr($country, '台湾') &&
                       !strstr($country, '省') &&
                       !strstr($country, '市')) {
                        $re = 0;
                    }
                }
                if ($area && $re) {
                    foreach (explode('/', $area) as $v) {
                        if ($v && strstr($country, $v)) {
                            $re = 0;
                        }
                    }
                }
                if($longitude && $latitude) {
                    //是否在经纬度黑名单中
//                    $kilometre = \DB::table('app_black_kilometre')
//                        ->where('channel', '=', $channel)
//                        ->where('status', '=', 'enabled')
//                        ->get()->toArray();
//
//                    foreach ($kilometre as $val) {
//                        $kilo = $this->LantitudeLongitudeDist($longitude, $latitude, $val->longitude, $val->latitude);
//                        if ($kilo < $val->kilometre) {
//                            $re = 0;
//                            break;
//                        }
//                    }
                }
            }

            //进入日志
            $log = [
                'switch'     => $data->switch,
                'channel_id' => $channel_id,
                'ip'         => $ip,
                'area'       => json_encode($location),
                'before'     => $status,
                'after'      => $re,
            ];

            $logger->info('IP地区屏蔽', $log);
        }

        return $re;
    }


    /**
     * 转化为弧度(rad)
     * */
    public function rad(float $d)
    {
        return $d * M_PI / 180.0;
    }

    /**
     * 基于余弦定理求两经纬度距离
     * @param lon1 第一点的精度
     * @param lat1 第一点的纬度
     * @param lon2 第二点的精度
     * @param lat2 第二点的纬度
     * @return 返回的距离，单位km
     * */
    public function LantitudeLongitudeDist(float $lon1, float $lat1,float $lon2, float $lat2) {
        $EARTH_RADIUS = 6378137;  //赤道半径(单位m)
        $radLat1 = $this->rad($lat1);
        $radLat2 = $this->rad($lat2);

        $radLon1 = $this->rad($lon1);
        $radLon2 = $this->rad($lon2);

        if ($radLat1 < 0)
            $radLat1 = M_PI / 2 + abs($radLat1);// south
        if ($radLat1 > 0)
            $radLat1 = M_PI / 2 - abs($radLat1);// north
        if ($radLon1 < 0)
            $radLon1 = M_PI * 2 - abs($radLon1);// west
        if ($radLat2 < 0)
            $radLat2 = M_PI / 2 + abs($radLat2);// south
        if ($radLat2 > 0)
            $radLat2 = M_PI / 2 - abs($radLat2);// north
        if ($radLon2 < 0)
            $radLon2 = M_PI * 2 - abs($radLon2);// west
        $x1 = $EARTH_RADIUS * cos($radLon1) * sin($radLat1);
        $y1 = $EARTH_RADIUS * sin($radLon1) * sin($radLat1);
        $z1 = $EARTH_RADIUS * cos($radLat1);

        $x2 = $EARTH_RADIUS * cos($radLon2) * sin($radLat2);
        $y2 = $EARTH_RADIUS * sin($radLon2) * sin($radLat2);
        $z2 = $EARTH_RADIUS * cos($radLat2);

        $d = sqrt(($x1 - $x2) * ($x1 - $x2) + ($y1 - $y2) * ($y1 - $y2)+ ($z1 - $z2) * ($z1 - $z2));
        //余弦定理求夹角
        $theta = acos(($EARTH_RADIUS * $EARTH_RADIUS + $EARTH_RADIUS * $EARTH_RADIUS - $d * $d) / (2 * $EARTH_RADIUS * $EARTH_RADIUS));
        $dist = $theta * $EARTH_RADIUS;
        return $dist/1000;
    }

    public static function getList(){
        $query= AppBag::select(['type','url'])
            ->where('delete','=',0)
            ->get()->toArray();
        return $query;
    }

}