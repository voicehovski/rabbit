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
	protected $data = null;
	protected $errors = null;
	/*
		Возвращает структуру для импорта, сформированную из csv-данных в методе check. Если метод check обнаружил в csv-данных критические ошибки, возвращает пустую стуруктуру.
		
			Формат структуры:	|групповой_артикул <	|цвет < размер - перечень свойств ключ=значение
								|						|>перечень изображений, флаг_основной
								|>категория, перечень свойств ключ=значение
	*/
	public function getImportStruct ( $param = array() )
	{
		return $this -> data;
		//return array ( "Checked 1", "Checked 2" );
	}
	
	/*
		Возвращает структуру с инфо об ошибках, найденных в csv-данных в ходе работы метода check. Если метод check не обнаружил в данных ошибки, возвращает пустую стуруктуру.
	*/
	public function getErrorStruct ( $param = array() )
	{
		$this -> errors =  array ( "Error in input 1", "Error in input 2" );
		return $errors;
	}
	
	/*
			Выполняет проверку csv-данных на синтаксическую и логическую корректность
		и формирует структуру для дальнейшего импорта и/или структуру с инфо об ошибках,
		доступные посредством методов get***Struct
			Возвращает статус проверки: 0 - порядок, 1 - есть не критичные ошибки, 2 - 
		критичные, 3 - данные не корректры вцелом, например отсутствуют
	*/
	public function check ( $csv_data ) {
		
		$status = 0;
		
		//Входные данные должны быть массивом строк
		if ( ! is_array ( $csv_data ) ) {
			//$this -> errors = type of $csv_data, "input is not array";
			return 3;
		}
		
		//Массив должен содержать хоть что-то
		if ( ! count ( $csv_data ) ) {
			//$this -> errors = type of $csv_data, "input is empty";
			return 3;
		}
		
		//Пропускаем пустые строки, если такие есть
		//! Заголовки нужно проверить на валидность
		$index = -1;
		while ( ! $csv_data [++$index] ) {
			continue;
		}
			
		$headers = str_getcsv ( $csv_data [$index], RabbitHelper::$CSV_DELIMITER, RabbitHelper::$CSV_ENCLOSURE, RabbitHelper::$CSV_ESCAPE );
		$header_indexes = RabbitHelper::create_header_indexes ( $headers );
		
		//Обрабатываем данные
		for ( ; $index < count ( $csv_data ); $index++ ) {
			$row = str_getcsv ( $csv_data [$index], RabbitHelper::$CSV_DELIMITER, RabbitHelper::$CSV_ENCLOSURE, RabbitHelper::$CSV_ESCAPE );
			
			//Проверяем строку по размеру и соответствию регулярным варажениям
			/*
				Проверить артикул
				Выделить части артикула: база, цвет, размер. Разные форматы для разных категорий. В пределах категори могут быть исключения. Формат может вообще не содержать сведений о товаре.
				Некоторые поля содержат списки, разделенные запятой.
			*/
			foreach ( $header_indexes as $header_key => $header_data ) {
				if ( preg_match ( $header_data ['pattern'], $row [$header_data ['index']], $matches ) ) {
					if ( $header_key == 'sku' ) {
						echo implode ( "::", $matches );
						//if ( $this -> data [] ) = $row;
					}
				} else {
					//Данные в ячейке не корректны. Записываем в ошибки
					$status = $header_data ['error_status'];
				}
			}
			
			//$base_sku = getBaseSku ( $row );
			//Найти в элемент с соответствующим полем base_sku, если такого нет - создать
			$this -> data [] = $row;
		}
		
		return 0;
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