<?php
abstract class Pagination_Abstract
{
    protected $_pageSize            = null;     //每页项目数
    protected $_itemTotal           = null;     //总项目数
    protected $_pageTotal           = null;     //总页数
    protected $_PageName            = "page";   //页面参数名称
    protected $_params              = '';       //自定义参数
    protected $_currentPage       = 0;        //当前页码

    protected $_nextPageString      = "下一页";     //导航栏中前一页显示的字符
    protected $_previousPageString  = "上一页";     //导航栏中后一页显示的字符
    //当前页面两边允许的页码数目 , 例: 1 ... 2 3 4 5 6 7 8 ... 100
    protected $_bothSidesItemsTotal = 3;        //导航栏显示导航总页数

    //当前url除了page参数外的部分
    protected $_pageUrl = '';

    /**
    * 构造函数
    *
    * @param int $itemTotal 数据总数
    * @param int $pageSize  每页显示数据数目
    * @param array $params  要在url里加的参数
    * @return void
    */
    public function __construct($itemTotal = 0, $pageSize = 0, $params = array())
    {
        $this->_itemTotal = (int) $itemTotal;
        $this->_pageSize  = (int) $pageSize;
        $this->_pageTotal = ceil($itemTotal/$pageSize);
        $this->getCurrentPage();

        /***************************生成当前url(除了page参数)****************************/
        $this->_pageUrl = Yaf_Dispatcher::getInstance()->getRequest()->getServer('REQUEST_URI');
        if (strpos($this->_pageUrl, '?')) {
            $this->_pageUrl = substr($this->_pageUrl, 0, strpos($this->_pageUrl, '?'));
        }
        //参数处理
        $params = isset($_GET) && $_GET ? ($_GET + $params) : $params;
        //去掉page参数
        if (isset($params[$this->_PageName])) {
            unset($params[$this->_PageName]);
        }

        if ($params) {
            $this->_pageUrl .= '?';
            foreach ($params as $key => $para) {
                if ($para !== '') {
                    if (is_array($para)) {
                        foreach ($para as $item) {
                            $this->_pageUrl .= $key . '[]=' . $item . '&';
                        }
                    } else {
                        $this->_pageUrl .= $key . '=' . $para . '&';
                    }
                }
            }

            $this->_pageUrl = rtrim($this->_pageUrl, '?');
            $this->_pageUrl = $this->_pageUrl == '?' ? '' : rtrim($this->_pageUrl, '&');
        }

        /***************************生成当前url(除了page参数) END****************************/

    }

    /**
     * 获取当前的page number
     *
     * @return int
     **/
    protected function getCurrentPage()
    {
        if (!$this->_currentPage) {
            $page = (int) Yaf_Dispatcher::getInstance()->getRequest()->getQuery($this->_PageName);

            $this->_currentPage = !$page || $page < 1 ? 1 : ($page > $this->_pageTotal ? $this->_pageTotal : $page) ;    
        }
        return $this->_currentPage;
    }

    /**
     * 组装生成html
     *
     * @return string
     **/
    public function getPagination()
    {
        //获取分页组件
        $pageItems   = $this->components();
        //分页html
        $pageHtml    = '';
        //获取分页模板
        $pageTemplet = $this->template();

        if (is_array($pageItems)) {
            foreach ($pageItems as $key => $value) {
                if (isset($value['tempKey']) && isset($pageTemplet[$value['tempKey']])) {
                    $curTemplate = $pageTemplet[$value['tempKey']];
                    unset($value['tempKey']);
                    $find    = array();
                    $replace = array();
                    //生成替换数组
                    if ($value) {
                        foreach ($value as $k => $val) {
                            $find[]    = '{' . $k . '}';
                            $replace[] = $val;
                        }
                        $curTemplate = str_replace($find, $replace, $curTemplate);
                    }

                    $pageHtml .= $curTemplate;
                }
            }
        }

        //外面包裹的
        if (isset($pageTemplet['outer'])) {
            $pageHtml = str_replace('{inner}', $pageHtml, $pageTemplet['outer']);
        }

        return $pageHtml;
    }

    /**
     * 用于组装的templet
     *
     * @return array
     **/
    abstract protected function template();

    /**
     * 生成用于分页的页码以及url等
     *
     * @return array
     **/
    abstract protected function components();
    
    /**
     * 生成完整url
     *
     * @param int $page 页码
     * @return string
     **/
    protected function fullUrl($page)
    {
        if (!(int)$page) {
            return false;
        }
        return $this->_pageUrl . (strpos($this->_pageUrl, '?') ? '&' : '?') . $this->_PageName . '=' . $page;
    }

}