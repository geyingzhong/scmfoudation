<?php
/**
 * Created by PhpStorm.
 * User: bestry
 * Date: 8/27/16
 * Time: 11:41 PM
 */
namespace geyingzhong\ScmFoundation;



use geyingzhong\BaseModels\User;
use geyingzhong\Utils\Helpers;

class BaseData
{

    function users()
    {
        $users = User::where('deleted', 0)->get()->toArray();
        return Helpers::insideReturn(true, '获取数据成功', ['users' => $users]);
    }


    function repairLevels()
    {
        return Helpers::insideReturn(true, '获取数据成功',[
            'levels'=>[
                'L0', 'L1'
            ]
        ]);
    }

}