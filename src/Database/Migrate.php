<?php
declare(strict_types=1);

namespace Dux\Database;

use Dux\App;
use Dux\Database\Attribute\AutoMigrate;
use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Blueprint;
use Doctrine\DBAL\Schema\Comparator;
use Symfony\Component\Console\Output\OutputInterface;

class Migrate
{
    public array $migrate = [];

    public function register(string ...$model): void
    {
        $this->migrate = [...$this->migrate, ...$model];
    }

    public function migrate(OutputInterface $output): void
    {
        $seeds = [];
        $connect = App::db()->connection();
        foreach ($this->migrate as $model) {
            if (!method_exists($model, 'migration')) {
                continue;
            }
            $this->migrateTable($connect, new $model, $seeds);
            $output->writeln("<info>sync model $model</info>");
        }

        foreach ($seeds as $seed) {
            $seed->seed($connect);
            $name = $seed::class;
            $output->writeln("<info>sync send $name</info>");
        }

    }

    private function migrateTable(Connection $connect, Model $model, &$seed): void
    {
        $pre = $connect->getTablePrefix();
        $modelTable = $model->getTable();
        $tempTable = 'table_' . $modelTable;
        $tableExists = App::db()->schema()->hasTable($modelTable);
        App::db()->schema()->dropIfExists($tempTable);
        App::db()->schema()->create($tableExists ? $tempTable : $modelTable, function (Blueprint $table) use ($model) {
            $model->migration($table);
        });
        if (!$tableExists) {
            if (method_exists($model, 'seed')) {
                $seed[] = $model;
            }
            return;
        }
        // 更新表字段
        $manager = $model->getConnection()->getDoctrineSchemaManager();
        $modelTableDetails = $manager->introspectTable($pre . $modelTable);
        $tempTableDetails = $manager->introspectTable($pre . $tempTable);
        foreach ($tempTableDetails->getIndexes() as $indexName => $indexInfo) {
            $correctIndexName = str_replace('table_', '', $indexName);
            $tempTableDetails->renameIndex($indexName, $correctIndexName);
        }
        $diff = (new Comparator)->compareTables($modelTableDetails, $tempTableDetails);
        if ($diff) {
            $manager->alterTable($diff);
        }
        App::db()->schema()->drop($tempTable);
    }


    // 注册迁移模型
    public function registerAttribute(): void
    {
        $attributes = (array)App::di()->get("attributes");
        foreach ($attributes as $attribute => $list) {
            if (
                $attribute !== AutoMigrate::class
            ) {
                continue;
            }
            foreach ($list as $vo) {
                $class = $vo["class"];
                $this->register($class);
            }
        }
    }

}