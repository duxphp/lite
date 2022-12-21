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
            $data = $this->db->query("SELECT COUNT(*) as num FROM information_schema.TABLES WHERE table_name = '$tableName'")->fetch();
            $hasTable = (bool) $data["num"];
            $struct = $modelObject->struct;
            $rules = $this->migrateRule($struct);
            if (!$hasTable) {
                // 创建表
                $fieldArray = [];
                $afterFiled = "";
                $i = 0;
                foreach ($rules as $field => $rule) {
                    $row = [];
                    foreach ($rule as $method => $params) {
                        switch ($method) {
                            case "primarykey":
                                $row[] = "$method unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY";
                            case "comment":
                            case "default":
                            $row[] = "$method '$params'";
                            case "null":
                                $row[] = "DEFAULT NULL";
                            case "notnull":
                                $row[] = "NOT NULL";
                            default:
                                if ($i == 0 && $params) {
                                    $method = "$method($params)";
                                }
                                $row[] = $method;
                        }
                        if ($afterFiled) {
                            $row[] = "after " . $afterFiled;
                        }
                    }
                    $afterFiled = $field;
                    $fieldArray[] = implode(" ", $row);
                    $i++;
                }
                $fieldSql = implode(",", $fieldArray);

                $this->db->query("create table $tableName ($fieldSql)");
            } else {
                // 更新表


            }

        }


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