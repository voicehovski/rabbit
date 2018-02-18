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
	
	public function translatecheck ( $cachable = false, $urlparams = false ) {
		
		$TMP = JPATH_SITE . DIRECTORY_SEPARATOR . 'tmp';
		
		$input = JFactory::getApplication (  ) -> input;
		
		$uploaded_files = $input -> files -> get ( 'jform', null, 'raw' );
		$hasFiles = false;

		$options = $input -> getArray (
			array (
				"jform" => array (
					'translate_type' => 'string'	//radio
				)
			)
		);
		
		RabbitHelper::save_variable ( 'translate_type', $options ['jform'] ['translate_type'] );
		
		jimport('joomla.filesystem.file');
		
		// Загружаем файлы во временный каталог и сохраняем их имена в сессии. Возвращает массив
		$uploaded_en_table = RabbitHelper::upload_file ( $uploaded_files, 'en_table', $TMP . DIRECTORY_SEPARATOR );

		if ( ! $uploaded_en_table ) {
			echo "Couldn`t load en_table<br/>";
		} else {
			RabbitHelper::save_variable ( 'en_table', $uploaded_en_table );
			$hasFiles = true;
		}
		
		$uploaded_ru_table = RabbitHelper::upload_file ( $uploaded_files, 'ru_table', $TMP . DIRECTORY_SEPARATOR );
		if ( ! $uploaded_ru_table ) {
			echo "Couldn`t load ru_table<br/>";
		} else {
			RabbitHelper::save_variable ( 'ru_table', $uploaded_ru_table );
			$hasFiles = true;
		}
		
		$this->setRedirect(JRoute::_('index.php?option=com_rabbit&view=translatecheck', false) );
	}
		
	public function import ( $cachable = false, $urlparams = false ) {
		$this->setRedirect(JRoute::_('index.php?option=com_rabbit&view=import', false) );
	}
	
	public function importsales ( $cachable = false, $urlparams = false ) {
		$this->setRedirect(JRoute::_('index.php?option=com_rabbit&view=importsales', false) );
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
					'product_variant_def' => 'string',	//combo
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
		RabbitHelper::save_variable ( 'product_variant_def', $options ['jform'] ['product_variant_def'] );
		RabbitHelper::save_variable ( 'convert_images', $options ['jform'] ['convert_images'] );
		RabbitHelper::save_variable ( 'images_packed', $options ['jform'] ['images_packed'] );
		RabbitHelper::save_variable ( 'import_type', $options ['jform'] ['import_type'] );
		
		jimport('joomla.filesystem.file');
		
		// Загружаем файлы во временный каталог и сохраняем их имена в сессии
		
		$uploaded_images = RabbitHelper::upload_images ( $uploaded_files, 'images', $TMP . DIRECTORY_SEPARATOR . 'full' );

		if ( empty ( $uploaded_images ) ) {
			echo "Couldn`t load images<br/>";
		} else {
			RabbitHelper::save_variable ( 'uploaded_images', $uploaded_images );
			$hasFiles = true;
		}
		
		$uploaded_medi = RabbitHelper::upload_images ( $uploaded_files, 'medi_images', $TMP . DIRECTORY_SEPARATOR . 'medi' );
		if ( empty ( $uploaded_medi ) ) {
			echo "Couldn`t load medi_images<br/>";
		} else {
			RabbitHelper::save_variable ( 'uploaded_medi', $uploaded_medi );
			$hasFiles = true;
		}
		
		$uploaded_mini = RabbitHelper::upload_images ( $uploaded_files, 'mini_images', $TMP . DIRECTORY_SEPARATOR . 'mini' );
		if ( empty ( $uploaded_mini ) ) {
			echo "Couldn`t load mini_images<br/>";
		} else {
			RabbitHelper::save_variable ( 'uploaded_mini', $uploaded_mini );
			$hasFiles = true;
		}
		
		if ( ! empty ( $uploaded_files ['import_table']['name'] ) ) {
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
		
		//To implement rollback implementation here yet now
		
		$this->setRedirect(JRoute::_('index.php?option=com_rabbit', false) );
	}
	
	public function close ( $cachable = false, $urlparams = false ) {
		$this->setRedirect(JRoute::_('index.php', false) );
	}

	
}