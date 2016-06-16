<?php

/**
 * Description of Yi_Redis
 *
 * @author cy
 */
class Rds {

    private static $_handle;
    private $redis_conn;

    public static function getInstance() {
        if (!is_object(self::$_handle)) {
            $config = Bootstrap::$config['redis'];

            self::$_handle = new self();
            self::$_handle->redis_conn = new Redis();
            self::$_handle->redis_conn->connect($config["tools"]['host'], $config["tools"]['port'], 5);
            self::$_handle->redis_conn->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_PHP);
        }
        return self::$_handle;
    }

    public function getconn() {
        return $this->redis_conn;
    }

}

?>
