<?php 
// No direct access
defined('_JEXEC') or die;

JFactory::getDocument()->addScriptDeclaration(
	'
	Rabbit.some_function = function() {
		var form = document .getElementById ( "some_id" );
		//...
	};

	jQuery ( document ) .ready ( function ( $ ) {
		
	} );
	'
);

JFactory::getDocument()->addStyleDeclaration(
	'
	div.filter-section {
		margin-bottom: 16px;
	}
	
	.filter-section > div.title {
		background-color: #a3a3a380;
		text-align: center;
		line-height: 2.5em;
		margin-bottom: 8px;	
	}
	
	.filter-section div.option {
		line-height: 2.5em;	
	}
	
	.filter-section div.option input[type=checkbox] {
		margin: 0 8px 0 0;
	}
	'
);

?>


<?php

//  Возвращает пересечение переданных множеств, игнорируя пустые
    function intersect_filters ( $filters ) {

        $filter_total = array (  );

        foreach ( $filters as $v ) {
            if ( empty ( $v ) ) {
                continue;
            }
            $filter_total = array_intersect ( $v, $v );
            break;
        }

        foreach ( $filters as $v ) {
            if ( empty ( $v ) ) {
                continue;
            }
            $filter_total = array_intersect ( $filter_total, $v );
        }
        return $filter_total;
    }

    function calculate_property_produst_lists ( $filter_options, $properties_data ) {

        $result = array (  );

        foreach ( $filter_options as $property_id => $value_code_list ) {

            $property_product_list = array (  );

            if ( ! isset ( $properties_data [$property_id] ) || ! isset ( $properties_data [$property_id][1] ) ) {
                continue;
            }

            foreach ( $value_code_list as $value_code ) {

                if ( ! isset ( $properties_data [$property_id][1][$value_code] ) || ! isset ( $properties_data [$property_id][1][$value_code][1] ) ) {
                    continue;
                }

                $value_product_list = $properties_data [$property_id][1][$value_code][1];
                $property_product_list = array_unique(array_merge($property_product_list, $value_product_list), SORT_NUMERIC);
            }

            $result [$property_id] = $property_product_list;
        }

        return $result;
    }

//$filter_total = count ( $filter_counter, COUNT_RECURSIVE );
if ( $filter_options ) {
    $filter_counter = calculate_property_produst_lists($filter_options, $product_properties);
    $filter_total = intersect_filters ( $filter_counter );
}


echo "<form action='" . JUri::getInstance (  ) -> getPath (  ) . "'>";
    foreach ( $product_properties as $property_id => $property_data ) {
        //echo "<div class='filter-section'><div class='title'>{$filter_section['lcf_name']}: {$filter_section['lcf_value']}";
        echo "<div class='filter-section'>";
		echo "<div class='title'>" . JText::_ ( $property_data [0] ) . "</div>";	// See also JText::sprintf

        foreach ( $property_data [1] as $value_code => $value_data ) {

            $k = false;
			if ( $filter_options && isset ( $filter_options [$property_id] ) ) {
				$k = array_search ( $value_code, $filter_options [$property_id] );
			}

            echo "<div class='option'><input type='checkbox' name='" . $property_id . "' value='" . $value_code . ($k === false ? "'>" : "' checked >");
			echo $value_data [0];

			if ( $filter_options ) {
                if ( $k === false ) {
                    if ( $filter_counter [$property_id] ) {
                        $a = array_unique ( array_merge ( $filter_counter [$property_id], $value_data [1] ), SORT_NUMERIC );
                        $temp = $filter_counter [$property_id];
                        $filter_counter [$property_id] = $a;

                        echo " ( +" .
                            ( count (intersect_filters ( $filter_counter ) ) - count ( $filter_total ) )
                            . " )";

                        $filter_counter [$property_id] = $temp;
                    } else {
                        $filter_counter [$property_id] = array_unique ( $value_data [1], SORT_NUMERIC );
                        //$additional = count ( intersect_filters ( $filter_counter ) ) - count ( $filter_total );
                        $additional = count ( intersect_filters ( $filter_counter ) );
                        unset ( $filter_counter [$property_id] );
                        echo " ( " . $additional . " )";
                    }
                }
            } else {
                echo " ( " . count ( $value_data [1] ) . " )";
            }

            echo "</div>";
        }
        echo "</div>";
    }
	echo "<input type='submit' value='Применить'>";
	echo "</form>";
?>

