<?php

namespace geyingzhong\BaseModels;

use Illuminate\Database\Eloquent\Model;

class UserToken extends Model
{
    protected $connection = 'mysqlBase';
    protected $table = 'user_token';
    public $timestamps = false;
    public static $expirePeriod = 10800;
}
