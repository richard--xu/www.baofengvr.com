<?php
/**
* 分类
*/

include_once(__DIR__.'/Root.php');

class CategoryController extends RootController
{
    /**
     * 分类表
     *
     * @return void
     **/
    public function indexAction()
    {

        $categoryObj       = new Model_Cinema_Category();
        $categoryItems     = $categoryObj->selectWith(array(), array('id', 'name', 'parentId'));
        $param['category'] = $categoryObj->_dealClasses($categoryItems);
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
        //获取参数
        $name     = $this->getTrimedPost('name', '');
        $parentId = $this->getIntPost('pid', 0);
        if (!$name) {
            return $this->sendAjax('类别名称不能为空!', false);
        }

        $categoryObj = new Model_Cinema_Category();
        //检查同一父类别下是否已经有相同的名称
        $total       = $categoryObj->getCount(array('name' => $name, 'parentId' => $parentId));
        if ($total > 0) {
            return $this->sendAjax('同一父类别下,已经有相同的名字了!', false);
        }

        $add['name']     = $name;
        $add['parentId'] = $parentId;
        $add['addTime']  = time();
        $result = $categoryObj->insert($add);
        if (!$result) {
            return $this->sendAjax('新增失败!', false);
        }
        return $this->sendAjax('新增成功!');
    }

    /**
     * 处理评语
     *
     * @return json
     **/
    public function editAction()
    {
        Yaf_Dispatcher::getInstance()->disableView();
        if (!$this->getRequest()->isXmlHttpRequest()) {
            die('非法的链接');
        }

        $id   = $this->getIntPost('id', 0); //分类ID
        $name = $this->getTrimedPost('name', '');
        if (!$id || !$name) {
            return $this->sendAjax('参数错误!', false);
        }

        $categoryObj = new Model_Cinema_Category();
        $result      = $categoryObj->update(array('name' => $name), array('id' => $id));
        if (!$result) {
            return $this->sendAjax('修改失败!', false);
        }

        return $this->sendAjax('修改成功!');
    }

    /**
     * 删除作品
     *
     * @return json
     **/
    public function deleteAction()
    {
        Yaf_Dispatcher::getInstance()->disableView();
        if (!$this->getRequest()->isXmlHttpRequest()) {
            die('非法的链接');
        }

        $id = $this->getIntPost('id', 0); //分类ID
        if (!$id) {
            return $this->sendAjax('参数错误!', false);
        }

        //检查是否有对应的产品关联
        $categoryObj = new Model_Cinema_Category();
        $relationObj = new Model_Cinema_RelationOfCategoryAndProduct();
        $total       = $relationObj->getCount(array('categoryId' => $id));
        if ($total) {
            return $this->sendAjax('该分类有对应的产品关联, 请先解除和这些产品的关联!', false);
        }

        try {
            //事务开启
            $connection = $categoryObj->getAdapter()->getDriver()->getConnection();
            $connection->beginTransaction();

            //删除数据库
            $result = $categoryObj->delItemById($id);
            if ($result === false) {
                $connection->rollback();
                return $this->sendAjax('删除该分类失败!', false);
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
}