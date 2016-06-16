<?php
/**
* 后台申请列表
*/

include_once(__DIR__.'/Root.php');

class UserController extends RootController
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

        /*********************************获取参数 END***************************/
        $condition = array();
        $conditions['getCount'] = true;
        $conditions['limit']    = self::LIMIT_DEFAULT;
        $conditions['offset']   = ($page - 1) * self::LIMIT_DEFAULT;
        $conditions['where']    = array('isAdmin' => 0);
        $modelUser = new Model_Cinema_User();
        $userItems = $modelUser->getList($conditions);

        $param['list']         = $userItems['list'];
        $param['pageList']     = parent::pageBar($userItems['count'], self::LIMIT_DEFAULT);
        $this->getView()->assign($param);
    }
}