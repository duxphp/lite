<?php
declare(strict_types=1);

namespace Dux\Database;

use Dux\App;
use Exception;
use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Blueprint;

class Model extends \Illuminate\Database\Eloquent\Model
{
    protected $fillable = [];
    protected $guarded = [];
    protected array $schema = [];

    public function getSchema(): array
    {
        return $this->schema;
    }

    public function migration(Blueprint $table)
    {
    }

    public function seed(Connection $db)
    {
    }

}