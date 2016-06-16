<?php

class Model
{
    /**
    * 数据表主键
    *
    * @var string
    */
    protected $_pkey;

    /**
    * 数据库名称
    *
    * @var string
    */
    protected $_db;

    /**
    * 数据表名称
    *
    * @var string
    */
    protected $_table;

    /**
    * 要选择那组数据库服务器连接
    *
    * @var string
    */
    protected $_group;

    /**
    * 具体的要连接的服务器的配置信息
    *
    * @var array
    */
    protected $_server;

    /**
    * 数据库适配信息
    *
    * @var array
    */
    static private $adapters = array();

    /**
     * 构造函数
     *
     * @return void
     **/    
    public function __construct(){}

    /**
     * 获取表名 $_table
     *
     * @return string
     **/
    public function getTable()
    {
        return $this->_table;
    }
    
    /**
     * 获取数据库 $_db
     *
     * @return string
     **/
    public function getDb()
    {
        return $this->_db;
    }

    /**
     * 获取要选择的服务器组 $_group
     *
     * @return string
     **/
    protected function getGroup()
    {
        //先暂时设置为default
        $this->_group = 'default';

        return $this->_group;
    }

    /**
     * 获取具体的要连接的服务器 $_server
     * 以后有选择策略可以在这里添加
     *
     * @return array
     **/
    protected function getServer($master = false)
    {
        $group    = $this->getGroup();
        $dbConfig = Bootstrap::$config['mysql'][$group];
        //分配策略array_rand
        $this->_server = $master ? $dbConfig['master'][array_rand($dbConfig['master'])]
                                 : $dbConfig['slave'][array_rand($dbConfig['slave'])];

        return $this->_server;
    }

    public function getAdapter($master = false)
    {
        $config = $this->getServer($master);
        
        if (!isset(self::$adapters[$config['host']][$config['port']][$this->getDb()])) {
            $adapter = new Zend\Db\Adapter\Adapter(array(
                            'driver'    => 'Pdo_Mysql',
                            'database'    => $this->getDb(),
                            'username'    => $config['user'],
                            'password'    => $config['pass'],
                            'hostname'    => $config['host'],
                            'port'        => $config['port'],
                       ));
            $adapter->query("SET NAMES utf8", $adapter::QUERY_MODE_EXECUTE);
            
            $timezone = TIME_ZONE > 0 ? '-' . abs(TIME_ZONE) : '+' . abs(TIME_ZONE);
            $adapter->query("set time_zone = '" . $timezone . ":00';", $adapter::QUERY_MODE_EXECUTE);
            self::$adapters[$config['host']][$config['port']][$this->getDb()] = $adapter;
        }
        
        return self::$adapters[$config['host']][$config['port']][$this->getDb()];
    }

    public function getSql($master = false)
    {
        return new Zend\Db\Sql\Sql($this->getAdapter($master), $this->getTable());
    }

    public function getTableGateway($master = false)
    {
        return new Zend\Db\TableGateway\TableGateway($this->getTable(), $this->getAdapter($master));
    }

    public function getRowGateway($master = false)
    {
        return new Zend\Db\RowGateway\RowGateway($this->pkey, $this->getTable(), $this->getAdapter($master));
    }

    public function getSelect()
    {
        return new Zend\Db\Sql\Select($this->getTable());
    }
}
