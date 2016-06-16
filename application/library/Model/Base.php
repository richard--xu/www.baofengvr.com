<?php
class Model_Base extends Model
{
    protected $_group = 'default';
    protected $_db    = '';
    protected $_pkey   = 'id';

    /**
     * 通过主键获得一条数据
     * @param int $id
     * @param mixed $field 获取字段
     * @return array
     */
    public function getItemById($id = 0, $field = array())
    {
        if ($id) {
            $select = $this->getSelect()
                            ->columns($field ? (is_array($field) ? $field : array($field)) : array('*'))
                            ->where(array($this->_pkey => (int) $id));
            if ($info = $this->getTableGateway()->selectWith($select)->toArray()) {
                return $info[0];
            }
        }
        return array();
    }

    /**
     * 通过主键删除一条数据
     * @param int $id
     * @return bool
     */
    public function delItemById($id = 0)
    {
        if ($id) {
            return $this->getTableGateway(true)
                        ->delete(array($this->_pkey => (int) $id));
        }
        return false;
    }

    /**
     * 删除一条或多条数据
     * @param array $data
     * @return bool
     */
    public function delItems($data = array())
    {
        if ($data && is_array($data)) {
            return $this->getTableGateway(true)->delete($data);
        }
        return false;
    }

    /**
     * 添加一条数据,返回主键
     * @param array $data
     * @return int    ID
     */
    public function save($data = array())
    {
        if ($this->insert($data)) {
            return $this->getAdapter(true)->getDriver()->getConnection()->getLastGeneratedValue();
        }
        return false;
    }

    /**
     * 添加一条数据
     * @param array $data
     * @return bool
     */
    public function insert($data = array())
    {
        if ($data && is_array($data)) {
            return $this->getTableGateway(true)->insert($data);
        }
        return false;
    }

    /**
     * 一次添加多条数据
     * @param array $columns 要插入数据库的字段 ,例:array('a', 'b', 'c')
     * @param array $data 要插入数据库的字段,二维数组 ,例: array(array(1, 2, 3), array(2, 3, 4));
     * @return int
     */
    public function insertMore($columns = array(), $data = array())
    {
        if (empty($columns) || empty($data)) {
            return false;
        }

        $sql = 'INSERT INTO ';
        $sql .= $this->getTable();
        $sql .= ' (`'.join('`,`', $columns).'`)';
        $sql .= ' VALUES ';
        $val = array();
        foreach ($data as $key => $value) {
            $val[] = ' (\''.join('\',\'', $value).'\')';
        }
        $sql .= join(',', $val);
        $result = $this->getAdapter(true)->query($sql)->execute();
        return $result->getAffectedRows();
    }

    /**
     * 更新数据
     * @param array $set
     * @param array $where
     * @return bools
     */
    public function update($set = array(), $where = array())
    {
        if ($set && $where && is_array($set) && is_array($where)) {
            return $this->getTableGateway(true)->update($set, $where);
        }
        return false;
    }

    /**
     * 根据where条件查询
     * @param array $select
     * @return array
     */
    public function select($where = array(), $field = array(), $order = array())
    {
        if ($where && is_array($where)) {
            $select = $this->getSelect();
            if ($field) {
                $select->columns($field);
            }

            $this->_where($where, $select);
            //$select->where($where);
            if ($order) {
                $select->order($order);
            }
            $select->limit(1);
            if ($info = $this->getTableGateway()->selectWith($select)->toArray()) {
                return $info[0];
            }
        }
        return array();
    }

    /**
     * 根据where条件查询
     * @param array $select
     * @return array
     */
    public function selectWith($where = array(), $field = array(), $order = array(), $groupBy = '')
    {
        $select = $this->getSelect();
        if ($field) {
            $select->columns($field);
        }
        
        if ($where && is_array($where)) {
            $select->where($where);
        }
        
        if ($order) {
            $select->order($order);
        }

        if ($groupBy) {
            $select->group($groupBy);
        }
        return $this->getTableGateway()->selectWith($select)->toArray();
    }

    /**
     * 根据were条件获取总条数
     * @param array $where  例 array('User_id' => 1)
     * @return
     */
    public function getCount($where = array(), $groupBy = '')
    {
        $this->_where($where, ($select = $this->getSelect()));
        if ($groupBy) {
            $select->group($groupBy);
        }
        return $this->getTableGateway()->selectWith($select)->count();
    }

    /**
     * 获取列表
     * @param $conditions
     *
     * //填写需要的列名, 单个可以字符串， 多个用数组
     * $conditions['fields'] = 'id' || array('id', '别名' => '字段名', '...');
     *
     * //where 条件, 写法如下范例
     * $conditions['where']  = array(
     *                             'a' => 828,                                   //a = 828
     *                             'b' => array('!=' => '828'),                  //b != 828
     *                             'c' => array('828', '829'),                   //c in (828, 829)
     *                             'd' => array('notin' => array('828', '829')), //d not in (828, 829)
     *                             'e' => array('>'  => '828'),                     //e > 828
     *                             'f' => array('<'  => '828'),                     //f < 828
     *                             'g' => array('>=' => '828'),                    //e >= 828
     *                             'h' => array('<=' => '828'),                    //f <= 828
     *                             'i' => array('><' => array(10, 20)),            //i > 10 and i < 20
     *                             'i' => array('>=<' => array(10, 20)),           //i >= 10 and i <= 20
     *                             'j' => array('between' => array(10, 20)),       //j >= 10 and i <= 20
     *                         );
     *
     * //group 条件， 支持字符串和数组
     * $conditions['group']  = 'id' || array('id');
     *
     * //order 条件， 支持字符串和数组
     * $conditions['order']  = 'id desc' || array('id desc');
     *
     * //limit 条件
     * $conditions['limit']  = 10;
     *
     * //offset 条件
     * $conditions['offset'] = 5;
     *
     * //getCount 是否需要获取总条数, 默认不获取
     * $conditions['getCount'] = false;
     *
     * //getSql 是否获取sql
     * $conditions['getSql'] = false;
     *
     * @return $data
     * $data['list']  = array(); //数据列表
     * 当 $conditions['getCount'] = True 时
     * $data['count'] = 总条数 (不受limit影响);
     * 当 $conditions['getSql'] = True 时
     * $data['sql'] = sql 语句;
     */
    public function getList($conditions = array())
    {
        $result = array('list' => '');
        $select = $this->getSelect();

        if (isset($conditions['fields']) && $conditions['fields']) {
            if (!is_array($conditions['fields'])) {
                $conditions['fields'] = array($conditions['fields']);
            }
            $select->columns($conditions['fields']);
        }
        if (isset($conditions['where']) && $conditions['where']) {
            $this->_where($conditions['where'], $select);
        }

        if (isset($conditions['group']) && $conditions['group']) {
            $select->group($conditions['group']);
        }

        if (isset($conditions['getCount']) && $conditions['getCount']) {
            $result['count'] = $this->getTableGateway()->selectWith($select)->count();
        }

        if (isset($conditions['order']) && $conditions['order']) {
            $select->order($conditions['order']);
        }

        if (isset($conditions['limit']) && $conditions['limit']) {
            $select->limit((int) $conditions['limit']);
            if (isset($conditions['offset']) && $conditions['offset']) {
                $select->offset((int) $conditions['offset']);
            }
        }

        if (isset($conditions['getSql']) && $conditions['getSql']) {
            $result['sql'] = $select->getSqlString();
        }

        $result['list'] = $this->getTableGateway()->selectWith($select)->toArray(); //getDataSource
        return $result;
    }

    protected function _where($whereCond = array(), $select)
    {
        foreach ($whereCond as $key => $where) {
            if (is_array($where)) {
                if ($action = key($where)) {
                    switch ($action) {
                        case '!='      : $select->where->notEqualTo($key, current($where));
                                         break;
                        case 'in'      : $select->where->in($key, current($where));
                                         break;
                        case 'notin'   : $select->where($key . ' NOT IN (' . implode(',', current($where)) . ')');
                                         break;
                        case '<'       : $select->where->lessThan($key, current($where));
                                         break;
                        case '<='      : $select->where->lessThanOrEqualTo($key, current($where));
                                         break;
                        case '>'       : $select->where->greaterThan($key, current($where));
                                         break;
                        case '>='      : $select->where->greaterThanOrEqualTo($key, current($where));
                                         break;
                        case '><'      : $data = current($where);
                                         $select->where->greaterThan($key, current($data))->lessThan($key, end($data));
                                         break;
                        case '>=<'     : $data = current($where);
                                         $select->where->greaterThanOrEqualTo($key, current($data))->lessThanOrEqualTo($key, end($data));
                                         break;
                        case 'between' : $data = current($where);
                                         $select->where->between($key, current($data), end($data));
                                         break;
                        case 'like'    : $select->where->like($key, current($where));
                                         break;
                    }
                } else {
                    $select->where->in($key, $where);
                }
            } else {
                $select->where(array($key => $where));
            }
        }
        return true;
    }

}