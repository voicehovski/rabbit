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
		<?php if ( $this -> error_data ) { ?>
		<fieldset>
			<legend><?php echo JText::_('COM_RABBIT_CHECK_DETAILS') . ': Список обнаруженных ошибок'; ?></legend>
			<?php
			$csv_errors = $this -> csv_data -> errors (  );
			foreach ( $csv_errors as $row => $errorList ) {
				echo "<div>{$error -> rowIndex (  )}:{$error -> colIndex (  )} - {$error -> value (  )} - [ {$error -> comment (  )} ]</div>";
			}
			?>
        </fieldset>
		<?php } ?>
		<?php if ( $this -> import_data ) { ?>
        <fieldset>
			<legend><?php echo JText::_('COM_RABBIT_CHECK_DETAILS') . ': Список изменений, которые будут внесены'; ?></legend>
			
			<table>
			<tr><th><?php implode ( "</th><th>", $this -> csv_data -> headers (  ) ); ?></th></tr>
			
			<?php
			// create csv (file, validator) check and make index
			// 
			// csv_data -> assocDataList (  ), assocData -> get ( 'code' );
			foreach ( $this -> csv_data -> data (  ) as $rowIndex => $dataLine ) {	//data should return 2xarray of string
				
				$mle = $this -> multilineErrors -> getByRowIndex ( $rowIndex );	//has to implement toString
				
				if ( ! empty ( $mle ) ) { 
					echo "<tr class='wrong-row' title='$mle'>";
				} else { 
					echo "<tr>";
				}
				
				foreach ( $dataLine as $colIndex => $dataCell ) {
					
					$se = $this -> csv_data -> error ( $rowIndex, $colIndex );	//has to implement toString
					
					if ( ! empty ( $se ) ) {
						echo "<td class='wrong-cell' title='$se'>$dataCell</td>";
					} else { 
						echo "<td>$dataCell</td>"
					} 
				}
				</tr>
			}
			?>
        </fieldset>
		<?php } ?>
    </div>
    <input type="hidden" name="task" value="rabbit.import" />
    <?php echo JHtml::_('form.token'); ?>
</form>