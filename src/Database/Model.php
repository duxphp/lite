<?php
declare(strict_types=1);

namespace Dux\Database;

use Dux\App;
use Exception;

class Model extends \Illuminate\Database\Eloquent\Model
{
    const CREATED_AT = 'create_time';
    const UPDATED_AT = 'update_time';
    const DELETED_AT = 'delete_time';
    protected $dateFormat = 'U';
    protected $fillable = [];
    protected $guarded = [];
    protected array $schema = [];
    public function getSchema(): array {
        return $this->schema;
    }

}