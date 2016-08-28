<?php

namespace geyingzhong\BaseModels;

use Illuminate\Database\Eloquent\Model;

class RolePermission extends Model
{
    protected $connection = 'mysqlBase';
    protected $table = 'role_permission';
    public $timestamps = false;
}
