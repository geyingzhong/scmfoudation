<?php

namespace geyingzhong\BaseModels;

use Illuminate\Database\Eloquent\Model;

class UserExtension extends Model
{
    protected $connection = 'mysqlBase';
    protected $table = 'user_extension';
    public $timestamps = false;
}
