<?php

namespace Logic\Data;

use Logic\Logic;
/*
 * 数据迁移 @author viva
 *
 */
class Migration extends Logic {
    /*
     * 需要迁移的数据表
     * 时间格式，时间戳与日期格式  （查询需要）
     */
    private $tables = [
        'send_prize'        =>  ['time'=>'created','type'=>'date','count'=>1000],
        'lottery_order'     =>  ['time'=>'created','type'=>'date','count'=>1000],
        'lottery_chase'     =>  ['time'=>'created','type'=>'date','count'=>2000],
        'funds_deposit'     =>  ['time'=>'created','type'=>'date','count'=>1000],
        'funds_deal_log'    =>  ['time'=>'created','type'=>'date','count'=>1000],
        'funds_deal_manual' =>  ['time'=>'created','type'=>'time','count'=>1000],
        'message'           =>  ['time'=>'created','type'=>'time','count'=>2000],
        'message_pub'       =>  ['time'=>'created','type'=>'time','count'=>2000],
        'crease_money'      =>  ['time'=>'created','type'=>'date','count'=>2000],
        'user_rake_log'     =>  ['time'=>'created','type'=>'time','count'=>2000],
        'transfer_order'    =>  ['time'=>'created','type'=>'date','count'=>2000],
        'transfer_log'      =>  ['time'=>'created','type'=>'time','count'=>2000],
    ];
    private $connect = 'default';      // 历史表库
    private $alas = '_hist';      // 历史表后后缀
    private $count = 1000;        //每次迁移数据的条数
    private $maxMoverCount = 200000;        //每次迁移数据的总条数
    private $sucMoverCount = 0;        //每次迁移数据的总条数
    private $endTime;        //每次迁移结束时间
    private $maxId;        //每次迁移结束最大ID
    private $maxExeTime = 200000000000000000;        //每次迁移数据执行最大秒数  进程是无超时时间概念
    private $startExeTime;        //每次迁移数据执行最大秒数

    /*
     * 默认迁移两个月的数据
     */
    public function init(int $count = 0,string $endTime = '') {
        if($count && $count > 0) {
            $this->count = $count;
        }
        $endTime = @date('Y-m-d',strtotime($endTime));
        if($endTime && $endTime != '1970-01-01') {
            $this->endTime = $endTime;
        }else {
            $this->endTime = date('Y-m-d',strtotime('-2 months'));
        }
    }



    public function __construct($ci) {
        parent::__construct($ci);
        $this->init();
        $this->startExeTime = time();
    }

    public function start(){
        $res = [];
        $tables = array_keys($this->tables);
        foreach ($tables as $val){
            $res[] = [
                        'table'   => $val,
                        'success' => $this->moveTable((string)$val),
                    ];
            if($this->maxMoverCount <= $this->sucMoverCount)
                break;
        }
        return $res;
    }

    public function moveTable(string $table){
        $re = 0;
        $this->maxId = \DB::table($table)->orderBy('id','asc')->value('id') - 1;
        while (1){
            $suc = $this->move($table);
            $re += $suc;
            $this->sucMoverCount += $suc;
            $exe_time = time() - $this->startExeTime;
            if($suc < $this->count || $this->maxMoverCount <= $this->sucMoverCount || $exe_time >= $this->maxExeTime)
                return $re;
            sleep(1);
        }
        return $re;
    }
    /*
     *   以插入数据的最大ID来删除相应的数据
     *
     */
    public function move(string $table){
        if(in_array($table,array_keys($this->tables))) {
            try {
                $this->db->getConnection()->beginTransaction();
                $rule = $this->tables[$table];
                $this->count = $rule['count'] > 0 ? $rule['count'] : $this->count;
                if($rule['type'] == 'time') {
                    $end_time = strtotime($this->endTime.' 23:59:59');
                }else {
                    $end_time =$this->endTime.' 23:59:59';
                }
                if($this->maxId){
                    $tmp = $this->maxId + $this->count;
                    $data = \DB::table($table)->orderBy('id','asc')->where('id','<=',$tmp)->where($rule['time'],'<=',$end_time)->get()->toArray();
                }else{
                    $data = \DB::table($table)->orderBy('id','asc')->where($rule['time'],'<=',$end_time)->forPage(1,$this->count)->get()->toArray();
                }

                if($data) {
                    //转数组
                    foreach ($data as &$val) {
                        $val = (array)$val;
                    }
                    $insert = \DB::connection($this->connect)->table($table . $this->alas)->insert($data);
                    $max_id = max(array_column($data, 'id'));
                    $delete = \DB::table($table)->where('id', '<=', $max_id)->delete();
                    $this->maxId = $max_id;
                    if ($insert == $delete) {
                        $this->db->getConnection()->commit();
                        return count($data);
                    } else {
                        $this->db->getConnection()->rollback();
                        return 0;
                    }
                }
            }catch (\Exception $e) {
                $this->db->getConnection()->rollback();
            }
        }
        return 0;
    }

}