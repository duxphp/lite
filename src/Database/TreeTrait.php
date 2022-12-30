<?php

namespace Dux\Database;

trait TreeTrait {
    public static function tree(array $fields = []) {
        $data = self::query()->get();
        return new \BlueM\Tree($data->toArray(), ['rootId' => 0, 'id' => 'id', 'parent' => 'parent_id', ...$fields]);
    }

    public function children() {
        return $this->hasMany(get_class($this), 'parent_id' ,'id')->with(['children']);
    }
}