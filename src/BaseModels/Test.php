<?php

namespace geyingzhong\BaseModels;

use Illuminate\Database\Eloquent\Model;

class Test extends Model
{
    protected $connection = 'mysqlBase';
    protected $table = 'test';
    public $timestamps = false;
}
