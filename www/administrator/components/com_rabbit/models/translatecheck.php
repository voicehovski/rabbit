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


class RabbitModelTranslateCheck extends JModelAdmin
{
	public function getTranlateResult ( $param = array() )
	{
		//make import and return it result
		return array ( "Import result 1", "Import result 2" );
	}
	
	public function getTranslate ( $imp ) {
		
		$exit_status = 0;
		$importData = RabbitHelper::restore_variable ( 'import_data' );
		
		// @TODO: Здесь выполняем запись в базу данных. Устанавливаем статус результата. Записываем информацию в отчеты
		try {
			switch ( $importData ['content_type'] ) {
				case self::CLOTHES_CONTENT_TYPE:
					DBHelper::translate ( $importData );
					break;
				case self::FABRICS_CONTENT_TYPE:
					DBHelper::translate ( $importData );
					break;
				case self::TEXTILE_CONTENT_TYPE:
					DBHelper::translate ( $importData );
					break;
				default:
					break;
			}
		} catch ( Exception $e ) {
			// @TODO: перенаправление на страницу ошибки
			$this -> import_report ['error'] = $e -> getMessage (  );
			$exit_status = 1;
		}
		
		return $exit_status;
	}
	
	public function getTable($type = 'Rabbit', $prefix = 'RabbitTable', $config = array())
	{
		return JTable::getInstance($type, $prefix, $config);
	}
	
	public function getForm ( $param = array(), $loadData = true )
	{
		$form = $this->loadForm(
			'com_rabbit.translatecheck',
			'translatecheck',
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