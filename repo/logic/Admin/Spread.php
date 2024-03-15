<?php

namespace Logic\Admin;

use Model\Admin\Spread as SpreadModel;

class Spread extends \Logic\Logic {

    public function getList($data = []) {
        $query = SpreadModel::query();

        $attributes = [
            'total'        => $query->count(),
            'num'          => $data['page'],
            'size'         => $data['page_size'],
            'current_page' => $data['page'],
        ];

        $result = $query->orderBy('created', 'desc')
                        ->forPage($data['page'], $data['page_size'])
                        ->get()
                        ->toArray();

        return $this->lang->set(0, [], $result, $attributes);
    }

    public function getOne($id) {
        $query = SpreadModel::query();

        $query->where('id', $id);

        $result = $query->get()
                        ->toArray();

        return $this->lang->set(0, [], $result, [
            'total' => count($result),
        ]);
    }

    public function create($data) {
        $spread = new SpreadModel();

        $data = [
            'name'    => $data['name'],
            'status'  => $data['status'],
            'picture' => $data['picture'],
            'sort'    => $data['sort'],
        ];

        foreach ($data as $key => $value) {
            $spread->{$key} = $value;
        }

        $result = $spread->save();

        if (!$result) {
            return $this->lang->set(-2);
        }

        return $this->lang->set(0);
    }

    public function remove($id) {
        $result = SpreadModel::getOne($id);

        if (!$result) {
            return $this->lang->set(10015);
        }

        $result = SpreadModel::where('id', $id)
                             ->delete();

        if (!$result) {
            return $this->lang->set(-2);
        }

        return $this->lang->set(0);
    }

    public function update($id, $data) {
        $result = SpreadModel::getOne($id);

        if (!$result) {
            return $this->lang->set(10015);
        }

        $fields = ['name', 'status', 'sort', 'picture'];

        $update = [];
        foreach ($fields as $field) {
            if (!isset($data[$field])) {
                continue;
            }

            $update[$field] = $data[$field];
        }

        $result = SpreadModel::where('id', $id)
                             ->update($update);

        if (!$result) {
            return $this->lang->set(-2);
        }

        return $this->lang->set(0, [], $result, '');
    }
}
