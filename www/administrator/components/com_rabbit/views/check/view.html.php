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

class RabbitViewCheck extends JViewLegacy
{
	//protected $form = null;
	protected $check_status = null;

	public function display($tpl = null)
	{
		$TMP = JPATH_SITE . '/tmp/';	// Путь загрузки файлов. Аналогичная переменная в контроллере
		
		$this->form = $this->get('Form');
		
		$model = $this -> getModel ( 'check' );
		
		// Получаем имена загруженных файлов
		$table_filename = RabbitHelper::restore_variable ( 'uploaded_table' );
		$images = RabbitHelper::restore_variable ( 'uploaded_images' );
		
		// Проверка отсутствия файлов происходит в контроллере, так что здесь хоть чтото должно быть
		// @IDEA: Можно ничего не проверять и не передавать, а просто искать в каталоге загрузки новые файлы
		// @TODO: Скопировать изображения в нужный каталог. При необходимости преобразовать
		if ( $images && is_array ( $images ) ) {
			foreach ( $images as $image ) {
				echo "$image <br/>";
			}
		} else {
			echo "No images passed<br/>";
		}
		
		if ( $table_filename ) {
			//Читаем данные из таблицы импорта и выполняем проверку.
			$csv_data = file ( $TMP . $table_filename );	//Функция file_get_contents читает файл в одну строку, file - в массив строк
			$this -> check_status = $model -> check ( $csv_data );
			$this -> import_data = $this->get('ImportData');
		} else {
			echo "No table passed<br/>";
			$this -> check_status = 3;
		}
		
		//$this -> check_status = rand ( 0, 2 );
		
		// В зависимости от результатов проверки устанавливаем лайот и передаём в него ошибки/данные импорта
		switch ( $this -> check_status ) {
			case 3:
				//$this -> message = "No input file, bleat!";
				$this -> setLayout ( "error" );
				break;
			case 2:
				$this -> error_data = $this->get('ErrorData');
				$this -> logical_errors = $this -> import_data -> getLogicalErrors (  );
				$this -> setLayout ( "error" );
				break;
			case 1:
				$this -> error_data = $this->get('ErrorData');
				$this -> logical_errors = $this -> import_data -> getLogicalErrors (  );
				$this -> setLayout ( "warning" );
				break;
			case 0:
				// @QUESTION: Нужно ли сохранять в сессию?
				RabbitHelper::save_variable ( 'import_data', $this->import_data );
				break;
			default:
				JError::raiseError(500, "Unknown import check_status: " . $this -> check_status);
				return false;
		}
 
		if (count($errors = $this->get('Errors')))
		{
			JError::raiseError(500, implode('<br />', $errors));
 
			return false;
		}
 
		$this->addToolBar();
 
		parent::display($tpl);
	}

	protected function addToolBar()
	{
		JToolBarHelper::title($title, 'check');
		
		switch ( $this -> check_status ) {
			case 2:
				JToolBarHelper::custom('rabbit', null, null, "CANCEL", false);
				JToolBarHelper::custom('rabbit.close', null, null, "EXIT", false);
				break;
			case 1:
				JToolBarHelper::custom('rabbit.import', null, null, "IGNORE & CONTINUE [import]", false);
				JToolBarHelper::custom('rabbit', null, null, "CANCEL", false);
				JToolBarHelper::custom('rabbit.close', null, null, "EXIT", false);
				break;
			case 0:
				JToolBarHelper::custom('rabbit.import', null, null, "CONTINUE [import]", false);
				JToolBarHelper::custom('rabbit', null, null, "CANCEL", false);
				JToolBarHelper::custom('rabbit.close', null, null, "EXIT", false);
				break;
			default:
				return false;
		}
	}

	
	
}