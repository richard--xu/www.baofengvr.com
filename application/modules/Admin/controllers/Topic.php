<?php
/**
* 后台申请列表
*/

include_once(__DIR__.'/Root.php');

class TopicController extends RootController
{
    /**
     * 后台表单模板制作首页
     * 报名表列表
     *
     * @return void
     **/
    public function indexAction()
    {
        $topicObj       = new Model_Cinema_Topic();
        $param['topic'] = Common::convertArrBycolumn($topicObj->selectWith(), 'id');

        //获取所有分类
        $categoryObj       = new Model_Cinema_Category();
        $categoryItems     = $categoryObj->selectWith(array(), array('id', 'name', 'parentId'));
        $categoryList      = $categoryObj->_dealClasses($categoryItems);
        $param['category'] = Common::convertArrBycolumn($categoryList, 'id');
        //产品
        $relationObj          = new Model_Cinema_RelationOfProductAndTopic();
        $productItems         = $relationObj->getTopicProduct();
        $param['productList'] = Common::convertToOneArrayBycolumn($productItems, 'topicId');
        $this->getView()->assign($param);
    }

    /**
     * 新增分类
     *
     * @return
     **/
    public function addAction()
    {
        Yaf_Dispatcher::getInstance()->disableView();
        if (!$this->getRequest()->isXmlHttpRequest()) {
            die('非法的链接');
        }

        $add = $this->_dealPostData();
        if (!$add['status']) {
            return $this->sendAjax($add['data'], false);
        }

        try{
            $topicObj   = new Model_Cinema_Topic();
            $connection = $topicObj->getAdapter()->getDriver()->getConnection();
            $connection->beginTransaction();

            //插入活动数据
            $add['data']['addTime'] = time();
            $insertId = $topicObj->save($add['data']);
            if (!$insertId) {
                $connection->rollback();
                return $this->sendAjax('新增专题失败!', false);
            }

            if ($add['products']) {
                $columns = array('productId', 'topicId', 'addTime');
                $items   = array();
                foreach ($add['products'] as $key => $value) {
                    $items[] = array($value, $insertId, time());
                }

                $relationObj = new Model_Cinema_RelationOfProductAndTopic();
                $result      = $relationObj->insertMore($columns, $items);
                if ($result === false) {
                    $connection->rollback();
                    return $this->sendAjax('关联分类失败!', false);
                }
            }

            //提交数据
            $connection->commit();
        } catch(Exception $e) {
            if ($connection instanceof \Zend\Db\Adapter\Driver\ConnectionInterface) {
                $connection->rollback();
            }
            return $this->sendAjax('新增失败!', false);
        }
        return $this->sendAjax('新增成功!');
    }

    /**
     * 处理更新
     *
     * @return json
     **/
    public function editAction()
    {

        Yaf_Dispatcher::getInstance()->disableView();
        if (!$this->getRequest()->isXmlHttpRequest()) {
            die('非法的链接');
        }

        //专题ID
        $id = $this->getIntPost('id', 0);
        if (!$id) {
            return $this->sendAjax('参数错误', false);
        }
        //专题信息
        $topicObj = new Model_Cinema_Topic();
        $item     = $topicObj->getItemById($id);
        if (!$item) {
            return $this->sendAjax('未找到该专题', false);
        }

        //处理提交的数据
        $edit = $this->_dealPostData();
        if (!$edit['status']) {
            return $this->sendAjax($edit['data'], false);    
        }

        try{
            $connection = $topicObj->getAdapter()->getDriver()->getConnection();
            $connection->beginTransaction();
            $result = $topicObj->update($edit['data'], array('id' => $id));
            if ($result === false) {
                $connection->rollback();
                return $this->sendAjax('更新数据失败', false);
            }

            $relationObj   = new Model_Cinema_RelationOfProductAndTopic();
            $relationItems = $relationObj->selectWith(array('topicId' => $id), 
                                                       array('productId')
                                            );
            $pids = Common::getColumnArray($relationItems, 'productId');
            if (array_diff($edit['products'], $pids) != array_diff($pids, $edit['products'])) {
                //删除以前的
                $result = $relationObj->delItems(array('topicId' => $id));
                if ($result === false) {
                    $connection->rollback();
                    return $this->sendAjax('更新失败,请稍后重试!', false);
                }
                
                //新增数据
                if ($edit['products']) {
                    $columns = array('productId', 'topicId', 'addTime');
                    $items   = array();
                    foreach ($edit['products'] as $key => $value) {
                        $items[] = array($value, $id, time());
                    }

                    $result = $relationObj->insertMore($columns, $items);
                    if ($result === false) {
                        $connection->rollback();
                        return $this->sendAjax('关联分类失败!', false);
                    }
                }
            }

            //提交数据
            $connection->commit();
        } catch(Exception $e) {
            if ($connection instanceof \Zend\Db\Adapter\Driver\ConnectionInterface) {
                $connection->rollback();
            }
            return $this->sendAjax('修改失败!', false);
        }

        return $this->sendAjax('更新成功!');
    }

    /**
     * 删除专题
     *
     * @return json
     **/
    public function deleteAction()
    {
        Yaf_Dispatcher::getInstance()->disableView();
        if (!$this->getRequest()->isXmlHttpRequest()) {
            die('非法的链接');
        }

        $id = $this->getIntPost('id', 0); //专题ID
        if (!$id) {
            return $this->sendAjax('参数错误!', false);
        }

        $topicObj    = new Model_Cinema_Topic();
        $relationObj = new Model_Cinema_RelationOfProductAndTopic();

        try {
            //事务开启
            $connection = $topicObj->getAdapter()->getDriver()->getConnection();
            $connection->beginTransaction();

            //删除作品
            $result = $topicObj->delItemById($id);
            if ($result === false) {
                $connection->rollback();
                return $this->sendAjax('删除专题失败!', false);
            }

            //删除分类关系
            $result = $relationObj->delItems(array('topicId' => $id));
            if ($result === false) {
                $connection->rollback();
                return $this->sendAjax('删除专题产品失败!', false);
            }

            //提交数据
            $connection->commit();
        } catch(Exception $e) {
            if ($connection instanceof \Zend\Db\Adapter\Driver\ConnectionInterface) {
                $connection->rollback();
            }
            return $this->sendAjax('删除失败!', false);
        }

        return $this->sendAjax('删除成功!');
    }

    /**
     * 删除专题
     *
     * @return json
     **/
    public function searchAction()
    {
        Yaf_Dispatcher::getInstance()->disableView();
        if (!$this->getRequest()->isXmlHttpRequest()) {
            die('非法的链接');
        }

        $title = $this->getTrimedPost('title', ''); //专题ID
        if (!$title) {
            return $this->sendAjax('参数错误!', false);
        }

        $productObj   = new Model_Cinema_Product();
        $item         = $productObj->select(array('title' => $title));
        if (!$item) {
            return $this->sendAjax('未找到该名称的专题!', false);
        }

        return $this->sendAjax($item);
    }

    /**
     * 处理新增活动或者修改活动提交的数据
     *
     * @return array
     **/
    private function _dealPostData()
    {
        //获取post值
        $name       = $this->getTrimedPost('name', '');
        $categoryId = $this->getIntPost('categoryId', 0);
        $squence    = $this->getIntPost('squence', 0);
        $products   = $this->getRequest()->getPost('products', array());
        //判断不能为空的值
        if (!$name || !$categoryId || !$squence) {
            return array('status' => false, 'data' => '请先填写完整, 谢谢!');
        }

        //检测长度
        $nameLen = Common::strLength($name, 'utf8');
        if ($nameLen > 50) {
            return array('status' => false, 'data' => '产品名称不能超过50个字, 谢谢!');
        }

        //为更新准备值
        $data['name']       = $name;
        $data['categoryId'] = $categoryId;
        $data['squence']    = $squence;

        return array('status' => true, 'data' => $data, 'products' => $products);
    }
}