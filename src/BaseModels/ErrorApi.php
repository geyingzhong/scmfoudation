<?php

namespace geyingzhong\BaseModels;
use Illuminate\Database\Eloquent\Model;

class ErrorApi extends Model{

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $connection = 'mysqlBase';
    protected $table = 'error_api';
    public $timestamps = false;


}
