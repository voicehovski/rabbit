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
			<?php// foreach ( $this -> error_data as $error ) { ?>
				<div>
					<?php// echo $error; ?>
				</div>
			<?php// } ?>
        </fieldset>
		<?php } ?>
		<?php if ( $this -> import_data ) { ?>
        <fieldset>
			<legend><?php echo JText::_('COM_RABBIT_CHECK_DETAILS') . ': Список изменений, которые будут внесены'; ?></legend>
			<?php// foreach ( $this -> import_data as $is ) { ?>
				<div>
					<?php //echo implode ( " :: ", $is ); ?>
					<?php print_r ( $this -> import_data ); ?>
					<?php print_r ( $this -> logical_errors ); ?>
				</div>
			<?php } ?>
        </fieldset>
		<?php } ?>
    </div>
    <input type="hidden" name="task" value="rabbit.import" />
    <?php echo JHtml::_('form.token'); ?>
</form>