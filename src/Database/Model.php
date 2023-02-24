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

    public function migration(Blueprint $table)
    {
    }

    public function migrationAfter(Connection $db)
    {
    }

    public function seed(Connection $db)
    {
    }

}