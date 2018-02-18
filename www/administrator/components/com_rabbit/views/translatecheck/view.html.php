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

class RabbitViewTranslateCheck extends JViewLegacy
{
	//protected $form = null;
	protected $check_status = null;

	public function display ( $tpl = null )	{
		
		$TMP = JPATH_SITE . '/tmp/';
		$translate_type = RabbitHelper::restore_variable ( 'translate_type' );	//	0,1,2 or null
		$ru_table_filename = RabbitHelper::restore_variable ( 'ru_table' );		//	filename or null
		$en_table_filename = RabbitHelper::restore_variable ( 'en_table' );
		
		$csvRu = null;
		if ( $ru_table_filename ) {
			$rawCsvRu = file ( $TMP . $ru_table_filename );
			$csvRu = new Csv ( $rawCsvRu, array ( 'delim'=>';','encl'=>'','esc'=>'' ) );
		}
		
		/*if ( $en_table_filename ) {
			$rawCsvEn = file ( $TMP . $en_table_filename );
			$csvEn = new Csv ( $rawCsvEn, array ( 'delim'=>';','encl'=>'','esc'=>'' ) );
		}*/
		
		$model = $this -> getModel ( "translatecheck" );
		
		$this->form = $this -> get ( 'Form' );
		
		$this -> check_status = $model -> translate ( $csvRu, $translate_type, 'ru_ru' );
		
		$this->import_result = $this -> get ( 'TranslateResult' );
		
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
 
		if ( count( $errors = $this -> get ( 'Errors' ) ) ) {
			JError::raiseError ( 500, implode ( '<br />', $errors ) );
 
			return false;
		}
 
		$this -> addToolBar (  );
 
		parent::display ( $tpl );
	}

	protected function addToolBar()
	{
		JToolBarHelper::title($title, 'translate check/result');
		
		switch ( $this -> check_status ) {
			case 2:
				JToolBarHelper::custom('rabbit.rollback', null, null, "ROLLBACK [new import]", false);
				JToolBarHelper::custom('rabbit.close', null, null, "EXIT [finish import]", false);
				break;
			case 1:
				JToolBarHelper::custom('rabbit.dotranslate', null, null, "IGNORE & CONTINUE [translate]", false);
				JToolBarHelper::custom('rabbit.rollback', null, null, "ROLLBACK [new import]", false);
				JToolBarHelper::custom('rabbit.close', null, null, "EXIT [finish import]", false);
				break;
			case 0:
				JToolBarHelper::custom('rabbit', null, null, "ONE MORE [new import]", false);
				JToolBarHelper::custom('rabbit.rollback', null, null, "ROLLBACK [new import]", false);
				JToolBarHelper::custom('rabbit.close', null, null, "EXIT [finish import]", false);
				break;
			default:
				return false;
		}
	}
}