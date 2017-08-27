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
		
		//Получаем имя загруженной таблицы импорта
		//См. https://api.joomla.org/cms-3/classes/JInput.html#method_get
		$jinput = JFactory::getApplication()->input;
		$name = $jinput->get('filename');
		
		$model = $this -> getModel ( 'check' );
		
		if ( !class_exists ( 'csvHelper' ) ) require ( JPATH_COMPONENT_ADMINISTRATOR . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'csvh.php' );
		$csv_helper = csvHelper::getInstance (  );
		echo $csv_helper -> hallo (  );
		
		if ( ! $name ) {
			$this -> check_status = 3;
		} else {
			//Читаем данные из таблицы импорта и выполняем проверку.
			$csv_data = file ( JPATH_SITE . '/tmp/' . $name );	//Функция file_get_contents читает файл в одну строку, file - в массив строк
			$this -> check_status = $model -> check ( $csv_data );
		}
		
		//$this -> check_status = rand ( 0, 2 );
		
		switch ( $this -> check_status ) {
			case 3:
				//$this -> message = "No input file, bleat!";
				$this -> setLayout ( "error" );
				break;
			case 2:
				$this->error_struct = $this->get('ErrorStruct');
				$this -> setLayout ( "error" );
				break;
			case 1:
				$this->error_struct = $this->get('ErrorStruct');
				$this -> setLayout ( "warning" );
				break;
			case 0:
				$this->import_struct = $this->get('ImportStruct');
				RabbitHelper::save_variable ( 'import_struct', $this->import_struct );
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