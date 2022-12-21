<?php
declare(strict_types=1);

namespace Dux\Database;

use Dux\App;
use Exception;
use PDO;
use Medoo\Medoo;
use PDOStatement;

/**
 * 基类 基于 Medoo 代理封装
 * @package MedooModel
 * @link http://medoo.in
 * @link https://github.com/awheel/medoo-model
 *
 * @method select($join, $columns = null, $where = null)
 * @method insert(array $values, string $primaryKey = null)
 * @method update($data, $where = null)
 * @method delete($where)
 * @method replace(array $columns, $where = null)
 *
 * @method get(string $table, $join = null, $columns = null, $where = null)
 * @method has(string $table, $join, $where = null)
 * @method rand(string $table, $join = null, $columns = null, $where = null)
 * @method count(string $table, $join = null, $column = null, $where = null)
 * @method max(string $table, $join, $column = null, $where = null)
 * @method min(string $table, $join, $column = null, $where = null)
 * @method avg(string $table, $join, $column = null, $where = null)
 * @method sum(string $table, $join, $column = null, $where = null)
 *
 */
abstract class Model
{

    protected array $schema = [];

    /**
     * 表名
     * @var string
     */
    public string $table;

    /**
     * 主键
     * @var string
     */
    public string $primary = 'id';

    /**
     * 自动维护时间
     * @var string|array|bool
     */
    public $timestamps = false;

    /**
     * 配置类型
     * @var string
     */
    protected string $type = '';

    /**
     * 是否写入
     * @var bool
     */
    private bool $write;

    /**
     * 配置
     * @var array
     */
    private array $config = [];


    public function __construct() {
        if (!$this->config) {
            if (!$this->type) {
                $this->type = App::config("database")->get("db.type");
            }
            $this->config = App::config("database")->get("db.drivers.". $this->type, []);
        }
    }

    /**
     * 获取模型结构
     * @return array
     */
    public function getSchema(): array {
        return $this->schema;
    }


    /**
     * 获取 sql 执行错误信息
     *
     * @return array
     */
    public function error(): ?array {
        return $this->connection()->errorInfo;
    }

    /**
     * 判断是否有错误发生
     * @return bool
     */
    public function hasError(): bool {
        return $this->pdo()->errorCode() > 0;
    }

    /**
     * 获取数据库连接实例
     *
     * @return PDO
     */
    public function pdo(): PDO {
        return $this->connection()->pdo;
    }


    /**
     * 根据主键查询
     * @param $id
     * @param string $columns
     * @return false|mixed|null
     */
    public function first($id, string $columns = '*') {
        if (!$id) return null;
        return $this->connection()->get($this->table, $columns, [$this->primary => $id]);
    }

    /**
     * 根据主键删除
     * @param $id
     * @return false|PDOStatement|null
     */
    public function destroy($id)
    {
        if (!$id) return false;
        return $this->connection()->delete($this->table, [$this->primary => $id]);
    }

    /**
     * 获取日志
     * @return string[]
     */
    public function log(): array
    {
        return $this->connection()->log();
    }

    /**
     * 获取最后一条 sql
     * @return string|null
     */
    public function lastSql(): ?string
    {
        return $this->connection()->last();
    }

    /**
     * 获取插入id
     * @return string
     */
    public function id(): ?string
    {
        return $this->connection()->id();
    }

    /**
     * 自定义查询
     * @param $query
     * @return PDOStatement|null
     */
    public function query($query)
    {
        return $this->connection()->query($query);
    }

    /**
     * 转义字符串
     * @param $string
     * @return string
     */
    public function quote($string): string
    {
        return $this->connection()->quote($string);
    }

    /**
     * 获取数据库信息
     * @return array
     */
    public function dbInfo(): array {
        return $this->connection()->info();
    }

    /**
     * 回调事务
     * @param $callback
     * @return void
     * @throws Exception
     */
    public function action($callback): void
    {
        $this->connection()->action($callback);
    }


    /**
     * 开启事务
     * @return void
     */
    public function beginTransaction() {
        $this->pdo()->beginTransaction();
    }

    /**
     * 回滚事务
     * @return void
     */
    public function rollBack() {
        $this->pdo()->rollBack();
    }

    /**
     * 提交事务
     * @return void
     */
    public function commit() {
        $this->pdo()->commit();
    }


    /**
     * debug
     * @return $this
     */
    public function debug(): MedooModel {
        $this->connection()->debug();
        return $this;
    }

    /**
     * 获取表名
     * @return string
     */
    public function getTable(): string {
        return $this->config["prefix"] .$this->table;
    }

    /**
     * 设置表名
     * @param $table
     */
    public function setTable($table)
    {
        $this->table = ltrim($table, $this->config["prefix"]);
    }

    /**
     * medoo代理
     * @param $method
     * @param $arguments
     * @return mixed
     * @throws Exception
     */
    public function __call($method, $arguments)
    {
        $this->write = in_array($method, ['update', 'insert', 'replace']);
        $arguments = [$this->table, ...$arguments];
        $this->appendTimestamps($method, $arguments[1]);
        $result = call_user_func_array([$this->connection(), $method], $arguments);
        if ($this->connection()->error) {
            throw new \RuntimeException($this->connection()->error);
        }
        return $result;
    }

    /**
     * 自动更新日期
     * @param $method
     * @param $data
     * @return void
     */
    protected function appendTimestamps($method, &$data): void {
        if (!$this->write || !$this->timestamps) {
            return;
        }
        $timestamp = time();
        $times = [];
        if (is_bool($this->timestamps)) {
            $times = ['updated_time' => $timestamp];
            $method == 'insert' && $times['created_time'] = $timestamp;
        }
        elseif (is_array($this->timestamps)) {
            foreach ($this->timestamps as $item) {
                $times[$item] = $timestamp;
            }
        }
        elseif (is_string($this->timestamps)) {
            $times[$this->timestamps] = $timestamp;
        }
        if ($times) {
            $multi = $method == 'insert' && is_array($data) && is_numeric(array_keys($data)[0]);
            if ($multi) {
                foreach ($data as &$item) {
                    $item = array_merge($item, $times);
                }
            }
            else {
                $data = array_merge($data, $times);
            }
        }
    }

    /**
     * 获取数据库实例
     * @return Medoo
     */
    protected function connection(): Medoo
    {
        return App::db($this->type);
    }
}