<?php
$SUBVIEW = 1;

if(!$cl->checkPermission(null, 'InstallTool', false))
	die('<div class="alert warning">Sie sind nicht berechtigt diese Anwendung zu verwenden.</div>');

$settings = json_decode($db->settings->get('install-tool'), true);
if(empty($settings) || empty($settings['oco'])) die('<div class="alert warning">Konfiguration nicht gefunden.</div>');

$selectedForm = null;
if(!empty($_GET['form'])) switch($_GET['form']) {
	case 'installation':
	case 'remove-installation':
		$selectedForm = $_GET['form'];
}

function getPackageGroupsOptions($parentId=null, $depth=0) {
	global $cl, $settings;
	$html = '';
	$subgroups = $cl->getPackageGroups($parentId);
	if(count($subgroups) == 0) return '';
	foreach($subgroups as $group) {
		$selected = '';
		if($settings['oco']['package-group-id-base-windows'] == $group->id) $selected = 'selected';
		$html .= "<option ".$selected." value='".$group->id."'>".trim(str_repeat('-',$depth).' '.htmlspecialchars($group->name))."</option>";
		$html .= getPackageGroupsOptions($group->id, $depth++);
	}
	return $html;
}
?>

<h1><img src='img/install-tool.dyn.svg'>Install-Tool</h1>

<style>
.controls button {
	text-align: left;
	padding: 10px;
	display: flex !important;
	align-items: center;
	opacity: 0.4;
}
.controls button.active,
.controls button:focus,
.controls button:hover {
	opacity: 1;
}
.controls button img {
	height: 55px;
	float: left;
	margin-right: 10px;
}
table.box input:not([type=checkbox]):not([type=radio]),
table.box button, table.box select {
	width: 100%;
}
.box {
	background-color: white;
	border-radius: 2px;
	border: 1px solid rgba(0, 0, 0, 0.2);
	box-shadow: 1px 1px 4px 0 rgba(0,0,0,0.08);
	padding: 20px !important;
	margin-bottom: 5% !important;
}
.box > *:first-child {
	margin-top: 0px !important;
}
.box > *:last-child {
	margin-bottom: 0px !important;
}
@media(prefers-color-scheme: dark) {
	.box {
		background-color: #22343C;
		border: 1px solid #485764;
	}
}
</style>
<div class='controls'>
	<button onclick='installShowPage("installation")' class='<?php if($selectedForm=='installation' || empty($selectedForm)) echo 'active'; ?>'>
		<img src='img/automagical.png'>
		<div>
			<b>Vollautomatische Installation</b>
			<br><small>anhand MAC-Adresse/Seriennummer/UUID</small>
		</div>
	</button>
	<button onclick='installShowPage("remove-installation")' class='<?php if($selectedForm=='remove-installation' || empty($selectedForm)) echo 'active'; ?>'>
		<img src='img/installation-remove.png'>
		<div>
			<b>Austragen</b>
			<br><small>Alle Einträge entfernen</small>
		</div>
	</button>
	<div class='filler'></div>
</div>

<?php if($selectedForm=='installation') { ?>

<div class='details-abreast'>
<div>
	<h2>Automagische Installation</h2>
	<table id='installForm' class='box'>
		<tr>
			<td>Hostname:</td>
			<td>
				<input type='text' id='txtInstallHostname' placeholder='BDV100' class='monospace' autofocus='true' onkeydown='if(event.key=="Enter"){focusNext(installForm)}'></input>
			</td>
		</tr>
		<tr>
			<td>Identifier:</td>
			<td>
				<input type='text' id='txtInstallIdentifier' placeholder='MAC-Adresse / Seriennummer / UUID' class='monospace' onkeydown='if(event.key=="Enter"){focusNext(installForm)}'></input>
			</td>
		</tr>
		<tr>
			<td>Basis-Pakete:</td>
			<td>
				<select id='sltPackageGroup' onkeydown='if(event.key=="Enter"){focusNext(installForm)}'>
					<?php echo getPackageGroupsOptions(null); ?>
				</select>
			</td>
		</tr>
		<tr><td colspan='2'>&nbsp;</td></tr>
		<tr>
			<td></td>
			<td>
				<label><input type='checkbox' id='chkInstallRemoveOld' value='1'>&nbsp;Vorhandenen gleichnamigen Computer löschen</input></label>
			</td>
		</tr>
		<tr><td colspan='2'>&nbsp;</td></tr>
		<tr>
			<td></td>
			<td>
				<button onclick='installDoManualInstall()'><b>Anlegen</b></button>
			</td>
		</tr>
	</table>
</div>
<div>
	<h2>Fortschritt</h2>
	<table class='box'>
		<td>
			<div id='divInstallStatus'>
				<div class='alert info'>Bitte starten Sie den Installationsinitiierungsprozess.</div>
			</div>
		</td>
	</table>
</div>
</div>

<?php } elseif($selectedForm=='remove-installation') { ?>

<div class='details-abreast'>
<div>
	<h2>Computer austragen</h2>
	<table id='installForm' class='box'>
		<tr>
		<td>Identifier:</td>
			<td>
				<input type='text' id='txtRemoveIdentifier' placeholder='MAC-Adresse / Seriennummer / UUID' class='monospace' onkeydown='if(event.key=="Enter"){focusNext(installForm)}'></input>
			</td>
		</tr>
		<tr><td colspan='2'>&nbsp;</td></tr>
		<tr>
			<td></td>
			<td>
				<button onclick='installDoRemove()'><b>Austragen</b></button>
			</td>
		</tr>
	</table>
</div>
<div>
	<h2>Fortschritt</h2>
	<table class='box'>
		<td>
			<div id='divInstallStatus'>
				<div class='alert info'>Bitte initiieren Sie den Computerterminierungsprozess.</div>
			</div>
		</td>
	</table>
</div>
</div>

<?php } else { ?>

	<div class='alert hint'>
		<b>Herzlich Willkommen beim Install-Tool!</b>
		<br>Das Install-Tool automatisiert die Workflows rund um die In- und Außerbetriebnahme von Client-Computern via OCO.
		<br>Bitte wählen Sie eine Aufgabe aus, um zu beginnen.
	</div>

<?php } ?>

<div id='divInstallProgressBar' style='display:none'>
	<?php echo progressBar(100, 'prgInstall', 'prgInstallText', 'animated stretch', 'height:30px;width:auto'); ?>
</div>
