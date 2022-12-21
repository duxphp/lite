<?php

namespace Dux\Database;

use Dux\App;

class Migrate {
    public array $migrate = [];

    private MedooExtend $db;

    public function __construct(MedooExtend $db) {
        $this->db = $db;
    }

    public function register(string $model): void {
        $this->migrate[] = $model;
    }

    public function migrate(): void {
        foreach ($this->migrate as $model) {
            $modelObject = new $model;
            $name = $modelObject->getTable();
            //$pre = $this->db->getConfig("prefix");

            $data = $this->db->query("SELECT COUNT(*) FROM information_schema.TABLES WHERE table_name = <$name>")->fetchAll();

            $struct = $modelObject->struct;
            $rules = $this->migrateRule($struct);
            if (!$this->capsule->schema()->hasTable($name)) {
                // 创建表
                $this->capsule->schema()->create($name, function (Blueprint $table) use ($rules) {
                    foreach ($rules as $field => $rule) {
                        $this->createField($table, $field, $rule);
                    }
                });
            } else {
                // 更新表
                $this->capsule->schema()->table($name, function (Blueprint $table) use ($pre, $name, $rules) {
                    $table->string('name', 50)->change();
                    foreach ($rules as $field => $rule) {
                        // 创建字段
                        if (!$this->capsule->schema()->hasColumn($name, $field)) {
                            $this->createField($table, $field, $rule);
                            continue;
                        }
                        // 跳过无法更新字段
                        if (!in_array($this->capsule->schema()->getColumnType($name, $field), $this->updateFields)) {
                            continue;
                        }
                        // 更新字段
                        $this->createField($table, $field, $rule)->change();
                    }
                });

            }

        }


    }

    private function createField(Blueprint $table, string $field, array $rule): ColumnDefinition {
        // 创建字段
        $type = array_key_first($rule);
        $fieldObject = $table->$type($field, ...$rule[$type]);
        // 设置字段
        $i = 0;
        foreach ($rule as $method => $params) {
            if ($i == 0) {
                continue;
            }
            if ($params) {
                $fieldObject = call_user_func($fieldObject, $method, ...$params);
            } else {
                $fieldObject = call_user_func($fieldObject, $method);
            }

            $i++;
        }
        return $fieldObject;
    }

    private function migrateRule(array $struct): array {
        $map = [];
        foreach ($struct as $field => $rule) {
            $ruleMaps = [];
            $rules = explode('|', $rule);
            foreach ($rules as $vo) {
                $tmp = explode(':', $vo, 2);
                $method = $tmp[0];
                $params = isset($tmp[1]) ? $tmp[1] : "";
                $ruleMaps[$method] = explode(',', $params);
            }
            $map[$field] = $ruleMaps;
        }
        return $map;
    }


}