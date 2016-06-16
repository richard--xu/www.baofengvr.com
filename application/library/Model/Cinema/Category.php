<?php
/**
* category表
*
*/

class Model_Cinema_Category extends Model_Base
{
    protected $_db    = 'cinema';
    protected $_table = 'category';
    
    /**
     * 按照分类的层次关系返回数组
     *
     * @param array $classes 要分类的数组
     * @param array $group 初步分组后的数组
     * @return void
     **/
    public function _dealClasses($classes, $group = array())
    {
        if ($classes) {
            if ($group) {
                
                foreach ($classes as $key => $value) {
                    if (isset($group[$value['id']])) {
                        $classes[$key]['child'] = $this->_dealClasses($group[$value['id']], $group);
                    }
                }

                return $classes;
            } else { //按照父级分类,重新组成数组
                
                $groupArray = array();
                foreach ($classes as $key => $value) {
                    $groupArray[$value['parentId']][] = $value;
                }
                //从根类,parent = 0的开始
                return $this->_dealClasses($groupArray[0], $groupArray);
            }
        }

        return array();
    }
}