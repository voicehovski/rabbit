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
					<p>Список ошибок</p>
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