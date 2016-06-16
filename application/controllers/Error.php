<?php
/**
 * 错误或异常处理
 */
class ErrorController extends Yaf_Controller_Abstract
{

    public $notFound = array(
                           YAF_ERR_NOTFOUND_MODULE,
                           YAF_ERR_NOTFOUND_CONTROLLER,
                           YAF_ERR_NOTFOUND_ACTION,
                           YAF_ERR_NOTFOUND_VIEW
                       );

    public function errorAction($exception)
    {
         Yaf_Dispatcher::getInstance()->autoRender(FALSE);

         //找不到文件，返回404
         if (in_array($exception->getCode(), $this->notFound)) {
              return $this->display('404');
         }

         //其他错误
         $this->display('error', array('message' => $exception->getMessage()));
    }

    public function powerAction()
    {
        $this->setViewPath(MODULE_PATH . 'Statisticsmanage' . DS . 'views');
        $this->display('power', array('message' => '尊请的用户您好，您没有操作此功能的权限！'));
        die();
    }


}