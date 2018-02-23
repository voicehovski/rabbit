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
		
		if ( ! class_exists ( 'DBHelper' ) ) require ( JPATH_COMPONENT_ADMINISTRATOR . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'dbh.php' );
		
		$this->form = $this->get('Form');
		
		$app = JFactory::getApplication();
		
		// @NOTE: Чтобы можно было передавать через сессию ОБЪЕКТЫ, в основном файле компонента перезапускаем сессию (см. также "Загрузка классов")
		$this -> importData = RabbitHelper::restore_variable ( 'import_data' );
		
		$model = $this -> getModel ( 'import' );
		$this -> import_status = $model -> import ( $this -> importData );
		$this -> import_report = $this->get('ImportReport');
		
		
		switch ( $this -> import_status ) {
/*$app -> redirect(JRoute::_('index.php?option=com_rabbit&view=error', false), "Optional comment" )*/;
			case 1:
				echo $this -> import_report ['error'];
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
				JToolBarHelper::custom('rabbit.rollback', null, null, "ROLLBACK [new import]", false);
				JToolBarHelper::custom('rabbit.close', null, null, "EXIT [finish import]", false);
				break;
			case 0:
				JToolBarHelper::custom('rabbit.autotranslate', null, null, "AUTOTRANSLATE", false);
				JToolBarHelper::custom('rabbit.translate', null, null, "CONTINUE [translate form]", false);
				JToolBarHelper::custom('rabbit.rollback', null, null, "ROLLBACK [new import]", false);
				JToolBarHelper::custom('rabbit', null, null, "ONE MORE [new import]", false);
				JToolBarHelper::custom('rabbit.close', null, null, "EXIT [finish import]", false);
				break;
			default:
				return false;
		}
	}
}