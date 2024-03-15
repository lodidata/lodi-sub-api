<?php
use Utils\Www\Action;
return new class extends Action
{
    const TOKEN = true;
    const TITLE = "意见与反馈";
    const DESCRIPTION = "";
    const TAGS = "意见与反馈";
    const QUERY = [
   ];
    const SCHEMAS = [
       [
       ]
   ];

    public function run() {
        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }
        if($this->auth->getTrialStatus()) {
            return $this->lang->set(0, [], [], ['number' => 1, 'size' => 10, 'total' => 0]);
        }
        $user_id = $this->auth->getUserId();
        $page     = (int) $this->request->getQueryParam('page', 1);
        $pageSize = (int) $this->request->getQueryParam('page_size', 10);

        $data = DB::table('user_feedback')->select([
                    'id',
                    'status',
                    'type',
                    'question',
                    'img',
                    'reply',
                    'created',
                ])
                ->where('user_id', $user_id)
                ->orderBy('created', 'DESC')
                ->paginate($pageSize, ['*'], 'page', $page);
        $result = $data->toArray()['data'];
        if(!empty($result)){
            foreach($result as &$val){
                $val->img = showImageUrl($val->img, true);
            }
            unset($val);
        }

        return $this->lang->set(0, [], $result, ['number' => $page, 'size' => $pageSize, 'total' => $data->total()]);
    }
};
