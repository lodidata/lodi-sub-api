<?php
use Logic\Admin\BaseController;
return new class() extends BaseController
{
    const TITLE = '渠道管理-下载页配置';
    const DESCRIPTION = '获取下载配置列表';
    const QUERY = [
        'page' => 'int(required) #当前页',
        'page_size' => 'int(required) #每页数量',
        'date_start' => 'string() #开始日期',
        'date_end' => 'string() #结束日期',
        'channel_no_name' => 'string() #渠道号/渠道名称',
        'product_name' => 'string() #产品名称',
    ];
    const PARAMS = [];
    const SCHEMAS = [];

    protected $beforeActionList = [
        'verifyToken',
        'authorize'
    ];

    public function run() {
        $page = $this->request->getParam('page',1);
        $page_size = $this->request->getParam('page_size',20);
        $date_start = $this->request->getParam('date_start');
        $date_end = $this->request->getParam('date_end');
        $channel_no = $this->request->getParam('channel_no','');
        //$product_name = $this->request->getParam('product_name','');

        $sql = DB::table('channel_download')->where('is_delete',0)->orderBy('id','desc');
        if (!empty($date_start) && !empty($date_end)) {
            $fmt_date_start = date("Y-m-d 00:00:00",strtotime($date_start));
            $fmt_date_end = date("Y-m-d 23:59:00",strtotime($date_end));
            $sql->whereRaw('update_time>=? and update_time<=?', [$fmt_date_start,$fmt_date_end]);
        }
        if (!empty($channel_no)) {
            $sql->where('channel_no',$channel_no);
        }
        //if (!empty($product_name)) {
        //    $sql->where('product_name',$product_name);
        //}
        $data = $sql->paginate($page_size,['*'],'page',$page)->toJson();
        $data = json_decode($data, true);
        if(!empty($data)){
            foreach($data['data'] as &$val){
                $val['icon_url'] = showImageUrl($val['icon_url']);
                $val['ios'] = showImageUrl($val['ios']);
                $val['android'] = showImageUrl($val['android']);
            }
//            unset($val);
        }

        $attr = [
            'total' => $data['total'] ?? 0,
            'size' => $page_size,
            'number' => $data['last_page'] ?? 0,
            'current_page' => $data['current_page'] ?? 0,    //当前页数
            'last_page' => $data['last_page'] ?? 0,   //最后一页数
        ];

        return $this->lang->set(0, [], $data['data'], $attr);
    }
};