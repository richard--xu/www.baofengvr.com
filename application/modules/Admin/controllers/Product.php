<?php
/**
* 后台申请列表
*/

include_once(__DIR__.'/Root.php');

class ProductController extends RootController
{
    /**
     * 后台表单模板制作首页
     * 报名表列表
     *
     * @return void
     **/
    public function indexAction()
    {

        /**********************************获取参数******************************/
        //分页
        $page       = $this->getIntQuery('page', 1);
        $pCid       = $this->getIntQuery('pCid', 0); //父类别
        $cCid       = $this->getIntQuery('cCid', 0); //子类别

        $param['pCid']       = $pCid;
        $param['cCid']       = $cCid;
        /*********************************获取参数 END***************************/
        $condition     = array();
        $modelCategory = new Model_Cinema_Category();
        if ($pCid || $cCid) {
            if (!$cCid) {
                $childCategorys = $modelCategory->selectWith(array('parentId' => $pCid));
                if ($childCategorys) {
                    $cCid = Common::getColumnArray($childCategorys, 'id');
                }
            }

            if ($cCid) {
                $conditions['category'] = true;
                /*$conditions['joinWhere']  = 'relationOfCategoryAndProduct.categoryId';
                $conditions['joinWhere'] .= is_array($cCid) ? ' IN ('. join(',', $cCid). ')'
                                                            : ' = '. $cCid;*/
                $conditions['where']    = array('relationOfCategoryAndProduct.categoryId' => 
                                                is_array($cCid) ? array('in' => $cCid) : $cCid
                                               );
                $conditions['group']    = 'product.id';
            }
        }

        //$conditions['getSql'] = true;
        $conditions['getCount'] = true;
        $conditions['limit']    = self::LIMIT_DEFAULT;
        $conditions['offset']   = ($page - 1) * self::LIMIT_DEFAULT;
        $modelProduct = new Model_Cinema_Product();
        $productItems = $modelProduct->getProducts($conditions);
        //print_r($productItems);
        $pids         = Common::getColumnArray($productItems['list'], 'id');

        //获取所有分类
        $categoryItems     = $modelCategory->selectWith(array(), array('id', 'name', 'parentId'));
        $categoryList      = $modelCategory->_dealClasses($categoryItems);
        $param['category'] = Common::convertArrBycolumn($categoryList, 'id');
        $param['categoryNotSort'] = Common::convertArrBycolumn($categoryItems, 'id');

        //获取产品分类
        $relationObj  = new Model_Cinema_RelationOfCategoryAndProduct();
        $items        = $relationObj->getList(array('productId' => array('in' => $pids)));
        $relationList = Common::convertToOneArrayBycolumn($items['list'], 'productId');

        $param['list']         = Common::convertArrBycolumn($productItems['list'], 'id');
        $param['relationList'] = $relationList;
        $param['pageList']     = parent::pageBar($productItems['count'], self::LIMIT_DEFAULT);
        $this->getView()->assign($param);
    }

    /**
     * 新增作品
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
            $productObj  = new Model_Cinema_Product();
            $connection = $productObj->getAdapter()->getDriver()->getConnection();
            $connection->beginTransaction();

            //插入活动数据
            $add['data']['addTime']    = time();
            $add['data']['updateTime'] = time();
            $insertId = $productObj->save($add['data']);
            if (!$insertId) {
                $connection->rollback();
                return $this->sendAjax('新增作品失败!', false);
            }

            if ($add['classes']) {
                $columns = array('productId', 'categoryId', 'addTime');
                $items   = array();
                foreach ($add['classes'] as $key => $value) {
                    $items[] = array($insertId, $value, time());
                }

                $relationObj = new Model_Cinema_RelationOfCategoryAndProduct();
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
     * 更新作品
     *
     * @return json
     **/
    public function editAction()
    {

        Yaf_Dispatcher::getInstance()->disableView();
        if (!$this->getRequest()->isXmlHttpRequest()) {
            die('非法的链接');
        }

        if (!isset($_SESSION['isAdmin']) || !in_array($_SESSION['isAdmin'], array(1))) {
            return $this->sendAjax('您沒有編輯作品的權限', false);
        }

        //活动ID
        $id = $this->getIntPost('id', 0);
        if (!$id) {
            return $this->sendAjax('参数错误', false);
        }
        //活动信息
        $productObj = new Model_Cinema_Product();
        $item       = $productObj->getItemById($id);
        if (!$item) {
            return $this->sendAjax('未找到该作品', false);
        }

        //处理提交的数据
        $edit = $this->_dealPostData();
        if (!$edit['status']) {
            return $this->sendAjax($edit['data'], false);    
        }

        try{
            $connection = $productObj->getAdapter()->getDriver()->getConnection();
            $connection->beginTransaction();
            $result = $productObj->update($edit['data'], array('id' => $id));
            if ($result === false) {
                $connection->rollback();
                return $this->sendAjax('更新数据失败', false);
            }

            $relationObj   = new Model_Cinema_RelationOfCategoryAndProduct();
            $relationItems = $relationObj->selectWith(array('productId' => $id), 
                                                       array('categoryId')
                                            );
            $cids = Common::getColumnArray($relationItems, 'categoryId');
            if (array_diff($edit['classes'], $cids) != array_diff($cids, $edit['classes'])) {
                //删除以前的
                $result = $relationObj->delItems(array('productId' => $id));
                if ($result === false) {
                    $connection->rollback();
                    return $this->sendAjax('更新失败,请稍后重试!', false);
                }
                
                //新增数据
                $columns = array('productId', 'categoryId', 'addTime');
                $items   = array();
                foreach ($edit['classes'] as $key => $value) {
                    $items[] = array($id, $value, time());
                }

                $result = $relationObj->insertMore($columns, $items);
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
            return $this->sendAjax('修改失败!', false);
        }

        return $this->sendAjax('活动更新成功!');
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

        if (!isset($_SESSION['isAdmin']) || !in_array($_SESSION['isAdmin'], array(1))) {
            return $this->sendAjax('您沒有刪除作品的權限', false);
        }

        $id = $this->getIntPost('id', 0); //产品ID
        if (!$id) {
            return $this->sendAjax('参数错误!', false);
        }

        $productObj       = new Model_Cinema_Product();
        $relationObj      = new Model_Cinema_RelationOfCategoryAndProduct();
        $relationTopicObj = new Model_Cinema_RelationOfProductAndTopic();

        try {
            //事务开启
            $connection = $productObj->getAdapter()->getDriver()->getConnection();
            $connection->beginTransaction();

            //删除作品
            $result = $productObj->delItemById($id);
            if ($result === false) {
                $connection->rollback();
                return $this->sendAjax('删除作品失败!', false);
            }

            //删除分类关系
            $result = $relationObj->delItems(array('productId' => $id));
            if ($result === false) {
                $connection->rollback();
                return $this->sendAjax('删除作品分类失败!', false);
            }

            //删除专题关系
            $result = $relationTopicObj->delItems(array('productId' => $id));
            if ($result === false) {
                $connection->rollback();
                return $this->sendAjax('删除关联专题失败!', false);
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
     * 删除作品
     *
     * @return json
     **/
    public function getAllProductAction()
    {
        Yaf_Dispatcher::getInstance()->disableView();
        if (!$this->getRequest()->isXmlHttpRequest()) {
            die('非法的链接');
        }

        $conditions = array();
        $cid = $this->getIntPost('cid', 0); //类别ID
        if ($cid) {
            $modelCategory = new Model_Cinema_Category();
            $childs        = $modelCategory->selectWith(array('parentId' => $cid));
            if (!$childs) {
                return $this->sendAjax(array());
            }

            $cids                   = Common::getColumnArray($childs, 'id');
            $conditions['category'] = true;
            $conditions['where']    = array('relationOfCategoryAndProduct.categoryId' => 
                                            array('in' => $cids)
                                      );
        }

        //$conditions['getSql'] = true;
        $modelProduct = new Model_Cinema_Product();
        $productItems = $modelProduct->getProducts($conditions);

        return $this->sendAjax(Common::convertArrBycolumn($productItems['list'], 'id'));
    }

    /**
     * 处理新增活动或者修改活动提交的数据
     *
     * @return array
     **/
    private function _dealPostData()
    {
        //获取post值
        $title    = $this->getTrimedPost('title', ''); //标题
        $desc     = $this->getTrimedPost('description', ''); //描述
        //图片处理(小缩略图(1张)||大缩略图(数张))
        $imgSmall = $this->getTrimedPost('small', ''); //封面图
        $imgbig   = $this->getRequest()->getPost('big', array());
        //产品处理
        $filePath = $this->getTrimedPost('filePath', '');
        //产品分类处理
        $classes  = $this->getRequest()->getPost('classes', array());
        //判断不能为空的值
        if (!$title || !$desc || !$imgSmall || !$imgbig || !$filePath || !$classes) {
            return array('status' => false, 'data' => '请先填写完整, 谢谢!');
        }

        //检测长度
        $titleLen = Common::strLength($title, 'utf8');
        if ($titleLen > 100) {
            return array('status' => false, 'data' => '产品名称不能超过100个字, 谢谢!');
        }

        //检查作品是否存在
        if (!file_exists(PUB_PATH.$filePath)) {
            return array('status' => false, 'data' => '作品不存在请重新上传!');
        }
        $fileSize = filesize(PUB_PATH.$filePath);

        //为更新准备值
        $data['title']       = $title;
        $data['description'] = $desc;
        $data['pic']         = json_encode(array('small' => $imgSmall, 'big' => $imgbig));
        $data['filePath']    = $filePath;
        $data['fileSize']    = $fileSize;

        return array('status' => true, 'data' => $data, 'classes' => $classes);
    }
}