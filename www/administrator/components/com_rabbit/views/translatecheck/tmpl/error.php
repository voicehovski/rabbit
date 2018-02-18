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
            <legend><?php echo JText::_('COM_RABBIT_TRANLATE_CHECK_ERROR_DETAILS'); ?></legend>
            <div class="row-fluid">
                <div class="span6">
					<p>Список ошибок</p>
                </div>
            </div>
        </fieldset>
    </div>
    <input type="hidden" name="task" value="rabbit" />
    <?php echo JHtml::_('form.token'); ?>
</form>