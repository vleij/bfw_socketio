<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/9/25
 * Time: 13:38
 */
namespace bfw\base;

class StatusCode
{
    public static $statusCode=array(
        'success' => '0',
        'error' => '-1',
    );

    /**
     * Notes:
     * Date: 2020/9/25
     * Time: 13:40
     * @param $err
     * @return bool|mixed
     * @author: 雷佳
     */
    public static function getStatusCode($code) {
        if (isset(self::$statusCode[$code])) {
            return self::$statusCode[$code];
        }else {
            return false;
        };
    }

}