<?php
defined('_JEXEC') or die;

jimport('joomla.form.helper');
JFormHelper::loadFieldClass('list');

class JFormFieldCommodity extends JFormFieldList
{
	protected $type = 'Regency';

	protected function getCommodities($all = false, $prepare = false) {
		// Get a db connection.
		$db = JFactory::getDBO();

		// Create a new query object.
		$query = $db->getQuery(true);

		$query->select($db->quoteName(array('a.id', 'a.category_id')));
		if($all) {
			$query->select($db->quoteName('a.name'));
		} else {
			$query->select('CONCAT('.$db->quoteName('a.name').', " (",'.$db->quoteName('a.denomination').', ")") name');
		}
		$query->from($db->quoteName('#__gtsafetravel_ref_commodities', 'a'));
		
		$query->where($db->quoteName('a.published') . ' = 1');

		$db->setQuery($query);
		$raw = $db->loadObjectList('id');
		if($prepare) {
			$data = array();
			foreach ($raw as $item) {
				$data[$item->category_id][$item->id] = $item->name;
			}
		} else {
			$data = $raw;
		}
		
		//echo "<pre>"; print_r($data); echo "</pre>";
		//echo nl2br(str_replace('#__','eburo_',$query));
		return $data;
	}

	protected function getCategories() {
		// Get a db connection.
		$db = JFactory::getDBO();

		// Create a new query object.
		$query = $db->getQuery(true);

		$query->select($db->quoteName(array('a.id', 'a.parent_id', 'a.name')));
		$query->from($db->quoteName('#__gtsafetravel_ref_categories', 'a'));
		
		$query->where($db->quoteName('a.published') . ' = 1');
		
		$db->setQuery($query);
		$raw = $db->loadObjectList();
		$data = array();
		foreach ($raw as $item) {
			$data[$item->parent_id][$item->id] = $item->name;
		}
		return $data;
	}

	protected function getOptions() {
		$this->value = is_numeric($this->value) ? array($this->value) : JArrayHelper::fromObject($this->value);
		$this->value = $this->value ? $this->value : array(0);

		$commodities	= $this->getCommodities(true, true);
		$categories		= $this->getCategories();
		$items			= GTHelperHtml::setCommodities($categories[0], $categories, $commodities, true);
		if ($items) {
			foreach ($items as $item) {
				$options[] = JHtml::_('select.option', $item->value, $item->text);
			}
		}
		
		// Merge any additional options in the XML definition.
		$options	= array_merge(parent::getOptions(), $options);

		return $options;
	}
}
?>
