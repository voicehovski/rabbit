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
	
	public static function storeUploadedFiles (  ) {
		//Средства жумлы для работы с загруженными файлами
		$input    = JFactory::getApplication()->input;
		$userfile = $input->files->get('jform', null, 'raw');
		
		//Скорее всего это директория временных файлов сервера /tmp
		$tmp_src = $userfile [ 'import_file' ] ['tmp_name'];
		//Задаём временную папку в корне сайта
		$tmp_dest = JPATH_SITE . '/tmp/' . $userfile [ 'import_file' ] [ 'name' ];
		
		//Средства жумлы для работы с файлами. Исходные файлы, кажется, удаляются
		jimport('joomla.filesystem.file');
		JFile::upload($tmp_src, $tmp_dest, false, true);
		
		return $userfile [ 'import_file' ] [ 'name' ];
		
		//Другие способы работы с файлами
		//echo JFile::copy($tmp_src, $tmp_dest);
		//move_uploaded_file ($tmp_src, $tmp_dest);

		//???
		// $package = JInstallerHelper::unpack($tmp_dest, true);
		// return $package;
		
		/*
			Эксперименты с загрузкой файлов. Файлы нужно обрабатывать в том же запросе, в котором они отправлены - после редиректа они будут удалены
		
			$jinput = JFactory::getApplication()->input;
			$upload_options = $jinput -> get ( 'options', 'option_1=122', 'STR' );
			$upload_files = $jinput -> files -> get ( 'jform' );
			var_dump ( $jinput );
			var_dump ( $jinput -> files );
			var_dump ( $_POST );
			var_dump ( $_FILES );
			var_dump ( $upload_files );
			echo 'name = ' . $upload_files [ 'import_file' ] [ 'name' ];
			echo 'type = ' . $upload_files [ 'import_file' ] [ 'type' ];
			echo 'tmp_name = ' . $upload_files [ 'import_file' ] [ 'tmp_name' ];
			echo 'error = ' . $upload_files [ 'import_file' ] [ 'error' ];
			echo 'size = ' . $upload_files [ 'import_file' ] [ 'size' ];
		*/
	}


	public static $CSV_DELIMITER = ';';
	public static $CSV_ENCLOSURE = '"';
	public static $CSV_ESCAPE = '\\';
	
	public static function get_csv_headers ( $csv_data ) {
		
	}
	
	static $HEADERS = array (
		'sku' => array ( 'index' => -1, 'name' => "Артикул", 'pattern' => "#(\\d+)/(\\d+)/(\\d+)#", 'error_status' => 2 ),
		'name' => array ( 'index' => -1, 'name' => "Название", 'pattern' => "/.*/", 'error_status' => 2 ),
		'category' => array ( 'index' => -1, 'name' => "Категория", 'pattern' => "/.*/", 'error_status' => 2 ),
		'desc' => array ( 'index' => -1, 'name' => "Описание", 'pattern' => "/.*/", 'error_status' => 1 ),
		'price' => array ( 'index' => -1, 'name' => "Цена", 'pattern' => "/.*/", 'error_status' => 2 ),
		'images' => array ( 'index' => -1, 'name' => "Изображение", 'pattern' => "/.*/", 'error_status' => 1 )
	);
	public static function create_header_indexes ( $csv_row ) {
		for ( $i = 0; $i < count ( $csv_row ); $i++ ) {
			//Используем здесь ссылку чтобы можно было изменять элементы массива
			foreach ( RabbitHelper::$HEADERS as &$h ) {
				//! strcasecmp хуёво сравнивает мультибайтные строки без учета регистра, а именно - не сравнивает
				if ( strcasecmp ( $csv_row [$i], $h ['name'] ) == 0 ) {
					if ( $h ['index'] != -1 ) {
						//! Ошибка. Такой заголовок уже зарегистрирован. Надо что-то делать
					}
					$h ['index'] = $i;
					break;	//foreach
				}
			}
		}
		//! Проверить, все ли заголовки есть, нет ли лишних
		//! Плохий статический член, не можна. Надо заменить на кокой-то self
		return RabbitHelper::$HEADERS;
	}
	
}