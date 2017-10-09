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
	protected $cellErrors = array (  );
	protected $structuralErrors = array (  );
	protected $importData = null;
	protected $csv = null;

	public function display($tpl = null)
	{
		$TMP = JPATH_SITE . '/tmp/';	// Путь загрузки файлов. Аналогичная переменная в контроллере
		
		$this->form = $this->get('Form');
		
		$model = $this -> getModel ( 'check' );
		if ( ! class_exists ( 'csvHelper' ) ) require ( JPATH_COMPONENT_ADMINISTRATOR . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'csvh.php' );
		
		// Получаем имена загруженных файлов
		$table_filename = RabbitHelper::restore_variable ( 'uploaded_table' );
		$images = RabbitHelper::restore_variable ( 'uploaded_images' );
		
		// Проверка отсутствия файлов происходит в контроллере, так что здесь хоть чтото должно быть
		// @IDEA: Можно ничего не проверять и не передавать, а просто искать в каталоге загрузки новые файлы
		// @TODO: Скопировать изображения в нужный каталог. При необходимости преобразовать
		if ( $images && is_array ( $images ) ) {
			foreach ( $images as $image ) {
				echo "DEBUG: $image <br/>";
			}
		} else {
			echo "DEBUG: No images passed<br/>";
		}
		
		if ( $table_filename ) {
			// @NOTE: Функция file_get_contents читает файл в одну строку, file - в массив строк
			//Читаем данные из таблицы импорта и выполняем проверку.
			$rawCsv = file ( $TMP . $table_filename );
			$products = new ProductGroup (  );

			// Нормализуем данные загруженные из файла и формируем метаданные - индексы колонок и т.д.
			$csv = new Csv ( $rawCsv, array ( ';', '', '' ) );
			$csvMeta = new CsvMetadata ( RabbitHelper::$PRODUCT_CSV_META_TEMPLATE, $csv -> headers (  ) );
			
			foreach ( $csv -> data (  ) as $rowIndex => $row ) {	// Каждая строка исходных данных
				
				// Проверяем каждую ячейку регулярным выражением
				// В строке может быть несколько ошибок, поэтому checkCells возвращает массив и сливаем его с существующим
				$errors = $csvMeta -> checkCells ( $row, $rowIndex );
				if ( ! empty ( $errors ) ) {
					$this -> cellErrors [] = array_merge ( $this -> cellErrors, $errors );	
					// @PROBLEM: We should catch critical errors like empty sku ... getWorst if >= CRITICAL
				}

				// Формируем ассоциативный массив и фиксируем ошибку если не удалось
				$assocRow = $csvMeta -> createAssoc ( $row );
				if ( empty ( $assocRow ) ) {
					$structuralErrors [] = new StructuralError ( array ( $rowIndex ), '', "Couldn`t create assoc row from csv" );
					continue;
				}
				
				
				// @QUESTION: where should we catch errors like 'missing sku'? As critical error in cellErrors?
				// @QUESTION: а как если по артикулу цвет и размер не определяются?
				// Определяем по артикулу цвет и размер товара.
				$productVariantProperties = $csvMeta -> getProductVariantProperties ( $assocRow ['sku'] );//code=> color=> size=>
				if ( ! $productVariantProperties ) {
					$this -> cellErrors [] = new CellCsvError ( $rowIndex, 'sku', $assocRow ['sku'], 'Couldn`t parse sku' );
					continue;
				}

				// @QUESTION: Нужно ли сохранять ассоциативные массивы или они больше не понадобятся?
				// Формируем структуру данных для дальнейшего импорта
				$products -> add ( new ProductData ( array_merge ( $productVariantProperties, $assocRow ) ) );
				
			}
			
			// Струкрурные ошибки можно найти только в завершенном списке продукции
			$structuralErrors = array_merge ( $structuralErrors, $csvMeta -> checkStructural ( $products ) );
			
			$this -> importData = $products;
			$this -> csv = $csv;
			
			// @DEBUG: Now one should identify the worst error status for choosing corresponding layout. Something like check_status = worst getWorstStatus ( structuralErrors ), getworstStatus ( cellErrors)
			$this -> check_status = 2;
			 
			//++++++++++++++++++++++++++++++++++++++++
			
		} else {
			echo "DEBUG: No table passed<br/>";
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
				$this -> structuralErrors = $structuralErrors;
				$this -> setLayout ( "error" );
				break;
			case 1:
				$this -> cellWarnings = $this -> cellErrors;
				$this -> structuralWarnings = $structuralErrors;
				$this -> setLayout ( "warning" );
				break;
			case 0:
				// @QUESTION: Нужно ли сохранять в сессию?
				RabbitHelper::save_variable ( 'import_data', $this -> importData );
				break;
			default:
				JError::raiseError ( 500, "Unknown import check_status: " . $this -> check_status );
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