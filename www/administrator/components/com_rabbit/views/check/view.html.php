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

class RabbitViewCheck extends JViewLegacy
{
	//protected $form = null;
	protected $check_status = null;

	public function display($tpl = null)
	{
		$this->form = $this->get('Form');
		$this->import_result = $this->get('ImportResult');
		
		//$this -> check_status = rand ( 0, 2 );
		$this -> check_status = 0;
		
		//$jinput = JFactory::getApplication()->input;
		//$upload_options = $jinput -> get ( 'options', 'option_1=122', 'STR' );
		//$upload_files = $jinput -> files -> get ( 'jform' );
		//var_dump ( $jinput );
		//var_dump ( $jinput -> files );
		//var_dump ( $_POST );
		//var_dump ( $_FILES );
		//var_dump ( $upload_files );
		//echo 'name = ' . $upload_files [ 'import_file' ] [ 'name' ];
		//echo 'type = ' . $upload_files [ 'import_file' ] [ 'type' ];
		//echo 'tmp_name = ' . $upload_files [ 'import_file' ] [ 'tmp_name' ];
		//echo 'error = ' . $upload_files [ 'import_file' ] [ 'error' ];
		//echo 'size = ' . $upload_files [ 'import_file' ] [ 'size' ];
		
		switch ( $this -> check_status ) {
			case 2:
				$this -> setLayout ( "error" );
				break;
			case 1:
				$this -> setLayout ( "warning" );
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
		JToolBarHelper::title($title, 'check');
		
		switch ( $this -> check_status ) {
			case 2:
				JToolBarHelper::custom('rabbit', null, null, "CANCEL", false);
				JToolBarHelper::custom('rabbit.close', null, null, "EXIT", false);
				break;
			case 1:
				JToolBarHelper::custom('rabbit.import', null, null, "IGNORE & CONTINUE [import]", false);
				JToolBarHelper::custom('rabbit', null, null, "CANCEL", false);
				JToolBarHelper::custom('rabbit.close', null, null, "EXIT", false);
				break;
			case 0:
				JToolBarHelper::custom('rabbit.import', null, null, "CONTINUE [import]", false);
				JToolBarHelper::custom('rabbit', null, null, "CANCEL", false);
				JToolBarHelper::custom('rabbit.close', null, null, "EXIT", false);
				break;
			default:
				return false;
		}
	}
}