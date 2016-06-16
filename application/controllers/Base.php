<?php
/**
* 基类
*
*/
class BaseController extends Yaf_Controller_Abstract
{
    /**
    * module名称
    *
    * @var string
    */
    protected $_module;

    /**
    * controller名称
    *
    * @var string
    */
    protected $_controller;

    /**
    * action名称
    *
    * @var string
    */
    protected $_action;

    /**
    * default page Title
    *
    * @var string
    */
    public $pageTitle  = 'VR';

    /**
    * 暂时用的,由于程序要和老网站代码一起使用
    * 所以为区别老代码链接,统一加上/sites
    *
    * @var string
    */
    protected $_prefix  = '/sites';

    /**
     * 初始化操作
     *
     * @return void
     **/
    public function init()
    {
        $this->_module     = $this->getRequest()->getModuleName();
        $this->_controller = $this->getRequest()->getControllerName();
        $this->_action     = $this->getRequest()->getActionName();
        $this->getView()->assign(array(
                             'pageTitle'     => $this->pageTitle,
                             'curModule'     => $this->_module,
                             'curController' => $this->_controller,
                             'curAction'     => $this->_action,
                          ));
    }

    /**
     * ajax返回信息
     *
     * @param mix $data要返回的数据
     * @param boolen $status 状态
     * @param boolen $json 是否返回json格式
     * @return void
     **/
    public function sendAjax($data , $status = true, $json = true)
    {
        $data = $json ? json_encode(array('status' => $status, 'data' => $data), JSON_UNESCAPED_UNICODE)
                      : $data;
        return $this->getResponse()->setBody($data);
    }

    /**
     * 分页
     *
     * @param int $totalRows数据总数目
     * @param int $limit 每页显示的数目
     * @param array $params 是否返回json格式
     * @return void
     **/
    public static function pageBar($totalRows = 0, $limit = 25, $params = array())
    {
        $Pager = new Pagination($totalRows, $limit, $params);
        return $Pager->getPagination();
    }

    /**
     * 接收 get 参数类型 ?id=1 和 post 类型
     */
    public function getTrimedRequest($name = '', $default = '')
    {
        return trim($this->getRequest()->getRequest($name, $default));
    }

    /**
     * 接收 get 参数类型 ?id=1 和 post 类型
     */
    public function getIntRequest($name = '', $default = '')
    {
        return (int) $this->getRequest()->getRequest($name, $default);
    }

    /**
     * 获取 get 参数 类型 /id/1
     */
    public function getTrimedParam($name = '', $default = '')
    {
        return trim($this->getRequest()->getParam($name, $default));
    }

    /**
     * 获取 get 参数 类型 /id/1
     */
    public function getIntParam($name = '', $default = '')
    {
        return (int) $this->getRequest()->getParam($name, $default);
    }

    /**
     * 接收 post 类型数据
     */
    public function getTrimedPost($name = '', $default = '')
    {
        return trim($this->getRequest()->getPost($name, $default));
    }

    /**
     * 接收 post 类型数据
     */
    public function getIntPost($name = '', $default = '')
    {
        return (int) $this->getRequest()->getPost($name, $default);
    }

    /**
     * 获取 get 参数 类型 ?id=1
     */
    public function getTrimedQuery($name = '', $default = '')
    {
        return trim($this->getRequest()->getQuery($name, $default));
    }

    /**
     * 获取 get 参数 类型 ?id=1
     */
    public function getIntQuery($name = '', $default = '')
    {
        return (int) $this->getRequest()->getQuery($name, $default);
    }

    /**
    * 出错处理(非权限、404之类)
    *
    */
    protected function _error($message = '')
    {
        die('<script type="text/javascript">alert(\'' . $message . '\');history.go(-1)</script>');
    }
    
}