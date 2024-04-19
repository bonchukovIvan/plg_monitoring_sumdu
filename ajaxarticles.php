<?php
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Query\QueryFactory;
class plgAjaxAjaxarticles extends JPlugin
{
   public function onAjaxAjaxarticles()
   {
      $data = array();

      $news_alias   = $this->params->get('news');
      $events_alias = $this->params->get('events');

      $sixMonthsAgo = date('Y-m-d', strtotime('-6 months'));

      $data['setup_info'] = ['news_alias' => $news_alias, 'events_alias'=> $events_alias];

      $db = JFactory::getDbo();
      $query = $db->getQuery(true);
      $query->select($db->quoteName(array('a.id', 'a.title', 'a.created')));
      $query->from($db->quoteName('#__content', 'a'));
      $query->join('LEFT', $db->quoteName('#__categories', 'c') . ' ON a.catid = c.id');
      $query->where($db->quoteName('a.state') . ' = 1');
      $query->where($db->quoteName('c.alias') . ' = ' . $db->quote($news_alias));
      $query->where($db->quoteName('publish_up') . ' >= ' . $db->quote($sixMonthsAgo));
      $query->order($db->quoteName('a.created') . ' DESC');
      $db->setQuery($query, 0, -1);
      $news = $db->loadAssocList();

      $data['news'] = $news;

      $categoryId = $this->getCategoryIdFromAlias($events_alias);
      $categoryIds = $this->getDescendantCategoryIds($categoryId);
      $query = $db->getQuery(true);
      $query->select($db->quoteName(array('id', 'title', 'alias', 'created')));
      $query->from($db->quoteName('#__content'));
      $query->where($db->quoteName('state') . ' = 1');
      $query->where($db->quoteName('catid') . ' IN (' . implode(',', $categoryIds) . ')');
      $query->where($db->quoteName('publish_up') . ' >= ' . $db->quote($sixMonthsAgo));
      $query->order($db->quoteName('publish_up') . ' DESC');
      $db->setQuery($query, 0, -1);
      $events = $db->loadAssocList();

      $data['events'] = $events;

      return $data;
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