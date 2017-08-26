<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_orangeei
 *
 * @copyright   Copyright (C) 2005 - 2015 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
 
// No direct access to this file
defined('_JEXEC') or die('Restricted access');

class RabbitViewRabbit extends JViewLegacy
{
	protected $form = null;

	public function display($tpl = null)
	{
		$this->form = $this->get('Form');
 
		if (count($errors = $this->get('Errors')))
		{
			JError::raiseError(500, implode('<br />', $errors));
 
			return false;
		}
 
		$this->addToolBar();
 
		parent::display($tpl);
	}

	protected function addToolBar()
	{
		JToolBarHelper::title($title, 'rabbit');
		JToolBarHelper::custom('rabbit.check', null, null, "CHECK", false);
		JToolBarHelper::cancel( 'rabbit.cancel', 'JTOOLBAR_CLOSE'	);
	}
}