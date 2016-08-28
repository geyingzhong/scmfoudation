<?php

namespace geyingzhong\BaseModels;

use Illuminate\Database\Eloquent\Model;

class GroupModule extends Model
{
    protected $connection = 'mysqlBase';
    protected $table = 'group_module';
    public $timestamps = false;
}
