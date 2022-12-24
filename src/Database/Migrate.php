<?php

namespace Dux\Database;

use Dux\App;
use Illuminate\Database\Capsule\Manager;

class Migrate {
    public array $migrate = [];

    public function register(string $model): void {
        $this->migrate[] = $model;
    }

    public function migrate(): void {
        $pre = App::db()->connection()->getTablePrefix();
        foreach ($this->migrate as $model) {
            $modelObject = new $model;
            $name = $modelObject->getTable();
            $tableName = $pre . $name;
            $hasTable = App::db()->schema()->hasTable($name);
            $schema = $modelObject->getSchema();
            $rules = $this->migrateRule($schema);
            if (!$hasTable) {
                // 创建表
                $sql = [];
                $keys = [];
                foreach ($rules as $field => $rule) {
                    $index = [];
                    $sql[] = implode(" ", [$field, ...$this->generateCol($rule, false, $index)]);
                    if ($index) {
                        $keys[] = [$field, $index];
                    }
                }
                $fields = implode(",", $sql);
                App::db()->connection()->statement("create table $tableName ($fields)");
                foreach ($keys as $key) {
                    [$field, [$method, $params]] = $key;
                    $this->addIndex($tableName, $field, $method, $params ?: []);
                }
            } else {
                $lastField = "";
                foreach ($rules as $field => $rule) {
                    $index = [];
                    $hasColumn = App::db()->schema()->hasColumn($name, $field);
                    $sql = [$field, ...$this->generateCol($rule, $hasColumn, $index)];
                    if ($lastField) {
                        $sql[] = "after $lastField";
                    }
                    $string = implode(" ", $sql);
                    if ($hasColumn) {
                        //修改字段
                        App::db()->connection()->statement("alter table $tableName modify column $string");
                    } else {
                        //新增字段
                        App::db()->connection()->statement("alter table $tableName add column $string");
                        // 新增索引
                        [$method, $params] = $index;
                        $this->addIndex($tableName, $field, $method, $params);

                    }
                    $lastField = $field;


                }
            }
        }
    }

    private function addIndex(string $table, string $field, string $method, array $columns = []) {
        $columns = $columns ? $columns : [$field];
        switch ($method) {
            case "index":
                $columnName = implode(',', $columns);
                $indexName = implode('_', $columns);
                App::db()->connection()->statement("ALTER TABLE $table ADD INDEX $indexName($columnName)");
                break;
            case "unique":
                App::db()->connection()->statement("ALTER TABLE $table ADD UNIQUE ($field)");
                break;
            case "fulltext":
                App::db()->connection()->statement("ALTER TABLE $table ADD FULLTEXT ($field)");
                break;
        }
    }

    private function generateCol(array $rule, bool $update, array &$index): array {
        $i = 0;
        $row = [];
        foreach ($rule as $method => $params) {
            switch ($method) {
                case "primary":
                    $row[] = "unsigned AUTO_INCREMENT " . ($update ? "" : "PRIMARY KEY") . " NOT NULL ";
                    break;
                case "comment":
                case "default":
                    $row[] = "$method '$params'";
                    break;
                case "null":
                    $row[] = "DEFAULT NULL";
                    break;
                case "notnull":
                    $row[] = "NOT NULL";
                    break;
                case "unsigned":
                    $row[] = "unsigned";
                    break;
                case "index":
                case "unique":
                case "fulltext":
                    $index = [$method, $params];
                    break;
                default:
                    if ($i == 0) {
                        if ($params) {
                            $method = "$method($params)";
                        }
                    }
                    $row[] = $method;
            }
            $i++;
        }
        return $row;
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
                $ruleMaps[$method] = $params;
            }
            $map[$field] = $ruleMaps;
        }
        return $map;
    }


}