<?php

namespace geyingzhong\BaseModels;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $connection = 'mysqlBase';
    protected $table = 'role';
    public $timestamps = false;
}
