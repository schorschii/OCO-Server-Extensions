<?php
$SUBVIEW = 1;
if(!isset($db) || !isset($cl)) die();

$classPath = '\\Paketeer\\Software\\';

if(!empty($_GET['software'])) {
	try {
		$class = $classPath.$_GET['software'];
		$software = new $class();
		if(!$software instanceof \Paketeer\Software\BaseSoftware)
			throw new RuntimeException('This is not a Paketeer software class');
	} catch(NotFoundException $e) {
		die("<div class='alert warning'>".LANG('not_found')."</div>");
	} catch(PermissionException $e) {
		die("<div class='alert warning'>".LANG('permission_denied')."</div>");
	} catch(Exception $e) {
		die('<div class="alert error">'.htmlspecialchars($e->getMessage()).'</div>');
	}
?>

<h1><img src='img/img.d/paketeer.dyn.svg'><span id='page-title'><a <?php echo explorerLink('views/paketeer.php'); ?>>Paketeer</a> » <?php echo htmlspecialchars($software->getDisplayName()); ?></span></h1>

<table id='tblPaketeerData' class='list searchable sortable savesort actioncolumn fullwidth'>
	<thead>
		<tr>
			<th class='searchable sortable'><?php echo LANG('name'); ?></th>
			<th class='searchable sortable'><?php echo LANG('created'); ?></th>
			<th class='searchable sortable'><?php echo LANG('size'); ?></th>
			<th><?php echo LANG('action'); ?></th>
		</tr>
	</thead>
	<tbody>
	<?php
	foreach($software->getVersions() as $u) { ?>
		<tr>
			<td><?php echo $u['title']; ?></td>
			<td sort_key='<?php echo strtotime($u['date']); ?>'><?php echo date('d.m.Y', strtotime($u['date'])); ?></td>
			<td sort_key='<?php echo $u['size']; ?>'><?php echo niceSize($u['size']); ?></td>
			<td>
				<?php if(!empty($u['links'])) { ?>
					<button title='Will ich haben!'
						onclick='paketeerDialogCreatePackage(this.getAttribute("software"),this.getAttribute("name"),this.getAttribute("links"))'
						software='<?php echo htmlspecialchars($_GET['software'],ENT_QUOTES); ?>'
						name='<?php echo htmlspecialchars($u['title'],ENT_QUOTES); ?>'
						links='<?php echo htmlspecialchars(implode('|',$u['links']),ENT_QUOTES); ?>'>
						<img src='img/download.dyn.svg'>
					</button>
				<?php } ?>
			</td>
		</tr>
	<?php } ?>
	</tbody>
</table>

<?php } else { ?>

	<h1><img src='img/img.d/paketeer.dyn.svg'><span id='page-title'>Paketeer</span></h1>

	<div class='actionmenu'>
		<?php foreach(Paketeer\Software\BaseSoftware::CLASSES as $className) {
			$class = $classPath.$className ?>
			<a <?php echo explorerLink('views/paketeer.php?software='.urlencode($className)); ?>>&rarr;&nbsp;<?php echo htmlspecialchars((new $class())->getDisplayName()); ?></a>
		<?php } ?>
	</div>

<?php } ?>
