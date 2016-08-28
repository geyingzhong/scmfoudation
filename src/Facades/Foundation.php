<?php
namespace geyingzhong\Facades;
/**
 * Created by PhpStorm.
 * User: bestry
 * Date: 8/28/16
 * Time: 2:11 PM
 */
use Illuminate\Support\Facades\Facade;

class Foundation extends Facade
{
    /**
     * Get the registered name of the foundation.
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'foundation';
    }

}