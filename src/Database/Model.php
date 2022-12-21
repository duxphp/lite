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

    public array $struct = [
        'id' => 'bigIncrements',
        'name' => 'char:100',
        'price' => 'float:8,2',
        'time' => 'time|after:name|comment:dsdsd',
    ];

}