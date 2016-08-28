<?php

namespace geyingzhong\BaseModels;

use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    protected $connection = 'mysqlBase';
    protected $table = 'group';
    public $timestamps = false;
}
