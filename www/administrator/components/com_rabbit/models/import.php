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


class RabbitModelImport extends JModelAdmin
{
	
	protected $import_report = array (  );
	
	public function getImportReport ( $param = array() )
	{
		
		$this -> import_report ['modified'] = array ( "modified-1", "modified-2", "modified-3" );
		$this -> import_report ['added'] = array ( "added-1", "added-2", "added-3", "added-4" );
		$this -> import_report ['deleted'] = array ( "deleted-1" );
		
		return $this -> import_report;
	}
	
	public function getTable($type = 'Rabbit', $prefix = 'RabbitTable', $config = array())
	{
		return JTable::getInstance($type, $prefix, $config);
	}
	
	public function getForm ( $param = array(), $loadData = true )
	{
		$form = $this->loadForm(
			'com_rabbit.import',
			'import',
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
	
	/*
			Выполняет запись продукции в базу данных
			
			Возвращает статус выполнения
	*/
	public function import ( $importData ) {
		
		$exit_status = 0;
		
		// @TODO: Здесь выполняем запись в базу данных. Устанавливаем статус результата. Записываем информацию в отчеты
		try {
			DBHelper::import ( $importData );
		} catch ( Exception $e ) {
			// @TODO: перенаправление на страницу ошибки
			$this -> import_report ['error'] = $e -> getMessage (  );
			$exit_status = 1;
		}
		
		return $exit_status;
	}
}