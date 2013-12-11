<?php

class AW_Blog_Model_Mysql4_Blog_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('blog/blog');
    }

    public function addEnableFilter($status)
    {
        $this->getSelect()->where('main_table.status = ?', $status);
        return $this;
    }

    public function addPresentFilter()
    {
        $this->getSelect()->where('main_table.created_time<=?', now());
        return $this;
    }

    public function addCatFilter($catId)
    {
        $this
            ->getSelect()
            ->join(
                array('cat_table' => $this->getTable('post_cat')), 'main_table.post_id = cat_table.post_id', array()
            )
            ->where('cat_table.cat_id = ?', $catId)
        ;

        return $this;
    }

    public function addCatsFilter($catIds)
    {
        $this
            ->getSelect()
            ->join(
                array('cat_table' => $this->getTable('post_cat')), 'main_table.post_id = cat_table.post_id', array()
            )
            ->where('cat_table.cat_id IN (' . $catIds . ')')
            ->group('cat_table.post_id')
        ;
        return $this;
    }

    public function joinComments()
    {
        $commentCountExpression = new Zend_Db_Expr('COUNT(IF(comments_table.status = 2,comments_table.post_id, NULL))');
        $this
            ->getSelect()
            ->joinLeft(
                array('comments_table' => $this->getTable('blog/comment')),
                'main_table.post_id = comments_table.post_id',
                array('comment_count' => $commentCountExpression)
            )
        ;
        return $this;
    }

    /**
     * Add Filter by store
     *
     * @param int|Mage_Core_Model_Store $store
     *
     * @return Mage_Cms_Model_Mysql4_Page_Collection
     */
    public function addStoreFilter($store = null)
    {
        if ($store === null) {
            $store = Mage::app()->getStore()->getId();
        }
        if (!Mage::app()->isSingleStoreMode()) {
            if ($store instanceof Mage_Core_Model_Store) {
                $store = array($store->getId());
            }

            $this
                ->getSelect()
                ->joinLeft(
                    array('store_table' => $this->getTable('store')),
                    'main_table.post_id = store_table.post_id',
                    array()
                )
                ->where('store_table.store_id in (?)', array(0, $store))
            ;
        }
        return $this;
    }

    public function addTagFilter($tag)
    {
        if ($tag = trim($tag)) {
            $whereString = sprintf(
                "main_table.tags = %s OR main_table.tags LIKE %s OR main_table.tags LIKE %s OR main_table.tags LIKE %s",
                $this->getConnection()->quote($tag), $this->getConnection()->quote($tag . ',%'),
                $this->getConnection()->quote('%,' . $tag), $this->getConnection()->quote('%,' . $tag . ',%')
            );
            $this->getSelect()->where($whereString);
        }
        return $this;
    }
}