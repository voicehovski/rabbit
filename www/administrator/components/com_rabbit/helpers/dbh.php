<?php

defined('_JEXEC') or die('Restricted access');

class DBHelper {
	
	public static function import ( $group ) {
	
		foreach ( $group -> getAll (  ) as $p ) {
			
			$product = new Product ( $p );
			
			
			$product_id = self::getProductId ( $p -> get ( 'sku' ) );
			
			if ( $product_id === null ) {
				$product_id = self::createProduct ( $product );
				// if ( $product_id === null ) ...
			} else {
				self::updateProduct ( $product_id, $product );
			}
			
			
			$current_category_id_list = self::getProductCategories ( $product_id );	//ids
			$imported_categories = $product -> categories (  );	//objects
			
			foreach ( $imported_categories as $ic ) {
				
				$category_id = self::getCategoryPathId ( $ic -> path (  ), 'uk_ua' );
				// @IDEA: $imported_category_id_list [] = $category_id;
				
				if ( $category_id === null ) ) {
					$category_id = self::createCategory ( $ic );
				} else if ( $k = array_search ( $category_id, $current_category_id_list ) ) {
					unset ( $current_category_id_list [$k] );
					continue;
				}
				
				self::bindCategory ( $product_id, $category_id );
			}
			
			foreach ( $current_category_id_list as $cid ) {
				self::unbindCategory ( $product_id, $category_id );
			}
			// @IDEA: array_diff ( $current_category_id_list, $imported_category_id_list )	unbind
			// @IDEA: array_diff ( $imported_category_id_list ,$current_category_id_list )	bind
			
			
			
		}
	}
	
//====================	Products	===========================
public static function getProductId ( $productSku ) {
	$s = new stdClass (  );
	$s -> column_name = 'virtuemart_product_id';
	$s -> table_name = '#__virtuemart_products';
	$s -> condition_column = 'product_sku';

	$results = self::get_Id ( $productSku, $s );
	
	if ( $results ) {
		if ( count ( $results > 1 ) ) {
			//log warning	более одного изображения с таким именем в базе
		}
		return $results [0];
	} else {
		return null;
	}	
}

public static function updateProduct ( $id, $product ) {

	$object = new stdClass();

	// Must be a valid primary key value.
	$object -> virtuemart_product_id = $id;
	// There is $query -> currentTimestamp (  ) method, but it needs query object
	// $date = new JDate(); $date = JDate::getInstance(); $date = JFactory::getDate();
	// Функция php date ( 'Y-m-d H:i:s' ) формирует строку с датой и временем по шаблону, но, почемуто при попытке вставить такую строку в базу данных изменений не происходит, так что используем JFactory::getDate (  ) -> toSql (  )
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

public static function createProduct ( $product ) {
	$columns = array ( 'product_sku', 'product_in_stock', 'published', 'created_on' ); 
	//$values = array ( $db -> quote ( $image -> url ), $db -> quote( $image -> url_thumb ), 1, date ( 'Y-m-d H:i:s' ) );	test quote array
	$values = array ( $product -> identifier (  ), 1, 1, JFactory::getDate (  ) -> toSql (  ) );

	return self::create_ ( $columns, $values, '#__virtuemart_products' );
}

//====================	Categories	===========================

public static function getCategoryPathId ( $category_path, $locale ) {
	$parent_id = 0;
	foreach ( $category_path as $p ) {
		$id = self::getCategoryId ( $p, $parent_id, $loacle );
		if ( $id === null ) {
			$id = self::createCategory ( $p, $parent_id, $loacle );
		}
		if ( $id === null ) {
			return null; //or throw exception
		}
		$parent_id = $id;
	}
	return $id;
}

public static function getCategoryId ( $identifier, $parent_id, $locale ) {
	
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
		" on (" . $db -> quoteName ( "main.$name" ) . ' = ' . $db -> quoteName ( "lcl.$name" ) . ')';
	$query -> join ( 'INNER', $db -> quoteName ( $bind_table, 'bnd' ) .
		" on (" . $db -> quoteName ( "main.$name" ) . ' = ' . $db -> quoteName ( "bnd.category_child_id" ) . ')';
	$query -> where ( $db -> quoteName ( "lcl.$cond_1" ) . ' = ' . $db -> quote ( $identifier ) );
	$query -> where ( $db -> quoteName ( "bnd.$cond_2" ) . ' = ' . $db -> quote ( $parent_id ) );

	$db -> setQuery ( $query );
	return $db -> loadColumn (  );// Returns array or null
}

/*		Создаёт категорию и все необходимое

	Аргументы: имя категории в указанной далее локали, идентификатор родительской категории и код локали

	Вовзращает: идентификатор категории или null если что-то не получилось
	
	Проблемы: при возниконвении ошибки могут остаться уже внесенные изменения. Транзакция?
*/
public static function createCategory ( $category_name, $parent_id, $locale ) {
	$db = JFactory::getDbo (  );
	
	$main -> created_on = JFactory::getDate (  ) -> toSql (  );
	if ( ! db -> insertObject ( '#__virtuemart_categories', $main ) ) {
		return null;
	}
	$id = $db -> insertid (  );

	$lcl -> virtuemart_category_id = $id;
	$lcl -> category_name = $category_name;
	//$lcl -> category_description =
	// meta...	
	//$lcl -> slug = 
	if ( ! db -> insertObject ( "#__virtuemart_categories_$locale", $lcl ) ) {
		return null;
	}
	
	$bind -> category_parent_id = $parent_id;
	$bind -> category_child_id = $id;
	if ( ! db -> insertObject ( '#__virtuemart_category_categories', $bind ) ) {
		return null;
	}
	
	return $id;
}

public static function getProductCategories ( $productId )
public static function bindCategory ( $productId, $categoryId )
public static function unbindCategory ( $productId, $categoryId )
//====================	Images	===========================
public static function getImageId ( $image_url ) {
	$s = new stdClass (  );
	$s -> column_name = 'virtuemart_media_id';
	$s -> table_name = '#__virtuemart_medias';
	$s -> condition_column = 'file_url';

	$results = self::get_Id ( $productId, $s );
	
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
		//$values = array ( $db -> quote ( $image -> url ), $db -> quote( $image -> url_thumb ), 1, date ( 'd.m.Y. H-i-s' ) );	test quote array
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
		return self::getProduct_Ids ( $productId, $s );
	}

//=====================	Data bases	================================	
	static function get_Id ( $identifier, $s ) {
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
		// Return array or null
		return $db -> loadColumn (  );
	}

	static function create_ ( $columns, $values, $table_name ) {
		$db = JFactory::getDbo (  );
		$query = $db -> getQuery ( true );
		 
		$query
			-> insert ( $db -> quoteName ( $table_name ) ) 
			-> columns ( $db -> quoteName ( $columns ) ) 
			// -> values ( implode ( ',', $values ) );	test quote array
			-> values ( implode ( ',', $db -> quote ( $values ) ) );
		
		// Установка запроса для последующего выполнения. Можно указать смещение и лимит
		// @QUESTION: смещение и лимит для выборки понятны, а для вставки, например?
		$db -> setQuery ( $query );
		// Возвращает КУРСОР и ЛОЖЪ в случае неудачи
		// @QUESTION: что такое КУРСОРЪ?
		if ( ! $db -> execute (  ) ) {
			// Не получилось создать. Надо что-то делать
			//$db -> getErrorNum (  );
			//$db -> getErrorMessage (  );
			return null;
		}
		
		// Возвращаем последний добавленный ИД
		return $db -> insertid (  );
	}

	static function bind_ ( $productId, $_id, $s ) {
		$db = JFactory::getDbo (  );
		$query = $db -> getQuery ( true );
		
		$columns = array ( $s -> binder, $s -> bindee );
		$values = array ( $db -> quote ( $productId ), $db -> quote ( $_id ) );
		
		$query
			-> insert ( $db -> quoteName ( $s -> table_name ) )
			-> columns ( $db -> quoteName ( $columns ) )
			-> values ( implode ( ',', $values ) );
		
		$db -> setQuery ( $query );
		$result = $db -> execute (  );
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
		$result = $db -> execute (  );
	}

	/*		Для получения сущностей, соответствующих заданной. Например изображений продукта		
	*/
	static function getProduct_Ids ( $productId, $s ) {
		$db = JFactory::getDbo (  );
		$query = $db -> getQuery ( true );

		$query -> select ( $db -> quoteName ( $s -> column_name ) );
		$query -> from ( $db -> quoteName ( $s -> table_name ) );
		$query -> where ( $db -> quoteName ( $s -> condition_column ) . ' = ' . $db -> quote ( $productId ) );

		$db -> setQuery ( $query );
		// В случае неудачи возвращает null
		// @QUESTION: В смысле ошибки или пустого результата?
		return $db -> loadColumn (  );
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
	
	public function catagories (  ) {
		
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
}