<?php
/**
* relationOfCategoryAndProduct表
*
*/

class Model_Cinema_RelationOfCategoryAndProduct extends Model_Base
{
    protected $_db    = 'cinema';
    protected $_table = 'relationOfCategoryAndProduct';
    
    /**
     * 获取和专题有关的产品信息
     *
     * @return array
     * @author 
     **/
    public function getProducts($cIds = array())
    {
    	$joinWhere  = 'relationOfCategoryAndProduct.productId = product.id';
      $productCol = array('title', 
                          'description', 
                          'pic', 
                          'avgStar', 
                          'totalUserForStar', 
                          'filePath', 
                          'fileSize'
                    ); 
    	$select = $this->getSelect()
    		 		   ->join('product', $joinWhere, $productCol);

    	if ($cIds && is_array($cIds)) {
    		$select->where->in('relationOfCategoryAndProduct.categoryId', $cIds);
    	}

      $list = $this->getTableGateway()->selectWith($select)->toArray();
      //处理图片json
      if ($list) {
        foreach ($list as $key => &$value) {
          $value['pic'] = json_decode($value['pic'], true);
        }
        unset($value);
      }
      return $list;
    }
}