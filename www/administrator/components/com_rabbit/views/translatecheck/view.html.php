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
	protected $cellErrors = array (  );
	protected $structuralErrors = array (  );
	
	//protected $form = null;
	protected $check_status = null;

	public function display ( $tpl = null )	{
		
		if ( ! class_exists ( 'csvHelper' ) ) require_once ( JPATH_COMPONENT_ADMINISTRATOR . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'csvh.php' );
		
		$TMP = JPATH_SITE . '/tmp/';
		$translate_type = RabbitHelper::restore_variable ( 'translate_type' );	//	0,1,2 or null
		$ru_table_filename = RabbitHelper::restore_variable ( 'ru_table' );		//	filename or null
		$en_table_filename = RabbitHelper::restore_variable ( 'en_table' );
		
		if ( $ru_table_filename ) {
			$rawCsvRu = file ( $TMP . $ru_table_filename );
			$csvRu = new Csv ( $rawCsvRu, array ( 'delim'=>';','encl'=>'','esc'=>'' ) );
			$csvMetaRu = CsvMetadata::createClothesTranlateRuMetadata ( $csvRu -> headers (  ) );
			$elements = $this -> does_translate ( $csvMetaRu, null, $csvRu );
		}
		
		/*if ( $en_table_filename ) {
			$rawCsvEn = file ( $TMP . $en_table_filename );
			$csvEn = new Csv ( $rawCsvEn, array ( 'delim'=>';','encl'=>'','esc'=>'' ) );
		}*/
		
		$model = $this -> getModel ( "translatecheck" );
		
		$this->form = $this -> get ( 'Form' );
		
		$this -> check_status = $model -> translate ( $elements, $translate_type, 'ru_ru', $csvMetaRu );
		
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
	
	protected function does_translate ( $csvMeta, $product_variant_def, $csv ) {
		
		$elements = new ProductGroup (  );
		
		foreach ( $csv -> data (  ) as $rowIndex => $row ) {	// Каждая строка исходных данных
			
			// Проверяем каждую ячейку регулярным выражением
			// checkCells возвращает массив, поскольку в строке может быть несколько ошибок. Сливаем его с существующим
			$errors = $csvMeta -> checkCells ( $row, $rowIndex );
			if ( ! empty ( $errors ) ) {
				$this -> cellErrors = array_merge ( $this -> cellErrors, $errors );	
				// @PROBLEM: We should catch critical errors like empty sku ... getWorst if >= CRITICAL
			}

			// Формируем ассоциативный массив и фиксируем ошибку если не удалось
			$assocRow = $csvMeta -> createAssoc ( $row );
			if ( empty ( $assocRow ) ) {
				$this -> structuralErrors [] = new StructuralError ( array ( $rowIndex ), '', "Couldn`t create assoc row from csv", 2 );
				continue;
			}

			// @QUESTION: Нужно ли сохранять ассоциативные массивы или они больше не понадобятся?
			// @ATTENTION: В array_merge при совпадающих строковых ключах важна последовательность аргументов
			// Формируем структуру данных для дальнейшего импорта
			$elements -> add ( new ProductData ( $rowIndex, $assocRow ) );
			
		}
		
		return $elements;
	}
}