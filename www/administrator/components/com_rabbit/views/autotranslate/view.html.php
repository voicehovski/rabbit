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

class RabbitViewAutoTranslate extends JViewLegacy
{
	//protected $form = null;
	protected $check_status = null;

	// Результатом работы будет перевод или файл для доперевода
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
		JToolBarHelper::title($title, 'Translate form');
		
		//JToolBarHelper::custom('rabbit.translatecheck', null, null, "TRANSLATE", false);
		JToolBarHelper::custom('rabbit.rollback', null, null, "ROLLBACK [new import]", false);
		JToolBarHelper::custom('rabbit', null, null, "ONE MORE [new import]", false);
		JToolBarHelper::custom('rabbit.close', null, null, "EXIT [finish import]", false);

	}
}