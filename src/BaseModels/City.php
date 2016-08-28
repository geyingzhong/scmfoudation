<?php

namespace geyingzhong\BaseModels;

use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    protected $connection = 'mysqlBase';
    protected $table = 'city';
    public $timestamps = false;
}
