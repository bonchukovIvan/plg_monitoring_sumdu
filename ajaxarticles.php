<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Query\QueryFactory;

class plgAjaxAjaxarticles extends JPlugin
{
   public function onAjaxAjaxarticles()
   {
      $data = array();

      $news_alias       = str_replace(' ', '', $this->params->get('news'));
      $events_alias     = str_replace(' ', '', $this->params->get('events'));
      $eng_news_alias   = str_replace(' ', '', $this->params->get('eng_news'));
      $eng_events_alias = str_replace(' ', '', $this->params->get('eng_events'));

      $start_date = isset($_GET['custom_date'])
         ? date( 'Y-m-d', strtotime($_GET['custom_date']) )
         : date( 'Y-m-d', strtotime('first day of january this year') );

      $data['setup_info'] = [
         'news_alias' => $news_alias, 
         'events_alias'=> $events_alias,

         'eng_news_alias' => $eng_news_alias, 
         'eng_events_alias'=> $eng_events_alias,

         'start_date' => $start_date,
      ];

      $data['news'] = $this->getArticles($news_alias, $start_date);
      $data['events'] = $this->getArticles($events_alias, $start_date);
      $data['eng_news'] = $this->getArticles($eng_news_alias, $start_date);
      $data['eng_events'] = $this->getArticles($eng_events_alias, $start_date);

      return $data;
   }

   private function getArticles($categoryAlias, $startPeriod)
   {
      if(!$categoryAlias) return ['error' => 'category_not_set'];

      $categoryId = $this->getCategoryIdFromAlias($categoryAlias);

      if(!$categoryId) return ['error' => 'category_not_found'];

      $categoryIds = $this->getDescendantCategoryIds($categoryId);

      $db = JFactory::getDbo();
      $query = $db->getQuery(true);
      $query->select($db->quoteName(array('id', 'title', 'alias', 'created')));
      $query->from($db->quoteName('#__content'));
      $query->where($db->quoteName('state') . ' = 1');
      $query->where($db->quoteName('catid') . ' IN (' . implode(',', $categoryIds) . ')');
      $query->where($db->quoteName('publish_up') . ' >= ' . $db->quote($startPeriod));
      $query->order($db->quoteName('publish_up') . ' DESC');
      $db->setQuery($query, 0, -1);
      
      return $db->loadAssocList() != null ? $db->loadAssocList() : ['error' => 'posts_not_found'];
   }

   private function getCategoryIdFromAlias($categoryAlias)
   {
      $db = Factory::getDbo();
      $query = $db->getQuery(true);
      $query->select($db->quoteName('id'));
      $query->from($db->quoteName('#__categories'));
      $query->where($db->quoteName('alias') . ' = ' . $db->quote($categoryAlias));
      $db->setQuery($query);
      $categoryId = $db->loadResult();

      return $categoryId;
   }

   private function getDescendantCategoryIds($parentId)
   {
      $db = Factory::getDbo();
      $query = $db->getQuery(true);
      $query->select($db->quoteName('id'));
      $query->from($db->quoteName('#__categories'));
      $query->where($db->quoteName('parent_id') . ' = ' . (int) $parentId);
      $db->setQuery($query);
      $categoryIds = $db->loadColumn();

      $allCategoryIds = array($parentId);
      foreach ($categoryIds as $categoryId) {
         $subCategoryIds = $this->getDescendantCategoryIds($categoryId);
         $allCategoryIds = array_merge($allCategoryIds, $subCategoryIds);
      }

      return $allCategoryIds;
   }
}