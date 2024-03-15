<?php

namespace Model;

use Illuminate\Database\Eloquent\Model;

class IpLimit extends Model {

    protected $table = 'ip_limit';

    public function getById($id) {
        return $this->find($id);
    }

    /**
     * 获取ip白名单列表
     *
     * @param array $condition
     * @param int $page
     * @param int $page_size
     *
     * @return array
     */
    public function getList(array $condition = [], int $page = 1, int $page_size = 20) {
        return $this->where($condition)
                    ->forPage($page, $page_size)
                    ->orderby('created', 'desc')
                    ->get()
                    ->toArray();
    }

    public function total(array $condition = []) {
        return $this->where($condition)
                    ->count();
    }
}