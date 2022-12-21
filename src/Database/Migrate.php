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
            $dataname = $this->db->getConfig("database");
            $data = $this->db->query("SELECT COUNT(*) as num FROM information_schema.TABLES WHERE table_schema = '$dataname' AND table_name = '$name'")->fetch();
            $hasTable = (bool)$data["num"];
            $schema = $modelObject->getSchema();
            $rules = $this->migrateRule($schema);
            if (!$hasTable) {
                // 创建表
                $fieldArray = [];
                foreach ($rules as $field => $rule) {
                    $row = [$field];
                    $i = 0;
                    foreach ($rule as $method => $params) {
                        switch ($method) {
                            case "primarykey":
                                $row[] = "unsigned AUTO_INCREMENT PRIMARY KEY NOT NULL ";
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
                    $fieldArray[] = implode(" ", $row);
                }
                $fieldSql = implode(",", $fieldArray);
                $this->db->query("create table $name ($fieldSql)");
            } else {
                $result = $this->db->query("select count(*) as num
                        from information_schema.columns
                        where table_schema = '$dataname'
                        and table_name = '$name'
                        and column_name = '$field'")->fetch();
                $hasColumn = (bool)$result["num"];

                // 更新字段
                $lastField = "";
                foreach ($rules as $field => $rule) {
                    $row = [$field];
                    $i = 0;

                    if ($lastField) {
                        $row[] = "after $lastField";
                    }
                    $sql = implode(" ", $row);


                    if ($hasColumn) {
                        //修改字段
                        $this->db->query("alter table $name modify column $sql");
                    } else {
                        //新增字段
                        $this->db->query("alter table $name add column $sql");


                    }
                    $lastField = $field;
                }


            }

        }
    }

    private function generateCol(array $rule, bool $update = false): array {
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