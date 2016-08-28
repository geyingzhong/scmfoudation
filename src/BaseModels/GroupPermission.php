<?php

namespace geyingzhong\BaseModels;

use Illuminate\Database\Eloquent\Model;

class GroupPermission extends Model
{
    protected $connection = 'mysqlBase';
    protected $table = 'group_permission';
    public $timestamps = false;
}
