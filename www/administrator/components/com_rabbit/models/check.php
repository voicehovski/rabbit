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


class RabbitModelCheck extends JModelAdmin
{
	protected $import_data = null;
	protected $error_data = null;
	/*
		Возвращает структуру для импорта, сформированную из csv-данных в методе check. Если метод check обнаружил в csv-данных критические ошибки, возвращает пустую стуруктуру.
		
			Формат структуры:	|групповой_артикул <	|цвет < размер - перечень свойств ключ=значение
								|						|>перечень изображений, флаг_основной
								|>категория, перечень свойств ключ=значение
	*/
	public function getImportData ( $param = array() )
	{
		//$this -> import_data = array ( "Checked 1", "Checked 2" );
		return $this -> import_data;
	}
	
	/*
		Возвращает структуру с инфо об ошибках, найденных в csv-данных в ходе работы метода check. Если метод check не обнаружил в данных ошибки, возвращает пустую стуруктуру.
	*/
	public function getErrorData ( $param = array() )
	{
		//$this -> error_data =  array ( "Error in input 1", "Error in input 2" );
		return $this -> error_data;
	}
	
	/*		Проверка csv-данных на синтаксическую и логическую корректность
	
	@HOW_TO_USE: 
	
	@ACTIONS:
	* Формирует структуру для дальнейшего импорта и/или структуру с инфо об ошибках, доступные посредством методов get***Struct
	
	@RETURN: Статус проверки использующийся в виде check
		0 - порядок
		1 - есть не критичные ошибки
		2 - критичные
		3 - данные не корректры вцелом, например отсутствуют
	
	@PROBLEMS:
	* Статусы ошибок сделаны коряво и децентрализовано
	
	*/
	public function check ( $csv_data ) {
		
		if ( ! class_exists ( 'csvHelper' ) ) require ( JPATH_COMPONENT_ADMINISTRATOR . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'csvh.php' );
		
		try {
			$csv = new csvHelper (
				$csv_data,
				RabbitHelper::$PRODUCT_TABLE_VALIDATOR,
				array ( 'delim' => RabbitHelper::$CSV_DELIMITER, 'encl' => RabbitHelper::$CSV_ENCLOSURE, 'esc' => RabbitHelper::$CSV_ESCAPE )
			);
		} catch ( Exception $e ) {
			// @TODO: Обработать
		}
		 
		$this -> error_data = new Report (  );
		$this -> import_data = new ProductTree (  );

		$error_status = 0;
		
		// Выполняем синтаксическую проверку, результаты вносим в error_data, получаем error_status
		while ( $csv -> hasMoreRows (  ) ) {
			$row = $csv -> getNextRow (  );
			
			if ( $row -> hasError (  ) ) {
				$error_status = $row -> getWorstErrorStatus ( $error_status );
				$this -> error_data -> add ( $row );
			}
			
			$this -> import_data -> add ( $row );
		}
		
		// Выполняем логическую проверку
		$this -> import_data -> check (  );
		
		return $error_status;
	}
	
	public function getTable($type = 'Rabbit', $prefix = 'RabbitTable', $config = array())
	{
		return JTable::getInstance($type, $prefix, $config);
	}
	
	public function getForm ( $param = array(), $loadData = true )
	{
		$form = $this->loadForm(
			'com_rabbit.check',
			'check',
			array(
				'control' => 'jform',
				'load_data' => false
			)
		);
 
		if (empty($form))
		{
			return false;
		}
 
		return $form;
	}
}