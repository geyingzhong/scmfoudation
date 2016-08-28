<?php

namespace geyingzhong\BaseModels;

use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    protected $connection = 'mysqlBase';
    protected $table = 'module';
    public $timestamps = false;

    static $levelOne =1;
    static $levelTwo =2;
    static $levelThree =3;
}
