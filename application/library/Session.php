<?php

/**
 * Description of Yi_Session
 *
 * @author cy
 */
class Session implements ArrayAccess {

    private static $_handle;
    private $redisConn;
    private $en64syshash = array(
        '0', '1', '2', '3', '4', '5', '6', '7', '8', '9',
        'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z', '-',
        'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', '_'
    );
    private $sessionData = array();
    private $cookieKey   = '_BFVR';
    private $noStore     = true;

    public static function getInstance($type = 1) {
        if (!is_object(self::$_handle)) {
            self::$_handle = new self();
            self::$_handle->redisConn = Rds::getInstance()->getconn();
            self::$_handle->start($type);
        }
        return self::$_handle;
    }

    private function __construct() {
        
    }

    public function start($type = 1) {
        $ssid = $this->getSid();
        if (!$ssid) {
            $this->setSid($type);
            setcookie($this->cookieKey, $this->sessionData['session_id'], 0, '/', Bootstrap::$config['domain']);
            $this->noStore = false;
        } else {
            $this->sessionData = $this->redisConn->hGetAll($_COOKIE[$this->cookieKey]);
        }
    }

    public function getSid()
    {
        //如果没有现成的SESSION ID, 就去找cookie 或者传参
        if (!isset($this->sessionData['session_id']) || !$this->sessionData['session_id']) {
            $ssid = Yaf_Dispatcher::getInstance()->getRequest()->getRequest("ssid", false);
            if (!$ssid && isset($_COOKIE[$this->cookieKey]) && $_COOKIE[$this->cookieKey]) {
                $tmpSid = $_COOKIE[$this->cookieKey];
                if (strlen($tmpSid) != 16)
                    return false;
                for ($i = 0; $i < 16; $i++) {
                    if (!in_array($tmpSid[$i], $this->en64syshash))
                        return false;
                }
                $ssid = $tmpSid;
            }

            if ($ssid) {
                $result = $this->verify($ssid);
                if ($result === false) {
                    return false;
                }
            }
            return $ssid;
        }

        return $this->sessionData['session_id'];
    }

    private function setSid($type = 1)
    {
        if ($type == 1) {
            $serv_ip = isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : '127.0.0.10';
            $hd = explode('.', $serv_ip);
            $time = time();
            $key = 's' . $time;
            $secId = $this->redisConn->incr($key);
            if ($secId == 1)
                $this->redisConn->setTimeout($key, 2);
            $keys = sprintf("%08x", $time) . sprintf("%02x", $hd[1]) . sprintf("%02x", $hd[2]) . sprintf("%02x", $hd[3]) . sprintf("%04x", getmypid()) . sprintf("%06x", $secId);
            $this->sessionData['session_id'] = '';
            $keys = pack("H*", $keys);
            for ($i = 0; $i < 4; $i++) {
                $idx = 3 * $i;
                $this->sessionData['session_id'] .= $this->en64syshash[ord($keys[$idx]) >> 2] .
                        $this->en64syshash[(ord($keys[$idx]) & 3) << 4 | ord($keys[$idx + 1]) >> 4] .
                        $this->en64syshash[(ord($keys[$idx + 1]) & 15) << 2 | ord($keys[$idx + 2]) >> 6] .
                        $this->en64syshash[ord($keys[$idx + 2]) & 63];
            }
            $this->sessionData['session_id'] = str_shuffle($this->sessionData['session_id']);
        } else {
            session_start();
            $this->sessionData['session_id'] = session_id();
        }

        return $this->sessionData['session_id'];
    }

    private function verify($ssid) {

        return $this->redisConn->hGet($ssid, 'session_id') != $ssid ? false : true;
    }

    public function offsetSet($offset, $val) {
        if (!is_null($offset))
            $this->sessionData[$offset] = $val;
        $this->noStore = false;
    }

    public function offsetUnset($offset) {
        unset($this->sessionData[$offset]);
        $this->redisConn->hDel($this->sessionData['session_id'], $offset);
        $this->noStore = false;
    }

    public function offsetExists($offset) {
        return isset($this->sessionData[$offset]);
    }

    public function offsetGet($offset) {
        return isset($this->sessionData[$offset]) ? $this->sessionData[$offset] : null;
    }

    /**
     * 登出
     * 
     * @return [type] [description]
     */
    public function clear() {
        return $this->redisConn->del($this->sessionData['session_id']);
    }

    /**
    * 获取所有session值
    */
    public function getAll()
    {
        return $this->sessionData;
    }

    public function __destruct() {
        if (!$this->noStore) {
            $this->redisConn->hMset($this->sessionData['session_id'], $this->sessionData);
            if (!$this->redisConn->exists($this->sessionData['session_id'])) {
                $this->redisConn->setTimeout($this->sessionData['session_id'], 604800); // 7days
            }
        }
    }

}

?>
