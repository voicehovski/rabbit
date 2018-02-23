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
	const UNKNOWN_CONTENT_TYPE = '0';
	const AUTO_CONTENT_TYPE = '1';
	const CLOTHES_CONTENT_TYPE = '2';
	const FABRICS_CONTENT_TYPE = '3';
	const TEXTILE_CONTENT_TYPE = '4';
	const SOUVENIRS_CONTENT_TYPE = '5';
	const ACCESSORIES_CONTENT_TYPE = '6';
	
	protected $cellErrors = array (  );
	protected $structuralErrors = array (  );
	
	protected $language = null;	// ru_ru, en_gb
	protected $content_type = null;
	
	//protected $form = null;
	protected $check_status = null;

	public function display ( $tpl = null )	{
		
		if ( ! class_exists ( 'csvHelper' ) ) require_once ( JPATH_COMPONENT_ADMINISTRATOR . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'csvh.php' );
		
		$TMP = JPATH_SITE . '/tmp/';
		$translate_type = RabbitHelper::restore_variable ( 'translate_type' );	//	0,1,2 or null
		$translation_filename = RabbitHelper::restore_variable ( 'translation' );		//	filename or null
		$language = RabbitHelper::restore_variable ( 'language' );		//	filename or null
		$content_type = RabbitHelper::restore_variable ( 'content_type' );
		
		if ( $translate_type == 1 ) {	// Добавочный перевод
			// 
			if ( $translation_filename ) {
				$rawCsv = file ( $TMP . $translation_filename );
				$csv = new Csv ( $rawCsv, array ( 'delim'=>';','encl'=>'','esc'=>'' ) );
				$csvMeta = CsvMetadata::createAdditionalTranslateMetadata ( $csv -> headers (  ) );
				$elements = $this -> does_additional_translate ( $csvMeta, $csv );	// Array of assoc
				$this -> check_status = max ( CellCsvError::worstErrorStatus ( $this -> cellErrors ), StructuralError::worstErrorStatus ( $this -> structuralErrors ) );
			}
		} else if (  $translate_type == 2 ) {	// Полный перевод
		
			if ( $translation_filename ) {
				$rawCsv = file ( $TMP . $translation_filename );
				$csv = new Csv ( $rawCsv, array ( 'delim'=>';','encl'=>'','esc'=>'' ) );
				
				switch ( $content_type ) {
					case self::CLOTHES_CONTENT_TYPE:
						$csvMeta = CsvMetadata::createTranslateMetadata ( $csv -> headers (  ) );
						$elements = $this -> does_translate ( $csvMeta, null, $csv );
						break;
					case self::TEXTILE_CONTENT_TYPE:
						$csvMeta = CsvMetadata::createTranslateMetadata ( $csv -> headers (  ) );
						$elements = $this -> does_translate ( $csvMeta, null, $csv );
						break;
					case self::SOUVENIRS_CONTENT_TYPE:
						$csvMeta = CsvMetadata::createTranslateMetadata ( $csv -> headers (  ) );
						$elements = $this -> does_translate ( $csvMeta, null, $csv );
						break;
					case self::ACCESSORIES_CONTENT_TYPE:
						$csvMeta = CsvMetadata::createTranslateMetadata ( $csv -> headers (  ) );
						$elements = $this -> does_translate ( $csvMeta, null, $csv );
						break;
				}
				
				$this -> check_status = max ( CellCsvError::worstErrorStatus ( $this -> cellErrors ), StructuralError::worstErrorStatus ( $this -> structuralErrors ) );
			}
		}
		
		
		$this->form = $this -> get ( 'Form' );
		
		switch ( $this -> check_status ) {
			case 2:
				$this -> setLayout ( "error" );
				break;
			case 1:
				$this -> setLayout ( "warning" );
				RabbitHelper::save_variable ( 'translate_data', $elements );
				RabbitHelper::save_variable ( 'translate_metadata', $csvMeta );
				break;
			case 0:
				RabbitHelper::save_variable ( 'translate_data', $elements );
				RabbitHelper::save_variable ( 'translate_metadata', $csvMeta );
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
				JToolBarHelper::custom('rabbit.translatedo', null, null, "IGNORE & CONTINUE [translate]", false);
				JToolBarHelper::custom('rabbit.rollback', null, null, "ROLLBACK [new import]", false);
				JToolBarHelper::custom('rabbit.close', null, null, "EXIT [finish import]", false);
				break;
			case 0:
				JToolBarHelper::custom('rabbit.translatedo', null, null, "CONTINUE [translate]", false);
				JToolBarHelper::custom('rabbit.rollback', null, null, "ROLLBACK [new import]", false);
				JToolBarHelper::custom('rabbit.close', null, null, "EXIT [finish import]", false);
				break;
			default:
				return false;
		}
	}
	
	protected function does_additional_translate ( $csvMeta,  $csv ) {
		
		$elements = array (  );
		
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

			$elements [] = $assocRow;
		}
		
		return $elements;
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