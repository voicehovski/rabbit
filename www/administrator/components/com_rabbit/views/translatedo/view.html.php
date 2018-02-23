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

class RabbitViewTranslateDo extends JViewLegacy
{
	//protected $form = null;
	protected $check_status = null;

	public function display($tpl = null)
	{
		if ( ! class_exists ( 'csvHelper' ) ) require_once ( JPATH_COMPONENT_ADMINISTRATOR . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'csvh.php' );
		
		$translate_type = RabbitHelper::restore_variable ( 'translate_type' );
		$language = RabbitHelper::restore_variable ( 'language' );
		$content_type = RabbitHelper::restore_variable ( 'content_type' );
		$translate_data = RabbitHelper::restore_variable ( 'translate_data' );
		$translate_metadata = RabbitHelper::restore_variable ( 'translate_metadata' );
		
		$this->form = $this->get('Form');
		
		$model = $this -> getModel ( "translatedo" );
		
		$this -> check_status = $model -> translate ( $translate_data, $translate_type, $language, $translate_metadata );
		
		$this->import_result = $this -> get ( 'TranslateResult' );
 
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
		JToolBarHelper::title($title, 'Translate result');
		
		//JToolBarHelper::custom('rabbit.translatecheck', null, null, "TRANSLATE", false);
		JToolBarHelper::custom('rabbit.rollback', null, null, "ROLLBACK [new import]", false);
		JToolBarHelper::custom('rabbit', null, null, "ONE MORE [new import]", false);
		JToolBarHelper::custom('rabbit.close', null, null, "EXIT [finish import]", false);

	}
}