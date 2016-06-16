<?php
/**
* widget的基类
*
*/
Abstract class Widget_Base
{
    // Yaf view object
    protected $_view;

    //use module
    protected $_module = '';

    // 是否使用view输出
    protected $_isDiaplay = true;

    // view path
    protected $tpl = '';
    
    public function __construct()
    {
        $module = $this->_module ? 'modules' . DS . $this->_module . DS : '';
        $this->_view = new Yaf_View_Simple(LOCAL_LIB.DS . 'Widget' . DS . 'views' . DS . $module . DS);
        $this->run();
        if ($this->_isDiaplay) {
            if (!$this->tpl) {
                $rating = strtolower(trim(strstr(get_class($this), '_'), '_'));
                $this->tpl = join('/', explode('_', $rating));
            }

            $this->_view->display($this->tpl. '.phtml');
        }
    }

    protected function setView($path)
    {
        $this->_view->setScriptPath($path);
    }

    abstract protected function run();
}