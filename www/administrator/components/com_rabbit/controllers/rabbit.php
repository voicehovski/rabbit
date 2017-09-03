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
 
class RabbitControllerRabbit extends JControllerForm
{
	public function translate ( $cachable = false, $urlparams = false ) {
		$this->setRedirect(JRoute::_('index.php?option=com_rabbit&view=translate', false) );
	}
		
	public function import ( $cachable = false, $urlparams = false ) {
		$this->setRedirect(JRoute::_('index.php?option=com_rabbit&view=import', false) );
	}
	
	public function check ( $cachable = false, $urlparams = false ) {
		// Копируем загруженные файлы во временную директорию
		$files = RabbitHelper::storeUploadedFiles (  );
		$table_filename = "";
		$images = "";
		// Если файлов нет, нужно что-то предпринять
		if ( ! $files ) {
			//Error
			//Redirect
			return;
		}
		// Передаём имена файлов в запросе. Нужно учитывать ограничение длины запроса. Возможно лучше передавать в сессии
		// имя таблицы в переменной
		if ( $files [ 'import_table' ] ) {
			$table_filename = $files [ 'import_table' ] [ 'name' ];
		}
		// а имена изображений - в массиве. См. также http://php.net/http_build_query
		if ( $files ['images'] ) {
			foreach ( $files ['images'] as $image_filename ) {
				$images .= "images[]=".$image_filename['name']."&";
			}
		}
		$this->setRedirect(JRoute::_('index.php?option=com_rabbit&view=check&' . $images . 'table_filename=' . $table_filename, false) );
	}
	
	public function rollback ( $cachable = false, $urlparams = false ) {
		$this->setRedirect(JRoute::_('index.php?option=com_rabbit', false) );
	}
	
	public function close ( $cachable = false, $urlparams = false ) {
		$this->setRedirect(JRoute::_('index.php', false) );
	}
}