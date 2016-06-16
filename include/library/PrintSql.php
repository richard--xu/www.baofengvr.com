<?php

class PrintSql
{

    private static $instance;
    private static $data;
    private static $sql;
    private static $time;
    private static $size;
    private static  $first = true;


    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    static public function start($sql, $params = array())
    {
        if ($params) {
            $nameList = $valueList = array();
            foreach ($params as $name => $value) {
                $nameList[]  = ':' . $name;
                $valueList[] = "'" . addcslashes($value, "\000\n\r\\'\"\032\x00\x1a") . "'";
            }
            $sql = str_replace($nameList, $valueList, $sql) . '; [预处理]';
        }
        
        self::$sql   = $sql;
        list($usec, $sec) = explode(" ", microtime());
        self::$time = (float) $usec + (float) $sec;
        self::$size  = memory_get_usage();
    }

    public static function httpStart($url, $query = array())
    {
        if ($query) {
            foreach ($query as $name => $value) {
                if (!is_array($value)) {
                    $query[$name] = $name . '=' . rawurlencode($value);
                }
            }
            $url .= '?' . implode('&', $query) . ' [http请求]';
        }
    
        self::$sql  = $url;
        list($usec, $sec) = explode(" ", microtime());
        self::$time = (float) $usec + (float) $sec;
        self::$size = memory_get_usage();
    }
    
    public static function end()
    {
        list($usec, $sec) = explode(" ", microtime());
        self::$data[] = array(
                            'sql'  => self::$sql,
                            'time' => ((float) $usec + (float) $sec) - self::$time, 
                            'size' => (memory_get_usage() - self::$size)
                        );
    }
    
    public static function showToAdmin()
    {
        $config = Bootstrap::$config['printSql'];
        if (!$config['print']) {
            return false;
        }
        
        if (in_array('*', $config['userIds'])
                || (isset($_SESSION['uid']) && in_array($_SESSION['uid'], $config['userIds']))
        ) {
            $domain = '.21boya.cn';
            if (isset($_COOKIE['printsql'])) {
                $expire = gmdate("l d F Y H:i:s", strtotime("-30 day")) . " GMT";
                echo '<p><a href="javascript:void(0);" ' .
                     'onclick="document.cookie=\'printsql=; expires=' . $expire .
                     '; path=/; domain=' . $domain . '\';window.location.reload();">[关闭打印]</a></p>';
                self::getInstance()->show();
            } else {
                $expire = gmdate("l d F Y H:i:s",strtotime("+30 day")) . " GMT";
                echo '<p><a href="javascript:void(0);" ' .
                     'onclick="document.cookie=\'printsql=ok; expires=' . $expire .
                     '; path=/; domain=' . $domain . '\';window.location.reload();">[打印sql]</a></p>';
            }
        }
        return false;
    }

    public function show()
    {
        if (!self::$first) {
            return false;
        }
        self::$first = false;

        if (!self::$data) {
            return false;
        }
        
        echo '<table border="0" id="printSql">';
        echo '<tr><td>time(秒)&nbsp;</td><td>memory(MB)&nbsp;</td><td>SQL语句</td></tr>';
        foreach (self::$data as $value) {
            echo '<tr><td>' . round($value['time'], 3).'</td>' .
                 '<td>' . round($value['size'] / 1024 / 1024, 2) . '</td>' .
                 '<td>' . $value['sql'] . '</td></tr>';
        }
        echo '</table>';

    }

//     public function __destruct()
//     {
//         $this->show();
//     }
}
