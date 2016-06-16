<?php
/**
* 后台nav 
*
*/

class Widget_Admin_Left extends Widget_Base
{
    private $right = array('报名模板' => array(1), '活动模板' => array(1), '作品管理' => array(1, 2, 3, 4));

    protected function run()
    {
        $menu = $this->getMenuList();
        foreach ($menu as $key => $value) {
            if (isset($this->right[$key]) && !in_array($adminFlag, $this->right[$key])) {
                unset($menu[$key]);
            }
        }

        $param['menu']    = $menu;
        $param['current'] = $this->getCurrent();
        $this->_view->assign($param);
    }

    /**
    * 待完善
    */
    private function getCurrent()
    {
        $menuConfig = $this->getMenuList();
        $controller = lcfirst(Yaf_Application::app()->getDispatcher()->getRequest()->getControllerName());
        $action     = lcfirst(Yaf_Application::app()->getDispatcher()->getRequest()->getActionName());
        //$url        = '/' . $controller . '/' . $action;
        $url        = '/' . $controller . '/';
        $current    = '';
        foreach ($menuConfig as $key => $value) {
            if (strpos($value['url'], $url)) {
                $current = $key;
                break;
            }
        }

        return $current;
    }
    
    /**
    * 待完善
    */
    private function getMenuList()
    {
        $menuList = isset(Bootstrap::$config['menuList']) ? Bootstrap::$config['menuList'] : array();
        return $menuList;
    }
}