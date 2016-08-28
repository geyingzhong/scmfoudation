<?php

namespace geyingzhong\BaseModels;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    protected $connection = 'mysqlBase';
    protected $table = 'permission';
    public $timestamps = false;
}
