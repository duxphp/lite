<?php

namespace Dux\Database;

trait TreeTrait {
    public function tree(array $fields = []) {
        $data = $this->select($fields)->get();
        return new \BlueM\Tree($data->toArray(), ['rootId' => 0, 'id' => 'id', 'parent' => 'parent_id']);
    }

    public function children() {
        return $this->hasMany(get_class($this), 'parent_id' ,'id')->with(['children']);
    }
}