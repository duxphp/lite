<?php
declare(strict_types=1);

namespace Dux\Database;

trait TreeTrait {
    public static function tree(): \BlueM\Tree {
        $data = self::query()->get();
        return new \BlueM\Tree($data->toArray(), ['rootId' => 0, 'id' => self::$treeKey ?: 'id', 'parent' => self::$treeParentKey ?: 'parent_id']);
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
        return $this->hasMany(get_class($this), $this->treeParentKey ?: 'parent_id', $this->treeKey ?: 'id')->select($fields)->with(['children']);
    }


    private ?\BlueM\Tree $treeData = null;

    /**
     * 转换级别数据
     * @param $id
     * @return array
     */
    public function coverLevel($id): array {
        if (!$this->treeData) {
            $tree = self::tree();
            $this->treeData = $tree;
        } else {
            $tree = $this->treeData;
        }
        $node = $tree->getNodeById($id);
        $ancestorsPlusSelf = $node->getAncestorsAndSelf();
        return array_reverse(array_column($ancestorsPlusSelf, $this->treeKey ?: "id"));
    }
}