<?php

namespace geyingzhong\BaseModels;
use Illuminate\Database\Eloquent\Model;

class Captcha extends Model{

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $connection = 'mysqlBase';
    protected $table = 'captcha';
    public $timestamps = false;

    static public $tagReg =1;  //注册
    static public $tagCreateAct =2; //找回密码
    static public $tagJoinAct =3; //其他


}
