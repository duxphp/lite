<?php
declare(strict_types=1);

namespace Dux\Database;

use Dux\App;
use Exception;

class Model extends \Illuminate\Database\Eloquent\Model
{
    protected $fillable = [];
    protected $guarded = [];
    protected array $schema = [];
    public function getSchema(): array {
        return $this->schema;
    }

}