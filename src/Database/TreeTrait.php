<?php

namespace Dux\Database;

trait TreeTrait {
    public static function tree(): \BlueM\Tree {
        $data = self::query()->get();
        return new \BlueM\Tree($data->toArray(), ['rootId' => 0, 'id' => 'id', 'parent' => 'parent_id']);
    }

    public static function childrenAll(int $parentId = 0) {
        $model = new self();
        $fields = "*";
        if (isset($model->treeFields)) {
            $fields = $model->treeFields;
        }
        return $model->where("parent_id", 0)->with(['children'])->select($fields)->get();
    }

    public function children() {
        $fields = "*";
        if (isset($this->treeFields)) {
            $fields = $this->treeFields;
        }
        return $this->hasMany(get_class($this), 'parent_id' ,'id')->select($fields)->with(['children']);
    }
}