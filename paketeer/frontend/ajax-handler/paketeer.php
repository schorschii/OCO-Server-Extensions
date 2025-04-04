<?php
$SUBVIEW = 1;
if(!isset($db) || !isset($cl)) die();

$classPath = '\\Paketeer\\Software\\';

try {

	// create package if requested
	if(!empty($_POST['software'])
	&& !empty($_POST['links'])) {
		if(empty($_POST['family_name']) || empty($_POST['version'])) {
			throw new InvalidRequestException(LANG('please_fill_required_fields'));
		}
		if(!empty($db->selectAllPackageByPackageFamilyNameAndVersion($_POST['family_name'], $_POST['version']))) {
			throw new InvalidRequestException(LANG('package_exists_with_version'));
		}

		$class = $classPath.$_POST['software'];
		$software = new $class();
		if(!$software instanceof \Paketeer\Software\BaseSoftware)
			throw new RuntimeException('This is not a Paketeer software class');
		$insertId = $software->createPackage($cl, explode('|',$_POST['links']), $_POST['family_name'], $_POST['version'], $_POST['notes']??'');
		die(strval(intval($insertId)));
	}

} catch(UnexpectedValueException|InvalidRequestException $e) {
	header('HTTP/1.1 400 Invalid Request');
	die(htmlspecialchars($e->getMessage()));
} catch(NotFoundException $e) {
	header('HTTP/1.1 404 Not Found');
	die(LANG('not_found'));
} catch(PermissionException $e) {
	header('HTTP/1.1 401 Forbidden');
	die(LANG('permission_denied'));
} catch(Exception $e) {
	header('HTTP/1.1 500 Internal Server Error');
	die(htmlspecialchars($e->getMessage()));
}

header('HTTP/1.1 400 Invalid Request');
die(LANG('unknown_method'));
