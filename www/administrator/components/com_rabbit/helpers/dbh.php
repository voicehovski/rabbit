<?php

defined('_JEXEC') or die('Restricted access');

class DBHelper {
	
	public static function import ( $group ) {
	
		foreach ( $group -> getAll (  ) as $product ) {
			
			
		}
	}
	
//====================	Products	===========================
public static function getProductId ( $productSku ) {
	$s = new stdClass (  );
	$s -> column_name = 'virtuemart_product_id';
	$s -> table_name = '#__virtuemart_products';
	$s -> condition_column = 'product_sku';

	$results = $this -> get_Id ( $productId, $s );
	
	if ( $results ) {	//Что возвращает loadColumn в случае отсутствия результата?
		if ( count ( $results > 1 ) ) {
			//log warning	более одного изображения с таким именем в базе
		}
		return $results [0];
	} else {
		return null;
	}	
}

public static function updateProduct ( $id, $product ) {
	
}

public static function createProduct ( $id, $product ) {
	$columns = array ( 'product_sku', 'product_in_stock', 'product_availability', 'published', 'created_on' ); 
	//$values = array ( $db -> quote ( $image -> url ), $db -> quote( $image -> url_thumb ), 1, date ( 'd.m.Y. H-i-s' ) );	test quote array
	$values = array ( $product -> identifier (  ), 1, "1", 1, date ( 'd.m.Y. H-i-s' ) );

	$this -> create_ ( $columns, $values, '#__virtuemart_products' );
}
//====================	Images	===========================
public static function getImageId ( $image_url ) {
	$s = new stdClass (  );
	$s -> column_name = 'virtuemart_media_id';
	$s -> table_name = '#__virtuemart_medias';
	$s -> condition_column = 'file_url';

	$results = $this -> get_Id ( $productId, $s );
	
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

		$this -> create_ ( $columns, $values, '#__virtuemart_medias' );
	}

	public static function bindImage ( $productId, $imageId ) {
		$s = new stdClass (  );
		$s -> binder = 'virtuemart_product_id';
		$s -> bindee = 'virtuemart_media_id';
		$s -> table_name = '#__virtuemart_product_medias';
		
		$this -> bind_ ( $productId, $imageId, $s );	
	}

	public static function unbindImage ( $productId, $imageId ) {
		$s = new stdClass (  );
		$s -> binder = 'virtuemart_product_id';
		$s -> bindee = 'virtuemart_media_id';
		$s -> table_name = '#__virtuemart_product_medias';
		
		$this -> unbind_ ( $productId, $imageId, $s );
	}

	public static function getProductImages ( $productId ) {
		$s = new stdClass (  );
		$s -> column_name = 'virtuemart_media_id';
		$s -> table_name = '#__virtuemart_product_medias';
		$s -> condition_column = 'virtuemart_product_id';
		return $this -> getProduct_Ids ( $productId, $s );
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
		$db -> execute (  );
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

		$query -> select ( $db -> quoteName ( $s -> column_name );
		$query -> from ( $db -> quoteName ( $s -> table_name ) );
		$query -> where ( $db -> quoteName ( $s -> condition_column ) . ' = ' . $db -> quote ( $productId ) );

		$db -> setQuery ( $query );
		// В случае неудачи возвращает null
		// @QUESTION: В смысле ошибки или пустого результата?
		return $db -> loadColumn (  );
	}

}