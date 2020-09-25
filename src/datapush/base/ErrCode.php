<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/9/25
 * Time: 13:44
 */

namespace bfw\base;


class ErrCode
{
    public static $OK = 0;
    public static $errCode=array(
        '0' => '成功',
        '-1' => '失败',
    );
    public static function getErrText($err) {
        if (isset(self::$errCode[$err])) {
            return self::$errCode[$err];
        }else {
            return false;
        };
    }
}