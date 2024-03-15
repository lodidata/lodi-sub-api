<?php
use Logic\Admin\BaseController;
return new class() extends BaseController
{
    const TITLE       = '回水分析查询';
    const DESCRIPTION = '';

    const QUERY       = [
        'name' => 'string(required) # 用户名称',
        'start_date' => 'string(required) # 开始日期',
        'end_date' => 'string(required) # 结束日期，不允许超过30天',
    ];
    
    const PARAMS      = [];
    const SCHEMAS     = [

    ];
    //前置方法
    protected $beforeActionList = [
        'verifyToken','authorize'
    ];

    public function run()
    {
        $name = $this->request->getQueryParam('name');
        $startDate = $this->request->getQueryParam('start_date', date('Y-m-d'));
        $endDate = $this->request->getQueryParam('end_date', $startDate);

        $user = \Model\User::where('name', $name)->first();
        $lottery = \Model\Lottery::where('pid', '!=', 0)->get()->toArray();
        $lottery = DB::resultToArray($lottery);
        $lottery = array_column($lottery, 'name', 'id');
        $data = [];
        if (!empty($user)) {
            $rebet = new \Logic\Lottery\Rebet($this->ci);
            $num = (strtotime($endDate) - strtotime($startDate)) / 86400;
            $num = $num != 0 ? $num : 1;
            for ($i = 0; $i < $num; $i++) {
                $date = date('Y-m-d', strtotime($endDate) - 86400 * $i);
                $temp = $rebet->runByUserLevelRebet($date, $runMode = 'test', $user['id']);
                foreach($temp ?? [] as $k => $v) {
                    $betNumberStrs = explode(';', $v['betNumberStr']);
                    foreach ($betNumberStrs ?? [] as $k2 => $v2) {
                        $temp2 = explode(',', $v2);
                        $temp2[0] = $lottery[$temp2[0]];
                        $betNumberStrs[$k2] = $temp2;
                    }
                    $v['betNumberStrs'] = $betNumberStrs;
                    $v['isPass'] = $v['title'] == '【返水不通过】' ? 1 : 0;
                    $temp[$k] = $v;
                }
                $data[] = ['date' => $date, 'data' => $temp];
            } 
        }

        return $data;
    }
};