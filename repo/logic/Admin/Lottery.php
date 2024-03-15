<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/4/18
 * Time: 11:19
 */

namespace Logic\Admin;

use DB;
use lib\exception\BaseException;
class Lottery extends \Logic\Logic
{

    public function __construct($ci)
    {
        parent::__construct($ci);

    }

    public function curperiod($id = NULL) {
        $now = time();
        if ($id == NULL || is_array($id)) {
            $where = '';
            if($id) {
                $ids = '"'.implode('","',$id).'"';
                $where = ' AND id IN('.$ids.')' ;
            }

            $query = DB::table('lottery_info')
                ->select(DB::raw('lottery_type,start_time,end_time,official_time,lottery_number,state '))
                ->where('start_time','<=',$now)
                ->whereRaw("end_time >$now $where");

        } else {

            $open_time = DB::table('lottery_info')->where('lottery_type',$id)->value(DB::raw("end_time-start_time"));
            $start_time_min = $now - $open_time;
            $end_time_max = $now + $open_time;

            $query = DB::table('lottery_info')
                ->select(DB::raw('lottery_type,start_time,end_time,official_time,lottery_number,state '))
                ->where('lottery_type',$id)
                ->where('start_time','>=',$start_time_min)
                ->where('start_time','<=',$now)
                ->where('end_time','>=',$now)
                ->where('end_time' ,'<', $end_time_max)
                ->limit(1);
        }

        $res = $query->get()->toArray();
//        print_r(DB::getQueryLog(0));print_r($res);exit;
        if(!$res){
            $newResponse = createRsponse($this->response,200,10052,'未实现或无法处理');
            throw new BaseException($this->request,$newResponse);
        }
        return array_map('get_object_vars',$res);


    }

    public function  periods($id, $now_number){

        $res = DB::table('lottery_info')
            ->select(['id','lottery_number','lottery_type','period_code','start_time','end_time','catch_time','official_time','state'])
            ->where('lottery_type',$id)
            ->where('lottery_number','<=',$now_number)
            ->orderBy('lottery_number','desc')
            ->limit(1)
            ->get()
            ->toArray();
        $res = array_map('get_object_vars',$res);
        if(!$res){
            $newResponse = createRsponse($this->response,404,10,'未实现或无法处理');
            throw new BaseException($this->request,$newResponse);
        }
        return $res;
    }

    /**
     * 系统开奖设置
     * @param int $lottery_id
     * @param string $type
     * @param array $params
     * @return bool
     */
    public function openresult($lottery_id, $type, $params = []) {

        $table = 'open_lottery_'.$type.'_'.$lottery_id;
//        print_r($params);exit;
        $query = DB::table("$table as a")
            ->select(['*'])
            ->orderBy('lottery_number','desc');

        if(isset($params['number_from'])){
            $query = $query->where('lottery_number','>=',$params['number_from']);
        }
        if(isset($params['number_to'])){
            $query = $query->where('lottery_number','<=',$params['number_to']);
        }


        if(isset($params['date_from'])){
            $query = $query->where('start_time','>=',$params['date_from']);
        }

        if(isset($params['date_to'])){
            $query = $query->where('start_time','<=',$params['date_to']);
        }

        if(isset($params['start_official_time'])){
            $query = $query->where('official_time','>=',$params['start_official_time']);
        }

        if(isset($params['end_official_time'])){
            $query = $query->where('official_time','<=',$params['end_official_time']);
        }

        if(isset($params['openStatus'])){
            if($params['openStatus']== 2){
                $status = "FIND_IN_SET('open',a.state)";
                $query = $query->whereRaw($status);
            }elseif($params['openStatus']== 1){
                $status = "!FIND_IN_SET('open',a.state)";
                $query = $query->whereRaw($status);
            }
        }
        $res['attributes']['total'] =  $query->count();
        $data = $query->forPage($params['page'],$params['page_size'])->get()->toArray();
        $res['data'] = array_map('get_object_vars',$data);

        $res['attributes']['number'] =  $params['page'];
        $res['attributes']['size'] =  $params['page_size'];

        return $res;

    }

    /**
     * 获取开奖历史
     * @param array $params
     * @return mixed
     */
    public function result($params = []) {
//        print_r($params);exit;
        $query = DB::table("lottery_info")
            ->select(['*'])
            ->where('lottery_type',$params['lottery_type'])
            ->orderBy('lottery_number','desc');

        if(isset($params['number_from'])){
            $query = $query->where('lottery_number','>=',$params['number_from']);
        }
        if(isset($params['number_to'])){
            $query = $query->where('lottery_number','<=',$params['number_to']);
        }


        if(isset($params['date_from'])){
            $query = $query->where('start_time','>=',$params['date_from']);
        }

        if(isset($params['date_to'])){
            $query = $query->where('start_time','<=',$params['date_to']);
        }

        if(isset($params['start_official_time'])){
            $query = $query->where('official_time','>=',$params['start_official_time']);
        }

        if(isset($params['end_official_time'])){
            $query = $query->where('official_time','<=',$params['end_official_time']);
        }

        if(isset($params['openStatus']) && $params['openStatus']== 1){

            $status = "!FIND_IN_SET('open',state)";
            $query = $query->whereRaw($status);
        }else{
            $status = "FIND_IN_SET('open',state)";
            $query = $query->whereRaw($status);
        }
        $res['attributes']['total'] =  $query->count();
        $data = $query->forPage($params['page'],$params['page_size'])->get()->toArray();
        $res['data'] = array_map('get_object_vars',$data);

        $res['attributes']['number'] =  $params['page'];
        $res['attributes']['size'] =  $params['page_size'];
//        print_r(DB::getQueryLog());exit;

        return $res;
    }
}