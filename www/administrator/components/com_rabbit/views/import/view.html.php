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
	protected $import_status = null;

	public function display($tpl = null)
	{
		$this->form = $this->get('Form');
		$this->import_report = $this->get('ImportReport');
		
		$app = JFactory::getApplication();
		
		$this -> import_struct = RabbitHelper::restore_variable ( 'import_struct' );
		
		$model = $this -> getModel ( 'import' );
		$this -> import_status = $model -> import ( $this -> import_struct );
		
		
		switch ( $this -> import_status ) {
/*$app -> redirect(JRoute::_('index.php?option=com_rabbit&view=error', false), "Optional comment" )*/;
			case 1:
				$this -> setLayout ( "error" );
				break;
			case 0:
				break;
			default:
				JError::raiseError(500, "Unknown import import_status: " . $this -> import_status);
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
		
		switch ( $this -> import_status ) {
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