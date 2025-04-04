<?php
$SUBVIEW = 1;
require_once('../../loader.inc.php');
require_once('../session.inc.php');
?>

<input type='hidden' id='txtPaketeerSoftware' value='<?php echo htmlspecialchars($_GET['software']??'',ENT_QUOTES); ?>'></input>
<input type='hidden' id='txtPaketeerDownloadLinks' value='<?php echo htmlspecialchars($_GET['links']??'',ENT_QUOTES); ?>'></input>
<table id='frmPaketeerCreatePackage' class='fullwidth aligned form'>
	<tr class='nospace'>
		<th><?php echo LANG('package_family_name'); ?></th>
		<td>
			<datalist id='lstPackageFamilies'>
				<?php foreach($cl->getPackageFamilies() as $family) { ?>
					<option value='<?php echo htmlspecialchars($family->name,ENT_QUOTES); ?>'><?php echo htmlspecialchars($family->name); ?></option>
				<?php } ?>
			</datalist>
			<input id='txtPaketeerPackageFamily' list='lstPackageFamilies' autofocus='true'></input>
		</td>
	</tr>
	<tr class='nospace'>
		<th><?php echo LANG('version'); ?></th>
		<td><input type='text' autocomplete='new-password' id='txtPaketeerVersion' value='<?php echo htmlspecialchars($_GET['name']??'',ENT_QUOTES); ?>'></input></td>
	</tr>
	<tr>
		<th><?php echo LANG('description'); ?></th>
		<td><textarea autocomplete='new-password' id='txtPaketeerNotes'></textarea></td>
	</tr>
</table>

<div class='controls right'>
	<button id='btnCloseDialog' onclick='hideDialog();showLoader(false);showLoader2(false);'><img src='img/close.dyn.svg'>&nbsp;<?php echo LANG('close'); ?></button>
	<?php echo progressBar(0, 'prgPaketeerCreatePackage', 'prgPaketeerCreatePackageText', 'hidden big animated', 'width:100%'); ?>
	<button id='btnPaketeerCreatePackage' class='primary' onclick='paketeerCreatePackage(
		txtPaketeerSoftware.value,
		txtPaketeerDownloadLinks.value,
		txtPaketeerPackageFamily.value,
		txtPaketeerVersion.value,
		txtPaketeerNotes.value,
	)'><img src='img/send.white.svg'>&nbsp;<?php echo LANG('create'); ?></button>
</div>
