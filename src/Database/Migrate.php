<?php

namespace Dux\Database;

use Dux\App;
use Illuminate\Database\Capsule\Manager;

class Migrate {
    public array $migrate = [];

    private Manager $db;

    public function __construct(Manager $db) {
        $this->db = $db;
    }

    public function register(string $model): void {
        $this->migrate[] = $model;
    }

    public function migrate(): void {
        $pre = $this->db->connection()->getTablePrefix();
        foreach ($this->migrate as $model) {
            $modelObject = new $model;
            $name = $modelObject->getTable();
            $tableName = $pre . $name;
            $hasTable = $this->db->schema()->hasTable($name);
            $schema = $modelObject->getSchema();
            $rules = $this->migrateRule($schema);
            if (!$hasTable) {
                // 创建表
                $sqls = [];
                foreach ($rules as $field => $rule) {
                    $sqls[] = implode(" ", [$field, ...$this->generateCol($rule, false)]);
                }
                $fields = implode(",", $sqls);
                $this->db->connection()->statement("create table $tableName ($fields)");
            } else {
                $lastField = "";
                foreach ($rules as $field => $rule) {
                    $hasColumn = $this->db->schema()->hasColumn($name, $field);
                    $sql = [$field, ...$this->generateCol($rule, $hasColumn)];
                    if ($lastField) {
                        $sql[] = "after $lastField";
                    }
                    $string = implode(" ", $sql);
                    if ($hasColumn) {
                        //修改字段
                        $this->db->connection()->statement("alter table $tableName modify column $string");
                    } else {
                        //新增字段
                        $this->db->connection()->statement("alter table $tableName add column $string");
                    }
                    $lastField = $field;
                }
            }
        }
    }

    private function generateCol(array $rule, bool $update): array {
        $i = 0;
        foreach ($rule as $method => $params) {
            switch ($method) {
                case "primarykey":
                    if (!$update) {
                        $row[] = "unsigned AUTO_INCREMENT PRIMARY KEY NOT NULL ";
                    }
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