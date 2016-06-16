<?php
/**
* relationOfProductAndTopic表
*
*/

class Model_Cinema_RelationOfProductAndTopic extends Model_Base
{
    protected $_db    = 'cinema';
    protected $_table = 'relationOfProductAndTopic';
    
    /**
     * 获取和专题有关的产品信息
     *
     * @return array
     * @author 
     **/
    public function getTopicProduct($topicIds = array())
    {
    	$joinWhere  = 'relationOfProductAndTopic.productId = product.id';
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

    	if ($topicIds && is_array($topicIds)) {
    		$select->where->in('relationOfProductAndTopic.topicId', $topicIds);
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