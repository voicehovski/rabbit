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

/*		Приём данных импорта - таблиц и изображений
	
	@HOW_TO_USE: 
	
	@ACTIONS:
	* Сохраняет загруженные файлы во временный каталог
	* Сохраняет имена в сессии
	* Если файлы не загружены, делает редирект на страницу ошибки
	
	@PROBLEMS:
	* Путь сохранения временных файлов лучше вынести в общий конфиг
*/	
	public function check ( $cachable = false, $urlparams = false ) {
		
		$TMP = JPATH_SITE . '/tmp/';
		
		$input = JFactory::getApplication (  ) -> input;
		$uploaded_files = $input->files->get('jform', null, 'raw');
		$hasFiles = false;
		
		jimport('joomla.filesystem.file');
		
		// Загружаем файлы во временный каталог и сохраняем их имена в сессии
		if ( $uploaded_files ['images'] ) {
			$uploaded_images = array (  );
			foreach ( $uploaded_files ['images'] as $image ) {
				JFile::upload (
					$image ['tmp_name'],
					$TMP . $image [ 'name' ],
					false, true
				);
				$uploaded_images [] = $image [ 'name' ];
			}
			
			RabbitHelper::save_variable ( 'uploaded_images', $uploaded_images );
			$hasFiles = true;
		}
		
		if ( $uploaded_files ['import_table'] ) {
			JFile::upload (
				$uploaded_files [ 'import_table' ] ['tmp_name'],
				$TMP . $uploaded_files [ 'import_table' ] [ 'name' ],
				false, true
			);
			
			RabbitHelper::save_variable ( 'uploaded_table', $uploaded_files [ 'import_table' ] [ 'name' ] );
			$hasFiles = true;
		}
		
		// В случае отсутствия файлов - редирект на страницу ошибки
		if ( $hasFiles ) {
			$this->setRedirect(JRoute::_('index.php?option=com_rabbit&view=check', false) );
		} else {
			$this->setRedirect(JRoute::_('index.php?option=com_rabbit&view=check&layout=no_files_passed', false) );
		}
	}

	
/*		Предыдущая версия приёма данных импорта - таблиц и изображений
	
	@HOW_TO_USE: Чтобы функция нормально отработала, она должна быть вызвана в запросе который загружает файлы
	
	@ACTIONS:
	* Сохраняет загруженные файлы во временный каталог
	* Проверяет сохранились ли файлы
	* Передаёт имена загруженных файлов в запросе виду check
	
*/
	public function check_old ( $cachable = false, $urlparams = false ) {		
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