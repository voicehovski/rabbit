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

class RabbitViewImport extends JViewLegacy
{
	//protected $form = null;
	protected $check_status = null;

	public function display($tpl = null)
	{
		$this->form = $this->get('Form');
		$this->import_result = $this->get('ImportResult');
		
		$app = JFactory::getApplication();
		$this -> check_status = rand ( 0, 1 );
		
		switch ( $this -> check_status ) {
/*$app -> redirect(JRoute::_('index.php?option=com_rabbit&view=error', false), "Optional comment" )*/;
			case 1:
				$this -> setLayout ( "error" );
				break;
			case 0:
				break;
			default:
				JError::raiseError(500, "Unknown import check_status: " . $this -> check_status);
				return false;
		}
 
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
		JToolBarHelper::title($title, 'import');
		
		$input = JFactory::getApplication()->input;
		$input->set('hidemainmenu', true);
		
		switch ( $this -> check_status ) {
			case 1:
				JToolBarHelper::custom('rabbit.close', null, null, "EXIT", false);
				JToolBarHelper::custom('rabbit', null, null, "CANCEL", false);
				break;
			case 0:
				JToolBarHelper::custom('rabbit.translate', null, null, "CONTINUE [translate]", false);
				break;
			default:
				return false;
		}
	}
}