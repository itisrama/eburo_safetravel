<?php
defined('_JEXEC') or die;

jimport('joomla.form.helper');
JFormHelper::loadFieldClass('list');

class JFormFieldSeller extends JFormFieldList
{
	protected $type = 'Seller';

	protected function getOptions() {
		$this->value = is_numeric($this->value) ? array($this->value) : JArrayHelper::fromObject($this->value);
		$this->value = $this->value ? $this->value : array(0);
		
		$user		= JFactory::getUser();
		$profile	= JUserHelper::getProfile($user->id);
		$region_id	= (array) @$profile->region_id;
		$region_id	= $region_id ? $region_id : $this->form->getValue('region_id',  0);

		// Get a db connection.
		$db = JFactory::getDBO();

		// Create a new query object.
		$query = $db->getQuery(true);

		$query->select($db->quoteName(array('a.id')));
		$query->select('CONCAT('.$db->quoteName('c.name').'," - ",'.$db->quoteName('a.name').') name');
		$query->from($db->quoteName('#__gtsafetravel_ref_sellers', 'a'));
		$query->join('INNER', $db->quoteName('#__gtsafetravel_ref_regions', 'b').' ON '.
			'FIND_IN_SET('.$db->quoteName('a.regency_id').', '.$db->quoteName('b.regency_ids').')'
		);
		$query->join('INNER', $db->quoteName('#__gtsafetravel_ref_markets', 'c').' ON '.
			'FIND_IN_SET('.$db->quoteName('a.market_id').', '.$db->quoteName('c.id').')'
		);
		

		$query->where($db->quoteName('b.id').' = '.$db->quote(intval($region_id)));
		
		$query->where($db->quoteName('c.price_type_id') . ' = 1');
		$query->where($db->quoteName('a.published') . ' = 1');
		$query->order($db->quoteName('c.name'));
		$query->order($db->quoteName('a.name'));

		//echo nl2br(str_replace('#__','eburo_',$query)); die;
		$db->setQuery($query);
		$options = $db->loadObjectList('id');
		foreach ($options as &$opt) {
			$opt = JHtml::_('select.option', $opt->id, $opt->name);
		};

		// Merge any additional options in the XML definition.
		$options	= array_merge(parent::getOptions(), $options);

		return $options;
	}
}
?>
