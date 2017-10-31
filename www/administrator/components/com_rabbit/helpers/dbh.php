<?php

defined('_JEXEC') or die('Restricted access');


class DBHelper {
	
	static $config = array (
		'localized-slug' => true,
		'default-locale' => 'uk_ua'
	);
	
	public static function import ( $group ) {
		
		$test_products = $group -> getAll (  );
		$test = self::localizeProduct ( 5, $test_products [4], self::$config ['default-locale'] );
		var_dump ( $test );
		echo ( "<br/>" );
		$test = self::localizeProduct ( 5, $test_products [4], self::$config ['default-locale'] );
		
		return;
		
		foreach ( $group -> getAll (  ) as $p ) {
			
			$product = new Product ( $p );
			
			//		==	Product import	==
			$product_id = self::getProductId ( $p -> get ( 'sku' ) );
			
			if ( $product_id === null ) {
				$product_id = self::createProduct ( $product );
				// if ( $product_id === null ) ...
			} else {
				self::updateProduct ( $product_id, $product );
			}
			
			
			//		==	Category import	==
			$current_category_id_list = self::getProductCategories ( $product_id );	//ids
			$categories = $product -> categories (  );	//objects
			
			foreach ( $categories as $c ) {
				
				$parent_id = 0;
				$category_id = 0;
				
				foreach ( $c -> path (  ) as $p ) {
					$category_id = self::getCategoryId ( $parent_id, $p, self::$config ['default-locale'] );
					
					if ( $category_id === null ) {
						// @TODO: Сделать обработку ошибок
						$category_id = self::createCategory ( $parent_id ); //value/null
						self::localizeCategory ( $category_id, $p, self::$config ['default-locale'] );	//true/false
					}
					
					$parent_id = $category_id;
					}
				// @IDEA: $imported_category_id_list [] = $category_id;
				
				$k = array_search ( $category_id, $current_category_id_list );
				if ( $k !== false ) {
					unset ( $current_category_id_list [$k] );
					continue;
				}
				
				self::bindCategory ( $product_id, $category_id );
			}
			foreach ( $current_category_id_list as $cid ) {
				self::unbindCategory ( $product_id, $cid );
			}
			// @IDEA: array_diff ( $current_category_id_list, $imported_category_id_list )	unbind
			// @IDEA: array_diff ( $imported_category_id_list ,$current_category_id_list )	bind	
		}
	}
	
	
//====================	Products	===========================

/*		Извлекает идентификатор продукта по артикулу

	@RETURN: идентификатор продкута или null если такой не найден. В случае обнаружения дублирующегося артикула выбрасывает исключение - нужно откатить базы и разобраться вручную
	
	@PROBLEMS:
	*
	
	@IDEAS:
	*
*/
public static function getProductId ( $productSku ) {
	$s = new stdClass (  );
	$s -> column_name = 'virtuemart_product_id';
	$s -> table_name = '#__virtuemart_products';
	$s -> condition_column = 'product_sku';

	$results = self::get_id_ ( $productSku, $s );
	
	if ( $results ) {
		if ( count ( $results > 1 ) ) {
			throw Exception ( "Several products with sku $productSku" );
		}
		return $results [0];
	} else {
		return null;
	}	
}

/*		Создаёт новый продукт без локализации

	@TODO: Добавить остальные поля таблицы
*/
public static function createProduct ( $product ) {
	$columns = array ( 'product_sku', 'product_in_stock', 'published', 'created_on' ); 
	$values = array ( $product -> identifier (  ), 1, 1, JFactory::getDate (  ) -> toSql (  ) );

	return self::create_ ( $columns, $values, '#__virtuemart_products' );
}

/*		Обновляет данные продукта

	@TODO: Добавить локализацию
*/
public static function updateProduct ( $id, $product ) {

	$object = new stdClass();

	// Must be a valid primary key value.
	$object -> virtuemart_product_id = $id;
	$object -> modified_on = JFactory::getDate (  ) -> toSql (  );
	if ( isset ( $product -> published ) ) {
		if ( empty ( $product -> published ) ) {
			$object -> published = 0;
		} else {
			$object -> published = 1;
		}
	}

	// Обновляемая таблица, данные и ключевое поле. Есть еще четвертый логический параметр говорящий как обновлять null-поля, но что это значит, пока не ясно - то ли обнулять то чего нет в данных, то ли не трогать то что и так null в базе
	return JFactory::getDbo (  ) -> updateObject ( '#__virtuemart_products', $object, 'virtuemart_product_id' );
}

/*		Выполняет локализацию основных аттрибутов продукта (не пользовательских) на один язык

	@PURPOSE: 

	@ARGS: 

	@RETURN: true/false (если вставка оказалась неудачной)
	
	@PROBLEMS:
	* не выполняет проверку существования локали
	* слишком громоздко с проверкой существования
	* Необходима обработка возврата нескольких ИД при проверке, что добавит громоздкости
	
	@FRATURES:
	* 
*/
	public static function localizeProduct ( $product_id, $product, $locale ) {

		$s = new stdClass (  );
		$s -> column_name = 'virtuemart_product_id';
		$s -> table_name = "#__virtuemart_products_$locale";
		$s -> condition_column = 'virtuemart_product_id';

		$results = self::get_id_ ( $product_id, $s );
		
		
		$db = JFactory::getDbo (  );
		$lcl = new stdClass (  );
		
		$lcl -> virtuemart_product_id = $product_id;
		//$lcl -> product_s_desc = 
		$lcl -> product_desc = $product -> get ( 'desc' );
		$lcl -> product_name = $product -> get ( 'name' );
		// meta...	metadesc, metakey, customtitle
		$lcl -> slug = self::normalizeSlug ( $lcl -> product_name, ! self::$config ['localized-slug'] ) . $productId;
		
		if ( ! $results ) {
			return $db -> insertObject ( "#__virtuemart_products_$locale", $lcl );
		} else {
			return $db -> updateObject ( "#__virtuemart_products_$locale", $lcl, 'virtuemart_product_id' );
		}
	}


//====================	Categories	===========================

/*		Извлекает ИД категории по локализованному идентификатору (имени) и ИД родительской категории

	@RETURN: идентификатор категории или null если что-то не получилось
	
	@PROBLEMS:
	* не выполняет проверку существования локали
	* не учитывает ситуацию когда указанным в аргументах усливиям соответствует не солько значений, что свидетельствует об ошибке. Можно извлекать из базы массив вместо занчения или довериться, но сделать проверочную процедуру, запускаемую время от времени
*/
	public static function getCategoryId ( $parent_id, $identifier, $locale ) {
		
		$name = 'virtuemart_category_id';
		$table = "#__virtuemart_categories";
		$locale_table = "#__virtuemart_categories_$locale";
		$bind_table = "#__virtuemart_category_categories";
		$cond_1 = 'category_name';
		$cond_2 = 'category_parent_id';

		$db = JFactory::getDbo (  );
		$query = $db -> getQuery ( true );

		$query -> select ( $db -> quoteName ( "main.$name" ) );
		$query -> from ( $db -> quoteName ( $table, 'main' ) );
		$query -> join ( 'INNER', $db -> quoteName ( $locale_table, 'lcl' ) .
			" on (" . $db -> quoteName ( "main.$name" ) . ' = ' . $db -> quoteName ( "lcl.$name" ) . ')' );
		$query -> join ( 'INNER', $db -> quoteName ( $bind_table, 'bnd' ) .
			" on (" . $db -> quoteName ( "main.$name" ) . ' = ' . $db -> quoteName ( "bnd.category_child_id" ) . ')' );
		$query -> where ( $db -> quoteName ( "lcl.$cond_1" ) . ' = ' . $db -> quote ( $identifier ) );
		$query -> where ( $db -> quoteName ( "bnd.$cond_2" ) . ' = ' . $db -> quote ( $parent_id ) );

		$db -> setQuery ( $query );
		// return $db -> loadColumn (  );// Returns array or null
		return $db -> loadResult (  );// Returns value or null
	}

/*		Создаёт категорию в указанной родительской категории. Не вносит описания, метаданные и прочее

	@PURPOSE: Предназначена для автоматического создания категории по таблице продукции. Не устанавливает локализованные данные - только категорию и связь с родительской

	@HOW_TO_USE: Вызвать функцию. Возвращенный идентификатор передать в localizeCategory
	
	@ARGS: идентификатор родительской категории

	@RETURN: идентификатор категории или null если что-то не получилось
	
	@PROBLEMS:
	* при возниконвении ошибки могут остаться уже внесенные изменения. Транзакция?
	* если после этой функции не вызвать localizeCategory, то будет создана безымянная категория
	
	@IDEAS:
	* Можно включить сюда localizeCategory, и все обернуть в транзакцию или включить localizeCategory и это в отдельный методы
*/
	public static function createCategory ( $parent_id ) {
		$db = JFactory::getDbo (  );
		
		$main = new stdClass (  );
		
		$main -> created_on = JFactory::getDate (  ) -> toSql (  );
		if ( ! $db -> insertObject ( '#__virtuemart_categories', $main ) ) {
			return null;
		}
		$id = $db -> insertid (  );
		
		$bind = new stdClass (  );
		
		$bind -> category_parent_id = $parent_id;
		$bind -> category_child_id = $id;
		if ( ! $db -> insertObject ( '#__virtuemart_category_categories', $bind ) ) {
			return null;
		}
		
		return $id;
	}

	public static function bindCategory ( $productId, $categoryId ) {
		$s = new stdClass (  );
		$s -> binder = 'virtuemart_product_id';
		$s -> bindee = 'virtuemart_category_id';
		$s -> table_name = '#__virtuemart_product_categories';
		
		return self::bind_ ( $productId, $categoryId, $s );
	}

	public static function unbindCategory ( $productId, $categoryId ) {
		$s = new stdClass (  );
		$s -> binder = 'virtuemart_product_id';
		$s -> bindee = 'virtuemart_category_id';
		$s -> table_name = '#__virtuemart_product_categories';
		
		return self::unbind_ ( $productId, $categoryId, $s );
	}

	public static function getProductCategories ( $productId ) {
		/*
		$db = JFactory::getDbo (  );
		$query = $db -> getQuery ( true );
		
		$query
			-> select ( $db -> quoteName ( '' ) )
			-> from ( $db -> quoteName ( '' ) )
			-> where ( $db -> quoteName ( '' ) . ' = ' . $db -> quote ( $product_id ) );
		*/
		$s = new stdClass (  );
		$s -> column_name = 'virtuemart_category_id';
		$s -> table_name = '#__virtuemart_product_categories';
		$s -> condition_column = 'virtuemart_product_id';

		$results = self::get_id_ ( $productId, $s );
		return $results ? $results : array (  );
	}

/*		Выполняет локализацию на один язык. Только основные данные

	@PURPOSE: Предназначена для автоматического создания категории по таблице продукции. Устанавливает только критически необходимые данные категории. Изображение, метаданные и прочее необходимо установить другими способами

	@ARGS: идентификатор категории, имя категории в указанной локали и код локали

	@RETURN: true или null если что-то не получилось
	
	@PROBLEMS:
	* не выполняет проверку существования локали
	
	@FRATURES:
	* не выполняет проверку существования категории, соответствующей аргументам, поэтому такую проверку нужно выполнять в вызывающем коде
*/
	public static function localizeCategory ( $category_id, $category_name, $locale ) {
		$db = JFactory::getDbo (  );

		$lcl = new stdClass (  );
		
		$lcl -> virtuemart_category_id = $category_id;
		$lcl -> category_name = $category_name;
		//$lcl -> category_description = 
		// meta...	
		$lcl -> slug = self::normalizeSlug ( $category_name, ! self::$config ['localized-slug'] );
		
		return $db -> insertObject ( "#__virtuemart_categories_$locale", $lcl );
	}


//====================	Images	===========================

	public static function getImageId ( $image_url ) {
		$s = new stdClass (  );
		$s -> column_name = 'virtuemart_media_id';
		$s -> table_name = '#__virtuemart_medias';
		$s -> condition_column = 'file_url';

		$results = self::get_id_ ( $productId, $s );
		
		if ( $results ) {	//Что возвращает loadColumn в случае отсутствия результата?
			if ( count ( $results > 1 ) ) {
				//log warning	более одного изображения с таким именем в базе
			}
			return $results [0];
		} else {
			return null;
		}
	}

	public static function createImage ( $image ) {
		$columns = array ( 'file_url', 'file_url_thumb', 'file_is_product_image', 'created_on' ); 
		$values = array ( $image -> url, $image -> url_thumb, 1, date ( 'd.m.Y. H-i-s' ) );

		return self::create_ ( $columns, $values, '#__virtuemart_medias' );
	}

	public static function bindImage ( $productId, $imageId ) {
		$s = new stdClass (  );
		$s -> binder = 'virtuemart_product_id';
		$s -> bindee = 'virtuemart_media_id';
		$s -> table_name = '#__virtuemart_product_medias';
		
		self::bind_ ( $productId, $imageId, $s );	
	}

	public static function unbindImage ( $productId, $imageId ) {
		$s = new stdClass (  );
		$s -> binder = 'virtuemart_product_id';
		$s -> bindee = 'virtuemart_media_id';
		$s -> table_name = '#__virtuemart_product_medias';
		
		self::unbind_ ( $productId, $imageId, $s );
	}

	public static function getProductImages ( $productId ) {
		$s = new stdClass (  );
		$s -> column_name = 'virtuemart_media_id';
		$s -> table_name = '#__virtuemart_product_medias';
		$s -> condition_column = 'virtuemart_product_id';
		return self::get_id_ ( $productId, $s );
	}

	
//=====================	Data bases	================================
	
/*		Получение связанных сущностей, например изображений продукта или сущности по идентификатору
	
*/
	static function get_id_ ( $identifier, $s ) {
		// Возвращает объект JDatabaseDriver
		// См. также
		$db = JFactory::getDbo (  );
		// Если передать TRUE, вернет новый объект, по умолчанию - текущий
		$query = $db -> getQuery ( true );

		// Можно передавать имя или массив имен колонок, втч в форме "tbl_aliase.clmn_name". Возвращает себя, так что можно делать chain. Например $q -> select ( 'a.name' ) -> select ( 'b.code' ) эквивалентно $q -> select ( array ( 'a.name', 'b.code' ) )
		// См. также https://api.joomla.org/cms-3/classes/JDatabaseQuery.html
		// quoteName кавычит имена колонок, таблиц. Для кавыченья значений используем quote. Вторым параметром можно указать AS. Можно передавать /возвращать массивы
		$query -> select ( $db -> quoteName ( $s -> column_name ) );
		$query -> from ( $db -> quoteName ( $s -> table_name ) );
		// Массив или chain. По умолчанию AND (второй строковоий аргумент)
		$query -> where ( $db -> quoteName ( $s -> condition_column ) . ' = ' . $db -> quote ( $identifier ) );

		$db -> setQuery ( $query );
		// Возвращает массив значений. В случае неудачи возвращает null
		// @QUESTION: В смысле ошибки или пустого результата?
		$return =  $db -> loadColumn (  );
		return $return;
	}

	static function create_ ( $columns, $values, $table_name ) {
		$db = JFactory::getDbo (  );
		$query = $db -> getQuery ( true );
		 
		$query
			-> insert ( $db -> quoteName ( $table_name ) ) 
			-> columns ( $db -> quoteName ( $columns ) )
			-> values ( implode ( ',', $db -> quote ( $values ) ) );
		
		// Установка запроса для последующего выполнения. Можно указать смещение и лимит
		// @QUESTION: смещение и лимит для выборки понятны, а для вставки, например?
		$db -> setQuery ( $query );
		// Возвращает КУРСОР и ЛОЖЪ в случае неудачи
		// @QUESTION: что такое КУРСОРЪ? Отвътъ. Курсор - это объект типа mysqli_result, что-то вроде итератора по результатам запроса с допполями
		$return = $db -> execute (  );
		if ( ! $return ) {
			// Не получилось создать. Надо что-то делать
			//$db -> getErrorNum (  );
			//$db -> getErrorMessage (  );
			return null;
		}
		
		// Возвращаем последний добавленный ИД
		return $db -> insertid (  );
	}

	static function bind_ ( $productId, $_id, $s ) {
		
		if ( self::check_bind_ ( $productId, $_id, $s ) ) {
			return;
		}
		
		$db = JFactory::getDbo (  );
		$query = $db -> getQuery ( true );
		
		$columns = array ( $s -> binder, $s -> bindee );
		$values = array ( $db -> quote ( $productId ), $db -> quote ( $_id ) );
		
		$query
			-> insert ( $db -> quoteName ( $s -> table_name ) )
			-> columns ( $db -> quoteName ( $columns ) )
			-> values ( implode ( ',', $values ) );
		
		$db -> setQuery ( $query );
		$return = $db -> execute (  );
		return $return;
	}
	
	static function check_bind_ ( $productId, $_id, $s ) {
		$db = JFactory::getDbo (  );
		$query = $db -> getQuery ( true );
		
		$query
			-> select ( '*' )
			-> from ( $db -> quoteName ( $s -> table_name ) )
			-> where ( $db -> quoteName ( $s -> binder ) . ' = ' . $db -> quote ( $productId ) )
			-> where ( $db -> quoteName ( $s -> bindee ) . ' = ' . $db -> quote ( $_id ) );
			
		$db -> setQuery ( $query );
		$return = $db -> loadAssoc (  );
		return $return;
	}

	static function unbind_ ( $productId, $_id, $s ) {
		$db = JFactory::getDbo (  );
		$query = $db -> getQuery ( true );
		
		$conditions = array ( 
			$db -> quoteName ( $s -> binder ) . ' = ' . $db -> quote ( $productId ),
			$db -> quoteName ( $s -> bindee ) . ' = ' . $db -> quote ( $_id )
		);
		
		$query -> delete ( $db -> quoteName ( $s -> table_name ) );
		$query -> where ( $conditions );
		
		$db -> setQuery ( $query );
		$return = $db -> execute (  );
		return $return;
	}

	static function normalizeSlug ( $slug, $translit ) {
		$st = mb_ereg_replace ( '[\s/]', "_", $slug );
		if ( ! $st ) {
			return "";
		}
		
		if ( ! $translit ) {
			return $st;
		}
		
		$replacement = array( 
			"й"=>"i","ц"=>"c","у"=>"u","к"=>"k","е"=>"e","н"=>"n", 
			"г"=>"g","ш"=>"sh","щ"=>"sh","з"=>"z","х"=>"x","ъ"=>"\'", 
			"ф"=>"f","ы"=>"i","в"=>"v","а"=>"a","п"=>"p","р"=>"r", 
			"о"=>"o","л"=>"l","д"=>"d","ж"=>"zh","э"=>"ie","ё"=>"e", 
			"я"=>"ya","ч"=>"ch","с"=>"c","м"=>"m","и"=>"i","т"=>"t", 
			"ь"=>"\'","б"=>"b","ю"=>"yu", 
			"Й"=>"I","Ц"=>"C","У"=>"U","К"=>"K","Е"=>"E","Н"=>"N", 
			"Г"=>"G","Ш"=>"SH","Щ"=>"SH","З"=>"Z","Х"=>"X","Ъ"=>"\'", 
			"Ф"=>"F","Ы"=>"I","В"=>"V","А"=>"A","П"=>"P","Р"=>"R", 
			"О"=>"O","Л"=>"L","Д"=>"D","Ж"=>"ZH","Э"=>"IE","Ё"=>"E", 
			"Я"=>"YA","Ч"=>"CH","С"=>"C","М"=>"M","И"=>"I","Т"=>"T", 
			"Ь"=>"\'","Б"=>"B","Ю"=>"YU", 
		); 
		
		foreach($replacement as $i=>$u) { 
			$st = mb_eregi_replace($i,$u,$st); 
		} 
		return $st; 
	}
}

class Product {
	
	public function __construct ( $p ) {
		$this -> data = $p;
	}
	
	protected $data;
	
	public $published;
	
	public function identifier (  ) {
		return $this -> data -> get ( 'sku' );
	}
	
	public function categories (  ) {
		
		$categories =  explode ( ',' ,$this -> data -> get ( 'category' ) );
		
		if ( empty ( $categories ) )
			return null;
		
		/*
		array_walk (
			$categories,
			function ( & $value, $key ) {
				$value = trim ( $value );
			}
		);
		
		return $categories;
		*/
		
		$c_objects = array (  );
		foreach ( $categories as $c ) {
			$c_objects [] = new Category ( trim ( $c ) );
		}
		return $c_objects;
	}
}

class Category {
	
	public function __construct ( $c ) {
		$this -> data = $c;
	}
	
	protected $data;
	
	public function identifier (  ) {
		return $this -> data;
	}
	
	public function path (  ) {
		return explode ( '/', $this -> data );
	}
}