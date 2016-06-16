<?php
/**
* 专题系统
*
*/

include_once(__DIR__.'/Root.php');

class ProductController extends RootController
{
    //分类
    private $classityItems;

    //专题信息
    private $topicItem;

    /**
     * 获取分类列表
     *
     * @return
     **/
    public function categoryListAction()
    {
        $modelCategory = new Model_Cinema_Category();
        $categoryItems = $modelCategory->selectWith(array(), array('id', 'name', 'parentId'));
        $categoryList  = $modelCategory->_dealClasses($categoryItems);
        return $this->sendAjax($categoryList);
    }

    /**
     * 获取广告
     *
     * @return
     **/
    public function advertAction()
    {
        $cid = $this->getIntQuery('cid', '');
        if (!$cid) {
            return $this->sendAjax('顶级类别未知, 获取广告失败!', false);
        }

        $modelAdvert = new Model_Cinema_Advertisement();
        $advert      = $modelAdvert->selectWith(array('location' => $cid), array(), 'sequence ASC');
        return $this->sendAjax($advert);
    }

    /**
     * 根据类别id获取该类页的所有专题及该专题所有产品的列表信息。 
     *
     * @return json
     **/
    public function topicOfCategoryAction()
    {
        $cid = $this->getIntQuery('cid', 0);
        if (!$cid) {
            return $this->sendAjax('顶级类别未知, 获取专题失败!', false);
        }

        $modelTopic = new Model_Cinema_Topic();
        $topic      = $modelTopic->selectWith(array('categoryId' => $cid), array(), 'squence ASC');
        $tIds       = Common::getColumnArray($topic, 'id');
        if ($tIds) {
            $modelRelation = new Model_Cinema_RelationOfProductAndTopic();
            $list          = $modelRelation->getTopicProduct($tIds);
            $list          = Common::convertToOneArrayBycolumn($list, 'topicId');
            if ($list) {
                foreach ($topic as $key => &$value) {
                    if (isset($list[$value['id']])) {
                        $value['products'] = $list[$value['id']];
                    }
                }
                unset($value);
            }
        }
        return $this->sendAjax($topic);
    }

    /**
     * 根据专题id请求该专题内所有产品的列表。
     *
     * @return
     **/
    public function productOfTopicAction()
    {
        $tid = $this->getIntQuery('tid', 0);
        if (!$tid) {
            return $this->sendAjax('请提供专题的ID!', false);
        }

        $modelRelation = new Model_Cinema_RelationOfProductAndTopic();
        $list          = $modelRelation->getTopicProduct(array($tid));
        return $this->sendAjax($list);
    }

    /**
     * 根据小类别id请求该小类别的所有产品列表。
     *
     * @return
     **/
    public function productOfChildAction()
    {
        $cid = $this->getIntQuery('cid', 0);
        if (!$cid) {
            return $this->sendAjax('类别未知, 获取作品失败!', false);
        }

        $modelRelation = new Model_Cinema_RelationOfCategoryAndProduct();
        $list          = $modelRelation->getProducts(array($cid));
        return $this->sendAjax($list);
    }

    /**
     * 根据产品id请求某产品的详细信息。
     *
     * @return
     **/
    public function productDetailAction()
    {
        $pid = $this->getIntQuery('pid', 0);
        if (!$pid) {
            return $this->sendAjax('类别未知, 获取作品失败!', false);
        }

        $modelProduct = new Model_Cinema_Product();
        $Row          = $modelProduct->getItemById($pid);
        $Row['pic']   = json_decode($Row['pic'], true);
        return $this->sendAjax($Row);
    }

    /**
     * 产品搜索。根据字符串请求所有匹配的产品列表。
     *
     * @return
     **/
    public function productSearchAction()
    {
        $search = $this->getTrimedQuery('search', '');
        if (!$search) {
            return $this->sendAjax('请输入搜索信息!', false);
        }
        $condition['where']  = array('title' => array('like' => $search. "%"));
        $condition['getSql'] = true;
        $modelProduct = new Model_Cinema_Product();
        $items        = $modelProduct->getList($condition);
        $items        = isset($items['list']) ? $items['list'] : array();
        if ($items) {
            foreach ($items as $key => &$value) {
                $value['pic'] = json_decode($value['pic']);
            }
            unset($value);
        }
        return $this->sendAjax($items);
    }
          
}