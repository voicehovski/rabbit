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
		
		$TMP = JPATH_SITE . DIRECTORY_SEPARATOR . 'tmp';
		
		$input = JFactory::getApplication (  ) -> input;
		$uploaded_files = $input->files->get('jform', null, 'raw');
		$hasFiles = false;
		
		// Если архив
		
		// Если конвертировать на сервере
		$options = $input -> getArray ( array (
				"jform" => array (
					'content_type' => 'string',	//combo
					'convert_images' => 'string',	//checkbox
					'images_packed' => 'string',	//checkbox
					'import_options' => 'string',
					'param' => array (
						'import_type' => 'string',	//radio
						'userimport' => 'string'
					)
				)
			) );
		
		RabbitHelper::save_variable ( 'content_type', $options ['jform'] ['content_type'] );
		RabbitHelper::save_variable ( 'convert_images', $options ['jform'] ['convert_images'] );
		RabbitHelper::save_variable ( 'images_packed', $options ['jform'] ['images_packed'] );
		RabbitHelper::save_variable ( 'import_type', $options ['jform'] ['import_type'] );
		
		jimport('joomla.filesystem.file');
		
		// Загружаем файлы во временный каталог и сохраняем их имена в сессии
		
		$uploaded_images = RabbitHelper::upload_images ( $uploaded_files, 'images', $TMP . DIRECTORY_SEPARATOR . 'full' );

		if ( $uploaded_images === null ) {
			echo "Couldn`t load images<br/>";
		} else {
			RabbitHelper::save_variable ( 'uploaded_images', $uploaded_images );
			$hasFiles = true;
		}
		
		$uploaded_medi = RabbitHelper::upload_images ( $uploaded_files, 'medi_images', $TMP . DIRECTORY_SEPARATOR . 'medi' );
		if ( $uploaded_medi === null ) {
			echo "Couldn`t load medi_images<br/>";
		} else {
			RabbitHelper::save_variable ( 'uploaded_medi', $uploaded_medi );
			$hasFiles = true;
		}
		
		$uploaded_mini = RabbitHelper::upload_images ( $uploaded_files, 'mini_images', $TMP . DIRECTORY_SEPARATOR . 'mini' );
		if ( $uploaded_mini === null ) {
			echo "Couldn`t load mini_images<br/>";
		} else {
			RabbitHelper::save_variable ( 'uploaded_mini', $uploaded_mini );
			$hasFiles = true;
		}
		
		if ( $uploaded_files ['import_table'] ) {
			JFile::upload (
				$uploaded_files [ 'import_table' ] ['tmp_name'],
				$TMP . DIRECTORY_SEPARATOR . $uploaded_files [ 'import_table' ] [ 'name' ],
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