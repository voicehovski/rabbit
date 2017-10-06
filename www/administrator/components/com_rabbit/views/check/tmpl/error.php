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

?>
<form action="<?php echo JRoute::_('index.php?option=com_rabbit'); ?>"
    method="post" name="adminForm" id="adminForm">
    <div class="form-horizontal">
        <fieldset class="adminform">
            <legend><?php echo JText::_('COM_RABBIT_CHECK_ERROR_DETAILS'); ?></legend>
            <div class="row-fluid">
                <div class="span6">
					<?php
					echo "<h2>Summary</h2>";
					echo "<h3>Cell errors</h3>";
					// @IDEA: cellErrors can be a simple array, so that items method is exess. In this case method getByRCIndexes is redundant too
					foreach ( $this -> cellErrors -> items (  ) as & $error ) {
						echo "{$error -> row (  )}:{$error -> column (  )} - {$error -> value (  )} - [ {$error -> comment (  )} ]";
						echo "<br/>";
					}
					unset ( $error );
					
					
					echo "<h3>Structural errors</h3>";
					foreach ( $this -> structuralErrors -> items (  ) as & $error ) {	//array
						$indexes = $error -> rowIndexes (  );	//should always return array of whole wrong indexes
						if ( count ( $indexes ) == 1 ) {
							$rows = $indexes [0];
						} else {
							$rows =	$error -> isRange (  ) ? implode ( ", ", $indexes ) : indexes[0] . " - " . $indexes[count ( $indexes )];
						}
						//value (  ) can contain sku, product code, or product code with color or something else, but has to contain something
						echo "$rows : {$error -> value (  )} [ {$error -> comment (  )} ]";
						echo "<br/>";
					}
					unset ( $error );
					
					echo "<h2>Full product table</h2>";
					echo "<table><tr>";
					foreach ( $this -> csv -> headers (  ) as $header ) {
						echo "<th>$header</th>";
					}
					echo "</tr>";
					
					// @QUESTION: make $row and $cell links?
					foreach ( $this -> csv -> data (  ) as $i => $row ) {
						
						$sErrors = $this -> structuralErrors -> getByRowIndex ( $i );	//array
						
						if ( ! empty ( $sErrors ) ) {
							$tooltip = array_reduce ( $sErrors, function ( $carry, $item ) { return $carry . "::" . $item -> comment (  ) }, count ( $sErrors ) );
							echo "<tr class='wrong-row' title='$tooltip'>";
						} else {	// @NOTE: remove this for show wrong rows only. Another way - hide rows with css, assigning a class here
							echo "<tr>";
						}
						
						for ( $row as $j => $cell ) {
							
							$cError = $this -> cellErrors -> getByRCIndexes ( $i, $j );
							
							if ( ! empty ( $cError ) ) {
								$tooltip = $se -> comment (  );
								echo "<td class='wrong-cell' title='$tooltip'>$cell</td>";
							} else {
								echo "<td>$cell</td>";
							}
						}
						
						echo "</tr>";
					}
					/*
					foreach ( $productList as $product ) {
						echo "<tr>";
						foreach ( $product as $property ) {
							echo "<td>$property</td>";
						}
						echo "</tr>";
					}
					*/
					echo "</table>";
					?>
					
					<?php
						foreach ( $this -> import_data -> get (  ) as $gid => $group ) {
							echo "<h2>$gid</h2>";
							echo "<table>";
							foreach ( $group -> getAll (  ) as $product ) {
								echo "<tr>";
								foreach ( $product -> get (  ) as $name => $value ) {
									echo "<td>$value</td>";
								}
								echo "</tr>";
							}
							echo "</table>";
						}
					?>
					<h2>Errors</h2>
					<?php foreach ( $this -> error_data -> error_data as $i => $e ) { ?>
					<div>
					<?php
						if ( empty ( $e ) ) {
							continue;
						}
						echo "[$i]:";
						foreach ( $e as $eei => $ee ) {
							echo "[$eei]: $ee ";
						}
					?>
					</div>
					<?php } ?>
					<h2>Logical errors</h2>
					<?php foreach ( $this -> logical_errors as $li => $le ) { ?>
					<div>
					<?php
						echo "[$li]";
						foreach ( $le as $lli => $lle ) {
							echo "[$lli]: $lle ";
						}
					?>
					</div>
					<?php } ?>
                </div>
            </div>
        </fieldset>
    </div>
    <input type="hidden" name="task" value="" />
    <?php echo JHtml::_('form.token'); ?>
</form>