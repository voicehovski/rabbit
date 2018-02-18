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


class RabbitModelImportSales extends JModelAdmin {
	
	protected $import_report = array (  );
	
	public function getImportReport ( $param = array() ) {
		return $this -> import_report;
	}
	
	public function getTable($type = 'Rabbit', $prefix = 'RabbitTable', $config = array()) {
		return JTable::getInstance($type, $prefix, $config);
	}
	
	public function getForm ( $param = array(), $loadData = true ) {
		$form = $this->loadForm(
			'com_rabbit.importsales',
			'importsales',
			array(
				'control' => 'jform',
				'load_data' => false
			)
		);
 
		if (empty($form)) {
			return false;
		}
 
		return $form;
	}
	
	/*
			Выполняет запись продукции в базу данных
			
			Возвращает статус выполнения
	*/
	public function import ( $importData ) {
		
		$exit_status = 0;
		
		try {
			$result = DBHelper::import_sales ( $importData );
			$this -> import_report ['reuslt'] = $result;
		} catch ( Exception $e ) {
			// @TODO: перенаправление на страницу ошибки
			$this -> import_report ['error'] = $e -> getMessage (  );
			$exit_status = 1;
		}
		
		return $exit_status;
	}
}