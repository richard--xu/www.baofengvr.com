<?php
/**
* 程序突然中断,错误处理 
*
*/

class LastError
{
    
    private function __construct()
    {
        
    }

    /**
    * 将错误信息记录入日志中
    *
    * @param array $log 要记录的日志内容
    */
    private function log($log){

    }
    /**
     * 错误处理
     *
     * @return
     **/
    public static function handler()
    {
        /**('type' => 1, 'message' => 'Class \'Yaf_Registrys\' not found', 'file' => '/usr/local/Rds.php', 'line' => 15)*/
        $error = error_get_last();
        if ($error) {
            //var_export($error);
        }
    }
}