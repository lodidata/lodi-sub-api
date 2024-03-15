<?php
/**
 * Created by PhpStorm.
 * User: ben
 * Date: 2019/3/13
 * Time: 18:47
 */
use Utils\Www\Action;
use Respect\Validation\Validator as V;
return new class extends Action {
    const TOKEN = true;
    const TITLE = "钱包-交易记录";
    const DESCRIPTION = "\r\n 返回attributes[number =>'第几页', 'size' => '记录条数'， total=>'总记录数']";
    const TAGS = "钱包";
    const QUERY = [
        "date_start"    => "date(required)#开始日期 默认为当前时间",
        "date_end"      => "date(required)#结束日期 默认为当前时间",
        "deal_type"     => "int()#交易类型",
        'page'          => "int(,1) #第几页 默认为第1页",
        "page_size"     => "int(,10) #分页显示记录数 默认10条记录"
    ];
    const SCHEMAS = [
    ];

    public function run() {

        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }
        $userId = $this->auth->getUserId();
        $page = $this->request->getParam('page',1);
        $page_size = $this->request->getParam('page_size',10);
        $date_start = $this->request->getParam('date_start',date('Y-m-d'));
        $deal_type = $this->request->getParam('deal_type');
        $deal_type = $deal_type ? [$deal_type] : [305,306];
        $date_end = $this->request->getParam('date_end',date('Y-m-d'));
        $date_end .= ' 23:59:59';
        $query = DB::table('funds_deal_log')
            ->where('user_id',$userId)
            ->whereIn('deal_type',$deal_type)
            ->where('created','>=',$date_start)
            ->where('created','<=',$date_end)
            ->selectRaw('deal_money,deal_type,memo,created');

        $total = $query->count();

        $data = $query->forPage($page,$page_size)->orderBy('created','desc')->get()->toArray();
        $attributes['total'] = $total;
        $attributes['size'] = $page_size;
        $attributes['number'] = $page;

        return $this->lang->set(0, [], $data, $attributes);
    }
};
