<?php
/**
* product表
*
*/

class Model_Cinema_Product extends Model_Base
{
    protected $_db    = 'cinema';
    protected $_table = 'product';

    /**
     * 获取和专题有关的产品信息
     *
     * @return array
     * @author 
     **/
    public function getProducts($conditions)
    {
    	$select    = $this->getSelect();
        $tmpSelect = $this->getSelect();
    	if (isset($conditions['category']) && $conditions['category'] === true) {
    		$joinWhere  = 'relationOfCategoryAndProduct.productId = product.id';
    		$joinWhere .= isset($conditions['joinWhere']) && $conditions['joinWhere'] 
    							? ' AND '. $conditions['joinWhere'] : '';
        	$productCol = array('categoryId');
    		$select->join('relationOfCategoryAndProduct', $joinWhere, $productCol);
            $tmpSelect->join('relationOfCategoryAndProduct', $joinWhere, array());
    	}

    	if (isset($conditions['where']) && $conditions['where']) {
    		$this->_where($conditions['where'], $select);
            $this->_where($conditions['where'], $tmpSelect);
    	}

        if (isset($conditions['getCount']) && $conditions['getCount']) {
            $tmpSelect->columns(array('total' => New Zend\Db\Sql\Predicate\Expression('DISTINCT product.id')));
            //echo $tmpSelect->getSqlString();
            $result['count'] = $this->getTableGateway()->selectWith($tmpSelect)->count();
        }

        if (isset($conditions['group']) && $conditions['group']) {
            $select->group($conditions['group']);
        }

    	if (isset($conditions['limit']) && $conditions['limit']) {
            $select->limit((int) $conditions['limit']);
            if (isset($conditions['offset']) && $conditions['offset']) {
                $select->offset((int) $conditions['offset']);
            }
        }

        if (isset($conditions['getSql']) && $conditions['getSql']) {
            echo $select->getSqlString();
        }

    	$result['list'] = $this->getTableGateway()->selectWith($select)->toArray();
        //处理图片json
        if ($result['list']) {
            foreach ($result['list'] as $key => &$value) {
              $value['pic'] = json_decode($value['pic'], true);
            }
            unset($value);
        }
    	return $result;
    }
    
}