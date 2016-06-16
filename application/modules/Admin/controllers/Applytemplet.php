<?php
/**
* 后台制作表单申请模板
*/

include_once(__DIR__.'/Root.php');

class ApplytempletController extends RootController
{
    /**
     * 后台表单模板制作首页
     * 报名表列表
     *
     * @return void
     **/
    public function indexAction()
    {
        //分页
        $page = $this->getIntQuery('page', 1);
        
        //表单名称
        $name = $this->getTrimedQuery('name', '');
        if ($name) {
            $conditions['where'] = array('name' => array('like' => '%'.$name.'%'));    
        }
        $conditions['getCount']  = true;
        $conditions['limit']     = self::LIMIT_DEFAULT;
        $conditions['offset']    = ($page - 1) * self::LIMIT_DEFAULT;

        $templet = new Model_Topic_TopicApplyFormTemplet();
        $list    = $templet->getList($conditions);
        $param['name']     = $name; //查询数据
        $param['list']     = $list['list']; //列表数据
        $param['pageList'] = parent::pageBar($list['count'], self::LIMIT_DEFAULT);
        $shortPager        = new Pagination_Short($list['count'], self::LIMIT_DEFAULT);
        $param['shortList'] = $shortPager->getPagination();
        $this->getView()->assign($param);
    }

    /**
     * 后台表单模板制作新增
     *
     * @return void
     **/
    public function addAction()
    {
        $templet         = new Model_Topic_TopicApplyFormTemplet();
        $aptoticElement  = $templet->getAptoticElement();
        $allowType       = $templet->getAllowType();
        
        $this->getView()->assign(array(
                                        'aptoticElement'  => json_encode($aptoticElement),
                                        'allowType'       => json_encode($allowType)
                                 ));
    }

    /**
     * 后台表单模板制作处理新增
     *
     * @return void
     **/
    public function doAddAction()
    {
        Yaf_Dispatcher::getInstance()->disableView();
        if (!$this->getRequest()->isXmlHttpRequest()) {
            die('非法的链接');
        }

        $templetName = $this->getTrimedPost('templetName');
        $content     = $this->getTrimedPost('content');

        $templet     = new Model_Topic_TopicApplyFormTemplet();
        //检查模板名称是否已经存在
        $checkName = $templet->getCount(array('name' => $templetName));
        if ($checkName) {
            return $this->sendAjax('已经存在该名称的模板,请换个名字', false);
        }

        $param  = array('name' => $templetName, 'content' => $content, 'addTime' => time());
        $result = $templet->insert($param);
        if (!$result) {
            return $this->sendAjax('生成报名表单失败!', false);
        }

        return $this->sendAjax('生成报名表单成功!');
    }

    /**
     * 后台表单模板制作修改
     *
     * @return void
     **/
    public function editAction()
    {
        $id = $this->getIntQuery('id');
        if (!$id) {
            return $this->_error('参数错误!');
        }

        $templet = new Model_Topic_TopicApplyFormTemplet();
        $item    = $templet->getItemById($id);
        if (!$item) {
            return $this->_error('未找到该表单');
        }

        //检查该表单是否已经被使用过了,使用过的不能编辑
        $relation = new Model_Topic_TopicAndAftRelation();
        $total    = $relation->getCount(array('TopicApplyFormTemplet_id' => $id));
        //活动默认的表单是否有使用
        $topicInfo  = new Model_Topic_TopicInfo();
        $totalTopic = $topicInfo->getCount(array('TopicApplyFormTemplet_id' => $id));
        if ($total || $totalTopic) {
            return $this->_error('使用过的报名表单不能再编辑');    
        }
        
        $allowType = $templet->getAllowType();

        $this->getView()->assign(array(
                                        'item'      => $item,
                                        'allowType' => $allowType
                                      )
                                );
    }

    /**
     * 后台表单模板制作处理修改
     *
     * @return void
     **/
    public function doEditAction()
    {
        Yaf_Dispatcher::getInstance()->disableView();
        if (!$this->getRequest()->isXmlHttpRequest()) {
            die('非法的链接');
        }

        $id          = $this->getIntPost('id');
        $templetName = $this->getTrimedPost('templetName', '');
        $content     = $this->getTrimedPost('content', '');
        if (!$id || !$templetName || !$content) {
            return $this->sendAjax('参数错误!', false);
        }

        $templet = new Model_Topic_TopicApplyFormTemplet();
        $item    = $templet->getItemById($id);
        if (!$item) {
            return $this->sendAjax('未找到该表单', false);
        }

        //检查该表单是否已经被使用过了,使用过的不能编辑
        $relation = new Model_Topic_TopicAndAftRelation();
        $total    = $relation->getCount(array('TopicApplyFormTemplet_id' => $id));
        if ($total) {
            return $this->sendAjax('使用过的报名表单不能再编辑', false);    
        }

        $set = array(
                    'name'    => $templetName,
                    'content' => $content
               );
        $result = $templet->update($set, array('id' => $id));
        if ($result === false) {
            return $this->sendAjax('更新报名表单失败!', false);
        }

        return $this->sendAjax('更新报名表单成功!');
    }

    /**
     * 后台表单模板制作删除
     * 
     *
     * @return void
     **/
    public function deleteAction()
    {
        Yaf_Dispatcher::getInstance()->disableView();
        if (!$this->getRequest()->isXmlHttpRequest()) {
            die('非法的链接');
        }

        $ids = $this->getRequest()->getPost('id', 0);
        $ids = is_array($ids) ? $ids : (int) $ids;
        if (!$ids) {
            return $this->sendAjax('参数错误!', false);
        }

        if (is_array($ids)) {
            foreach ($ids as &$value) {
                $value = (int) $value;
                if (!$value) {
                    return $this->sendAjax('参数出错!', false);
                }
            }
            unset($value);
        }

        //检查该表单是否已经被使用过了,使用过的不能编辑
        $relation = new Model_Topic_TopicAndAftRelation();
        $total    = $relation->getCount(array('TopicApplyFormTemplet_id' => $ids));
        if ($total) {
            return $this->sendAjax('使用过的报名表单不能删除!', false);
        }
        
        $templet = new Model_Topic_TopicApplyFormTemplet();
        $result  = $templet->delItems(array('id' => $ids));
        if ($result === false) {
            return $this->sendAjax('删除失败!', false);
        }

        return $this->sendAjax('删除成功!');
    }
}