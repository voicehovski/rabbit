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

	
/*		Проверяет введенные данные и приводит их к форме пригодной для импорта

	@ACTIONS: 
	* Собирает параметры и данные, введенные на предыдущем шаге
	* При необходимости обрабатывает/распаковывает загруженные изображения
	* Проверяет корректность/комплектность изображений
	* Нормализует данные csv-файла
	* Проверяет их на ошибки ячеек (синтаксические)
	* Определяет вариации товаров (раскладывает артикул)
	* Создаёт структуру для импорта
	* Проверяет ошибки структуры (логические)
	* Определяет статус результата
	* Если все нормально, сохраняет структуру импорта в сессии
	
	@TODO:
	* Обработка изображений на сервере, распаковка архива
	* Копирование изображений в соответствующий каталоги
	* Разделение по типу импортируемой продукции (имя таблицы, поле, категория в табилце)
*/
	public function display($tpl = null)
	{
		
		$TMP = JPATH_SITE . '/tmp/';	// Путь загрузки файлов. Аналогичная переменная в контроллере
		
		$this->form = $this->get('Form');
		
		$model = $this -> getModel ( 'check' );
		if ( ! class_exists ( 'csvHelper' ) ) require ( JPATH_COMPONENT_ADMINISTRATOR . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'csvh.php' );
		
		// Получаем имена загруженных файлов
		$table_filename = RabbitHelper::restore_variable ( 'uploaded_table' );
		$images = RabbitHelper::restore_variable ( 'uploaded_images' );
		$medi_images = RabbitHelper::restore_variable ( 'uploaded_medi' );
		$mini_images = RabbitHelper::restore_variable ( 'uploaded_mini' );
		
		// Получаем остальные параметры импорта
		$content_type = RabbitHelper::restore_variable ( 'content_type' );
		$convert_images = RabbitHelper::restore_variable ( 'convert_images' );	//null or 1
		$images_packed = RabbitHelper::restore_variable ( 'images_packed' );
		$import_type = RabbitHelper::restore_variable ( 'import_type' );
		
		// Через поле полноразмерных изображений загрузка обязательна. Множество изображений или архив
		// @TODO: Способы возврата ошибок и прерывания работы скрпта (как выход из функции). Например метка на скобки
		try {
			if ( empty ( $images ) || ! is_array ( $images ) ) {
				throw new Exception ( "Images are missing" );
			}
			
			// @TODO: Распаковка архива. Содержимое архива может не соответствовать, так что нужно проверять
			if ( $images_packed ) {
				// Распаковать во временный каталог. Структура каталогов внутри архива должна соответствовать full, medi, mini
				// Если невозможно распаковать, выход
				// Если в архиве не то, выход
				throw new Exception ( "Archive error" );
			}
			
			// @TODO: Конвертация изображений на сервере
			if ( $convert_images ) {
				// Создать стандарт и миниатюру для каждого изображения
				// Если не получилось, выход
				// Сохранить в соответствующие каталоги категорий
				// Остальные загрузки игнорируем
				throw new Exception ( "Convertation error" );
			} else {
				// Должны быть загружены все поля. Проверяем наличие и соответствие имен
				$medi_diff = array (  );
				if ( ! $this -> checkImages ( $images, $medi_images, $medi_diff ) ) {
					throw new Exception ( "Medi images missmatch: " . implode ( ', ', $medi_diff ) );
				}
				
				$mini_diff = array (  );
				if ( ! $this -> checkImages ( $images, $mini_images, $mini_diff ) ) {
					throw new Exception ( "Mini images missmatch: " . implode ( ', ', $mini_diff ) );
				}
				
				
				// @TODO: Копируем файлы в соответствующие каталоги категорий
				// JFile::copy ( $src, $dest );
				// @SEE: https://docs.joomla.org/How_to_use_the_filesystem_package, https://api.joomla.org/cms-3/classes/JFolder.html
				
			}
		} catch ( Exception $e ) {
			echo "TROUBLES WHILE IMAGE PROCESSING<br/>{$e -> getMessage (  )}<br/>";
		}
		
		try {
			if ( $table_filename ) {
				// @NOTE: Функция file_get_contents читает файл в одну строку, file - в массив строк
				//Читаем данные из таблицы импорта и выполняем проверку.
				$rawCsv = file ( $TMP . $table_filename );
				$products = new ProductGroup (  );

				// Нормализуем данные загруженные из файла и формируем метаданные - индексы колонок и т.д.
				$csv = new Csv ( $rawCsv, array ( 'delim'=>';','encl'=>'','esc'=>'' ) );
				
				// @TODO: Здесь нужно определить тип продукции (чекбокс или по заголовкам/категориям) и создать соответствующий объект. Пользователей и заказы, видимо лучше импортировать в другом виде
				// Теперь можно создавать ассоциативные формы строк csv и обращаться к данным в них по заданным кодам, а не по исходным индексам
				$csvMeta = CsvMetadata::createProductMetadata ( $csv -> headers (  ) );
				
				foreach ( $csv -> data (  ) as $rowIndex => $row ) {	// Каждая строка исходных данных
					
					// Проверяем каждую ячейку регулярным выражением
					// checkCells возвращает массив, поскольку в строке может быть несколько ошибок. Сливаем его с существующим
					$errors = $csvMeta -> checkCells ( $row, $rowIndex );
					if ( ! empty ( $errors ) ) {
						$this -> cellErrors = array_merge ( $this -> cellErrors, $errors );	
						// @PROBLEM: We should catch critical errors like empty sku ... getWorst if >= CRITICAL
					}

					// Формируем ассоциативный массив и фиксируем ошибку если не удалось
					$assocRow = $csvMeta -> createAssoc ( $row );
					if ( empty ( $assocRow ) ) {
						$this -> structuralErrors [] = new StructuralError ( array ( $rowIndex ), '', "Couldn`t create assoc row from csv", 2 );
						continue;
					}
					
					
					// @QUESTION: where should we catch errors like 'missing sku'? As critical error in cellErrors?
					// Получаем свойства, определяющие разновидности одного товара . Функции получения этих свойств отличаются для разных категорий товаров. Соответствующие функции определены в статических фабричных методах CsvMetadata
					$productVariantProperties = $csvMeta -> getProductVariantProperties ( $assocRow ['sku'] );//code=> color=> size=>
					if ( ! $productVariantProperties ) {
						$this -> cellErrors [] = new CellCsvError ( $rowIndex, 'sku', $assocRow ['sku'], 'Couldn`t parse sku', 2 );
						continue;
					}

					// @QUESTION: Нужно ли сохранять ассоциативные массивы или они больше не понадобятся?
					// Формируем структуру данных для дальнейшего импорта
					$products -> add ( new ProductData ( $rowIndex, array_merge ( $productVariantProperties, $assocRow ) ) );
					
				}
				
				// Струкрурные ошибки можно найти только в завершенном списке продукции
				$this -> structuralErrors = array_merge ( $this -> structuralErrors, $csvMeta -> checkStructural ( $products ) );
				
				$this -> importData = $products;
				$this -> csv = $csv;
				
				$this -> check_status = max ( CellCsvError::worstErrorStatus ( $this -> cellErrors ), StructuralError::worstErrorStatus ( $this -> structuralErrors ) );
				
			} else {
				echo "DEBUG: No table passed<br/>";
				$this -> check_status = 3;
			}
		} catch (Exception $e) {
			echo "{$e -> getMessage (  )} <br/>";
			echo $e -> getTraceAsString (  );
		}
		
		//$this -> check_status = rand ( 0, 2 );
		echo "DEBUG: error status";
	print_r ( $this -> check_status );
		
		// В зависимости от результатов проверки устанавливаем лайот и передаём в него ошибки/данные импорта
		switch ( $this -> check_status ) {
			case 3:
				//$this -> message = "No input file, bleat!";
				$this -> setLayout ( "error" );
				break;
			case 2:
				$this -> setLayout ( "error" );
				break;
			case 1:
				$this -> setLayout ( "warning" );
				//break;
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

	protected function checkImages ( $base, $resized, & $diff ) {
			
			if ( empty ( $resized ) || ! is_array ( $resized ) ) {
				return false;
			}	
			
			$diff = array_diff ( $base, $resized );
			if ( ! empty ( $diff ) ) {
				return false;
			}
			
			return true;
		}
	
}