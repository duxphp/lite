<?php

namespace Dux\Database;

use Dux\App;
use Illuminate\Database\Schema\Blueprint;
use Doctrine\DBAL\Schema\Comparator;

class Migrate {
    public array $migrate = [];

    public function register(string $model): void {
        $this->migrate[] = $model;
    }

    public function migrate(): void {

        foreach ($this->migrate as $model) {
            $this->migrateTable(new $model);
        }
    }

    private function migrateTable(Model $model): void {
        $pre = App::db()->connection()->getTablePrefix();
        $modelTable = $model->getTable();
        $tempTable = 'table_' . $modelTable;
        $tableExists = App::db()->schema()->hasTable($modelTable);
        App::db()->schema()->dropIfExists($tempTable);
        App::db()->schema()->create($tableExists ? $tempTable : $modelTable, function (Blueprint $table) use ($model) {
            $model->migration($table);
        });
        if (!$tableExists) {
            return;
        }
        $manager = $model->getConnection()->getDoctrineSchemaManager();
        $modelTableDetails = $manager->introspectTable($pre.$modelTable);
        $tempTableDetails = $manager->introspectTable($pre.$tempTable);
        foreach ($tempTableDetails->getIndexes() as $indexName => $indexInfo) {
            $correctIndexName = str_replace('table_','',$indexName);
            $tempTableDetails->renameIndex($indexName, $correctIndexName);
        }
        $diff = (new Comparator)->compareTables($modelTableDetails, $tempTableDetails);
        if ($diff) {
            $manager->alterTable($diff);
        }
        App::db()->schema()->drop($tempTable);
    }


}