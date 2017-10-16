<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_orangeei
 *
 * @copyright   Copyright (C) 2005 - 2015 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
 
// No direct access
defined('_JEXEC') or die('Restricted access');

JFactory::getDocument()->addStyleDeclaration(
	'
	table.full-product-list {
		width: 100%;
	}
	.full-product-list th {
		width: auto;
	}
	.wrong-cell {
		background-color: orange;
	}
	.wrong-row {
		background-color: red;
	}
	'
);
?>
<form action="<?php echo JRoute::_('index.php?option=com_rabbit'); ?>"
    method="post" name="adminForm" id="adminForm">
    <div class="form-horizontal">
        <fieldset class="adminform">
            <legend><?php echo JText::_('COM_RABBIT_CHECK_ERROR_DETAILS'); ?></legend>
            <div class="row-fluid">
                <div class="span6">
                </div>
				
					<?php
						if ( $this -> check_status > 2 ) {
							
							echo "Common data error";
							return;
						}
					?>
				
					<?php
					echo "<h2>Summary</h2>";
					echo "<h3>Cell errors [row:column] - value - [ comment ]</h3>";
					// @IDEA: cellErrors can be a simple array, so that items method is exess. In this case method getByRCIndexes is redundant too
					foreach ( $this -> cellErrors as & $error ) {
						echo "[{$error -> row (  )}:{$error -> column (  )}] - {$error -> value (  )} - [ {$error -> comment (  )} ]";
						echo "<br/>";
					}
					unset ( $error );
					
					
					echo "<h3>Structural errors [rows] : value [ comment ]</h3>";
					foreach ( $this -> structuralErrors as & $error ) {	//array
						$indexes = $error -> rowIndexes (  );	//should always return array of whole wrong indexes
						if ( count ( $indexes ) == 1 ) {
							$rows = $indexes [0];
						} else {
							$rows =	$error -> isRange (  ) ? implode ( ", ", $indexes ) : $indexes[0] . " - " . $indexes[count ( $indexes )];
						}
						//value (  ) can contain sku, product code, or product code with color or something else, but has to contain something
						echo "[$rows] : {$error -> value (  )} [ {$error -> comment (  )} ]";
						echo "<br/>";
					}
					unset ( $error );
					
					echo "<h2>Full product table</h2>";
					echo "<table class='full-product-list' border='1'><tr>";
					foreach ( $this -> csv -> headers (  ) as $header ) {
						echo "<th>$header</th>";
					}
					echo "</tr>";
					
					// @QUESTION: make $row and $cell links?
					// @TODO: здесь пиздей. Нужно как-то привести в порядок
					foreach ( $this -> csv -> data (  ) as $i => $row ) {
						
						//$sErrors = $this -> structuralErrors -> getByRowIndex ( $i );	//array
						$sErrors = array_filter (
							$this -> structuralErrors,
							function ( $elem ) use ( & $i ) {
								return in_array ( $i, $elem -> rowIndexes (  ) );
							}
						);	//array
						
						if ( ! empty ( $sErrors ) ) {
							$tooltip = array_reduce (
								$sErrors,
								function ( $carry, $item ) { return $carry . "::" . $item -> comment (  ); },
								count ( $sErrors )
							);
							echo "<tr class='wrong-row' title='$tooltip'>";
						} else {	// @NOTE: remove this for show wrong rows only. Another way - hide rows with css, assigning a class here
							echo "<tr>";
						}
						
						foreach ( $row as $j => $cell ) {
							
							//$cError = $this -> cellErrors -> getByRCIndexes ( $i, $j );
							$cError = array_filter (
								$this -> cellErrors,
								function ( $elem ) use ( $i, $j ) {
									return $elem -> row (  ) == $i && $elem -> column (  ) == $j;
								}
							);
							// array_filter возвращает массив, так что нужно выделить скалярное значение
							// Но array_filter сохраняет индексы, поэтому нужно извлекать через жопу
							
							$cellContent = mb_strlen ( $cell ) < 128 ? $cell : mb_substr ( $cell, 0, 128, "UTF-8" );
							$cError = array_reduce ( $cError, function ( $carry, $item ) { return $item; } );
							if ( ! empty ( $cError ) ) {
								$tooltip = $cError -> comment (  );
								echo "<td class='wrong-cell' title='$tooltip'>$cellContent</td>";
							} else {
								echo "<td>$cellContent</td>";
							}
						}
						
						echo "</tr>";
					}
					echo "</table>";
					?>
				
            </div>
        </fieldset>
    </div>
    <input type="hidden" name="task" value="" />
    <?php echo JHtml::_('form.token'); ?>
</form>