<?php
$SUBVIEW = 1;
require_once('../../loader.inc.php');
require_once('../session.inc.php');
?>

<input type='hidden' name='software' value='<?php echo htmlspecialchars($_GET['software']??'',ENT_QUOTES); ?>'></input>
<input type='hidden' name='links' value='<?php echo htmlspecialchars($_GET['links']??'',ENT_QUOTES); ?>'></input>
<table class='fullwidth aligned form'>
	<tr class='nospace'>
		<th><?php echo LANG('package_family_name'); ?></th>
		<td>
			<datalist id='lstPackageFamilies'>
				<?php foreach($cl->getPackageFamilies() as $family) { ?>
					<option value='<?php echo htmlspecialchars($family->name,ENT_QUOTES); ?>'><?php echo htmlspecialchars($family->name); ?></option>
				<?php } ?>
			</datalist>
			<input name='package_family' list='lstPackageFamilies' autofocus='true'></input>
		</td>
	</tr>
	<tr class='nospace'>
		<th><?php echo LANG('version'); ?></th>
		<td><input type='text' autocomplete='new-password' name='version' value='<?php echo htmlspecialchars($_GET['name']??'',ENT_QUOTES); ?>'></input></td>
	</tr>
	<tr>
		<th><?php echo LANG('description'); ?></th>
		<td><textarea autocomplete='new-password' name='notes'></textarea></td>
	</tr>
</table>

<div class='controls right'>
	<button class='dialogClose'><img src='img/close.dyn.svg'>&nbsp;<?php echo LANG('close'); ?></button>
	<?php echo Html::progressBar(0, 'prgPaketeerCreatePackage', 'prgPaketeerCreatePackageText', 'hidden big animated', 'width:100%'); ?>
	<button class='primary' name='create'><img src='img/send.white.svg'>&nbsp;<?php echo LANG('create'); ?></button>
</div>
