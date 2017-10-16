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
            <legend><?php echo JText::_('COM_RABBIT_CHECK_DETAILS'); ?></legend>
            <div class="row-fluid">
                <div class="span6">
                    <?php foreach ($this->form->getFieldset() as $field): ?>
                        <div class="control-group">
                            <div class="control-label"><?php echo "control-label"; ?></div>
                            <div class="controls"><?php echo "controls"; ?></div>
							<?php //var_dump ($field); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </fieldset>
		
		<?php if ( $this -> csv ) { ?>
        <fieldset>
			<h2><legend><?php echo JText::_('COM_RABBIT_CHECK_DETAILS') . ': Список изменений, которые будут внесены'; ?></legend></h2>
			
			<?php
				/*
					Здесь выводим только изменения которые будут внесены
					В сводном виде
					Полная таблица изменений
				*/
			?>
			
			<?php
				echo "<h2>Full product table</h2>";
				echo "<table class='full-product-list' border='1'><tr>";
				foreach ( $this -> csv -> headers (  ) as $header ) {
					echo "<th>$header</th>";
				}
				echo "</tr>";

				foreach ( $this -> csv -> data (  ) as $i => $row ) {
					
					echo "<tr>";
					
					foreach ( $row as $j => $cell ) {
						
						$cellContent = mb_strlen ( $cell ) < 128 ? $cell : mb_substr ( $cell, 0, 128, "UTF-8" );
						echo "<td>$cellContent</td>";
					}
					
					echo "</tr>";
				}
				echo "</table>";
			?>
			
        </fieldset>
		<?php } ?>
    </div>
    <input type="hidden" name="task" value="rabbit.import" />
    <?php echo JHtml::_('form.token'); ?>
</form>