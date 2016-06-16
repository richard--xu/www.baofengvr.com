<?php
/**
* 分类
*/

include_once(__DIR__.'/Root.php');

class AdvertisementController extends RootController
{
    /**
     * 分类表
     *
     * @return void
     **/
    public function indexAction()
    {
        $advertObj       = new Model_Cinema_Advertisement();
        $param['advert'] = Common::convertArrBycolumn($advertObj->selectWith(), 'id');

        //获取所有分类
        $categoryObj       = new Model_Cinema_Category();
        $categoryItems     = $categoryObj->selectWith(array(), array('id', 'name', 'parentId'));
        $categoryList      = $categoryObj->_dealClasses($categoryItems);
        $param['category'] = Common::convertArrBycolumn($categoryList, 'id');

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
        $add = $this->_dealPostData();
        if (!$add['status']) {
            return $this->sendAjax($add['data'], false);    
        }

        $advertObj                 = new Model_Cinema_Advertisement();
        $add['data']['updateTime'] = time();
        $add['data']['addTime']    = time();
        $result                    = $advertObj->insert($add['data']);
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

        $id = $this->getIntPost('id', 0);
        if (!$id) {
            return $this->sendAjax('参数错误!', false);
        }

        //获取参数
        $edit = $this->_dealPostData();
        if (!$edit['status']) {
            return $this->sendAjax($edit['data'], false);    
        }
        
        $advertObj                  = new Model_Cinema_Advertisement();
        $edit['data']['updateTime'] = time();
        $result                     = $advertObj->update($edit['data'], array('id' => $id));
        if ($result === false) {
            return $this->sendAjax('修改失败', false);
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

        $advertObj = new Model_Cinema_Advertisement();
        $result    = $advertObj->delItemById($id);
        if ($result === false) {
            return $this->sendAjax('删除该分类失败!', false);
        }
        
        return $this->sendAjax('删除成功!');
    }

    /**
     * 处理新增活动或者修改活动提交的数据
     *
     * @return array
     **/
    private function _dealPostData()
    {
        //获取post值
        $pic      = $this->getTrimedPost('pic', '');
        $link     = $this->getTrimedPost('link', '');
        $location = $this->getTrimedPost('location', 0);
        $sequence = $this->getTrimedPost('sequence', 0);
        //判断不能为空的值
        if (!$pic || !$link || !$location) {
            return array('status' => false, 'data' => '请先填写完整, 谢谢!');
        }

        //为更新准备值
        $data['pic']      = $pic;
        $data['link']     = $link;
        $data['location'] = $location;
        $data['sequence'] = $sequence;

        return array('status' => true, 'data' => $data);
    }
}