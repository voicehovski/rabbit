<?php

defined('_JEXEC') or die('Restricted access');

abstract class RabbitHelper extends JHelperContent {
/*
		Сохранение данных между запросами посредством сессий. Данные сохраняются в файлах,
	в местах, указанных в соответствующих директивах php.ini
		
		Если директива session.auto_start php.ini установлена, сервер автоматически запустится
	сессию при получении запроса и вызов session_start (  ) можно будет опустить
	
		См. также https://api.joomla.org/cms-3/classes/JSession.html
*/
	public static function save_variable ( $name, $data ) {
		session_start (  );
		$_SESSION ["$name"] = $data;
		//session_write_close (  );
	}
	
	public static function restore_variable ( $name ) {
		session_start (  );
		$data =  null;
		if ( $_SESSION ["$name"] ) {
			$data = $_SESSION ["$name"];
		}
		//session_write_close (  );
		return $data;
	}

	public static function upload_images ( $uploaded_files, $name, $tmp_path ) {
		
		if ( !$uploaded_files [$name] ) {
			return null;
		}
		
		jimport('joomla.filesystem.file');
		jimport('joomla.filesystem.folder');
		
		$uploaded_images = array (  );
		
		if ( ! JFolder::exists ( $tmp_path ) && ! JFolder::create ( $tmp_path ) ) {
			return null;
		}
		
		foreach ( $uploaded_files [$name] as $image ) {
			JFile::upload (
				$image ['tmp_name'],
				$tmp_path . DIRECTORY_SEPARATOR . $image [ 'name' ],
				false, true
			);
			$uploaded_images [] = $image [ 'name' ];
		}
		
		return $uploaded_images;
	}
	
	public static function storeUploadedFiles (  ) {
		//Средства жумлы для работы с полями формы
		// См. также https://docs.joomla.org/Retrieving_request_data_using_JInput
		$input    = JFactory::getApplication()->input;
		
		/*		Отладка и тестирование ввода. Для отладки закомментировать редирект в контроллере !!!
		
			$input содержит поля формы в виде иерархического массива с корневым элементом jform
			Поля доступны как элементы второго уровня [jform][field_name]
			Поля из вкладок (в xml заключены в секцию fields) - третий уровень [jform][fieldіs_section_name][field_name]
			
			Конструкции вида
			$input -> get ( 'field_name', "default value", "check_type" );
			или
			$input -> get ( 'jform[params][import_type]', "default value", "check_type" );
			не работают
		
			Можно извлечь весь массив, передав null в качестве шаблона проверки
			$jform = $input -> get ( 'jform', null, null );
			foreach ( $jform as $k => $v ) {
				if ( !is_array ( $v ) ) {
					echo "$k = $v<br/>";
					continue;
				}
				print_array ( $v );	//rerucsive...
			}
			
			Можно использовать функцию getArray - тогда доступны проверки, но нужно заранее описать структуру
			$a2 = $input -> getArray ( array (
				"jform" => array (
					'import_options' => 'string',
					'param' => array (
						'import_type' => 'string',
						'userimport' => 'string'
					)
				)
			) );
			echo $a2 ['jform'] ['param'] ['import_type'];
			...
		*/
		
		//	Работа с файлами
		
		//Если форма содержит несколько полей типа "Файл", они будут доступны как элементы массива с соответствующими именами
		$uploaded_files = $input->files->get('jform', null, 'raw');
		
		//Средства жумлы для работы с файлами. Функция JFile::upload удаляет исходные временные файлы
		jimport('joomla.filesystem.file');
		
		//Поле с возможностью загрузки нескольких файлов (аттрибут multiple) будет доступно как массив
		if ( $uploaded_files ['images'] ) {
			foreach ( $uploaded_files ['images'] as $image )
				JFile::upload (
					$image ['tmp_name'],
					JPATH_SITE . '/tmp/' . $image [ 'name' ],
					false, true
				);
		}
		
		if ( $uploaded_files ['import_table'] ) {
			//Получаем путь к временной папке. Скорее всего это директория временных файлов сервера /tmp
			$tmp_src = $uploaded_files [ 'import_table' ] ['tmp_name'];
			
			//Задаём куда копировать. Пусть это будет временная папка в корне сайта
			$tmp_dest = JPATH_SITE . '/tmp/' . $uploaded_files [ 'import_table' ] [ 'name' ];
			
			JFile::upload($tmp_src, $tmp_dest, false, true);
		}
		
		return $uploaded_files;
		
		/*		Различные способы работы с загруженными файлами
		
			echo JFile::copy($tmp_src, $tmp_dest);
			move_uploaded_file ($tmp_src, $tmp_dest);

			$package = JInstallerHelper::unpack($tmp_dest, true);
			return $package;
		*/
		
		/*
				Загрузка файлов
				
			Файлы нужно обрабатывать в том же запросе, в котором они отправлены - после редиректа они будут удалены
		
			$jinput = JFactory::getApplication()->input;
			$upload_options = $jinput -> get ( 'options', 'option_1=122', 'STR' );
			$upload_files = $jinput -> files -> get ( 'jform' );
			var_dump ( $jinput );
			var_dump ( $jinput -> files );
			var_dump ( $_POST );
			var_dump ( $_FILES );
			var_dump ( $upload_files );
			echo 'name = ' . $upload_files [ 'import_table' ] [ 'name' ];
			echo 'type = ' . $upload_files [ 'import_table' ] [ 'type' ];
			echo 'tmp_name = ' . $upload_files [ 'import_table' ] [ 'tmp_name' ];
			echo 'error = ' . $upload_files [ 'import_table' ] [ 'error' ];
			echo 'size = ' . $upload_files [ 'import_table' ] [ 'size' ];
		*/
		
	}

	
}