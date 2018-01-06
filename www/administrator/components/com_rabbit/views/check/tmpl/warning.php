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
            <legend><?php echo JText::_('COM_RABBIT_CHECK_WARNING_HEADER'); ?></legend>
            <div class="row-fluid">
                <div class="span6">
					
					
                    <?php foreach ($this->form->getFieldset() as $field): ?>
                        <div class="control-group">
                            <div class="control-label"><?php echo "control-label"; ?></div>
                            <div class="controls"><?php echo "controls"; ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
				<div style="clear:both"></div>
				<?php
					// This template warning.php, so template warning_subtemplate.php can be load like
					// $this -> loadTemplate ( 'subtemplate' );
					// And echo may be needed
					echo "<legend>" . JText::_('COM_RABBIT_CHECK_WARNING_CELL_ERROR_HEADER') . "</legend>";
					echo "<div class='cell-errors'>";
					foreach ( $this -> cellErrors as & $error ) {
						echo "<p>";
						echo "[ {$error -> row (  )} : {$error -> column (  )} ] - {$error -> value (  )} - [ {$error -> comment (  )} ]";
						echo "</p>";
					}
					echo "</div>";
					unset ( $error );
					
					
					echo "<legend>" . JText::_('COM_RABBIT_CHECK_WARNING_STRUCTURAL_ERROR_HEADER') . "</legend>";
					echo "<div class='structural-errors'>";
					foreach ( $this -> structuralErrors as & $error ) {	//array
						$indexes = $error -> rowIndexes (  );
						if ( count ( $indexes ) == 1 ) {
							$rows = $indexes [0];
						} else {
							$rows =	$error -> isRange (  ) ? implode ( ", ", $indexes ) : $indexes[0] . " - " . $indexes[count ( $indexes )];
						}
						echo "<p>";
						echo "[$rows] : {$error -> value (  )} [ {$error -> comment (  )} ]";
						echo "</p>";
					}
					echo "</div>";
					unset ( $error );
					
					echo "<legend>" . JText::_('COM_RABBIT_CHECK_WARNING_WHOLE_DATA_HEADER') . "</legend>";
					echo "<table class='full-product-list' border='1'><tr>";
					foreach ( $this -> csv -> headers (  ) as $header ) {
						echo "<th>$header</th>";
					}
					echo "</tr>";
					
					foreach ( $this -> csv -> data (  ) as $i => $row ) {
						
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
						} else {
							echo "<tr>";
						}
						
						foreach ( $row as $j => $cell ) {
							
							$cError = array_filter (
								$this -> cellErrors,
								function ( $elem ) use ( $i, $j ) {
									return $elem -> row (  ) == $i && $elem -> column (  ) == $j;
								}
							);
							
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
	<?php
		//echo "SESSION<br/>";
		//print_r ( RabbitHelper::restore_variable ( 'import_data' ) );
	?>
    <input type="hidden" name="task" value="" />
    <?php echo JHtml::_('form.token'); ?>
</form>