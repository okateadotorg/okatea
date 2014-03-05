<?php
/*
 * This file is part of Okatea.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Okatea\Tao\Forms\Statics\FormElements as form;

$view->extend('Builder/Admin/Templates/Builder');

?>

<form action="<?php echo $view->generateUrl('Builder_index', array('step' => $stepper->getCurrentStep())) ?>" method="post">

	<p>Vous allez créer un package pour la version <strong><?php echo $version ?></strong></p>

	<p>Quel est le type de cette version ?</p>
	<ul class="col field">
		<li><label for="type_stable"><?php echo form::radio(array('type', 'type_stable'), 'stable', ($type == 'stable')) ?> Stable</label></li>
		<li><label for="type_dev"><?php echo form::radio(array('type', 'type_dev'), 'dev', ($type == 'dev')) ?> Développement</label></li>
	</ul>

	<p><?php echo form::hidden('form_sent', 1) ?>
	<input type="submit" value="<?php _e('c_c_next') ?>" /></p>
</form>
