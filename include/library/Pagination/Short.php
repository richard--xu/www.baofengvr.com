<?php
class Pagination_Short extends Pagination_Abstract
{
    ##############################################本分页类似于只有上一页、下一页两个按钮#######################################
    /**
     * 用于组装的templet
     *
     * @return array
     **/
    protected function template()
    {
        return array(
                    'prevStr'    => '<a href="{url}"><button class="btn btn-primary prev-btn icon-wrap"><span class="icon icon-prev"></span></button></a>', //上一页
                    'nextStr'    => '<a href="{url}"><button class="btn btn-primary next-btn icon-wrap"><span>下一页</span> <span class="icon icon-next"></span></button></a>', //下一页
               );
    }

    /**
     * 生成用于分页的页码以及url等
     *
     * @return array
     **/
    protected function components()
    {
        $items      = array();
        array_push($items, array('url' => $this->_currentPage  > 1 ? $this->fullUrl($this->_currentPage -1) : 'javascript:void(0)', 'tempKey' => 'prevStr'));
        array_push($items, array('url' => $this->_currentPage  < $this->_pageTotal ? $this->fullUrl($this->_currentPage + 1) : 'javascript:void(0)', 'tempKey' => 'nextStr'));
        return $items;
    }
}