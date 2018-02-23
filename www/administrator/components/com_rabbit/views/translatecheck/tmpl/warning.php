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
            <legend><?php echo JText::_('COM_RABBIT_TRANLATE_CHECK_WARNING_DETAILS'); ?></legend>
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
				?>
				
            </div>
        </fieldset>
    </div>
    <input type="hidden" name="task" value="rabbit" />
    <?php echo JHtml::_('form.token'); ?>
</form>