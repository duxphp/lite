<?php
declare(strict_types=1);

namespace Dux\Database;

use Dux\App;
use Exception;

class Model extends \Illuminate\Database\Eloquent\Model
{
    const CREATED_AT = 'created_time';
    const UPDATED_AT = 'updated_time';
    protected $fillable = [];
    protected $guarded = [];

}