<?php

namespace Paketeer\Software;

abstract class MicrosoftUpdateCatalog extends BaseSoftware {

	const URL = 'https://www.catalog.update.microsoft.com';

	protected function getUpdates(string $search, array $mustIncludes=[], bool $allPages=false, int $page=0) {
		$updates = [];
		$body = null;

		/*if($action) {
			$dArgument = $doc->getElementById('__EVENTARGUMENT');
			$dValidation = $doc->getElementById('__EVENTVALIDATION');
			$dViewState = $doc->getElementById('__VIEWSTATE');
			$dViewStateGenerator = $doc->getElementById('__VIEWSTATEGENERATOR');
			$body = http_build_query([
				'__EVENTTARGET' => $action,
				'__EVENTARGUMENT' => $dArgument ? $dArgument->getAttribute('value') : '',
				'__EVENTVALIDATION' => $dValidation ? $dValidation->getAttribute('value') : '',
				'__VIEWSTATE' => $dViewState ? $dViewState->getAttribute('value') : '',
				'__VIEWSTATEGENERATOR' => $dViewStateGenerator ? $dViewStateGenerator->getAttribute('value') : '',
			]);
		}*/

		$response = $this->apiCall('POST',
			self::URL.'/Search.aspx?'.http_build_query([
				'q' => $search, 'p' => $page
			]),
			$body,
			200,
			['Content-Type: application/x-www-form-urlencoded']
		);
		$doc = new \DOMDocument();
		@$doc->loadHTML($response);

		foreach($doc->getElementById('ctl00_catalogBody_updateMatches')->getElementsByTagName('tr') as $tr) {
			$tds = $tr->getElementsByTagName('td');
			if(count($tds) != 8) continue;

			$title = trim($tds[1]->getElementsByTagName('a')[0]->textContent);
			if(!empty($mustIncludes)) {
				foreach($mustIncludes as $mustInclude) {
					if(strpos($title, $mustInclude) === false)
						continue 2;
				}
			}

			// get download link
			$updateId = $tds[7]->getElementsByTagName('input')[0]->getAttribute('id');
			$response = $this->apiCall('POST',
				self::URL.'/DownloadDialog.aspx',
				http_build_query([
					'updateIDs' => json_encode([
						['size'=>0, 'languages'=>'', 'updateID'=>$updateId, 'uidInfo'=>$updateId]
					])
				]),
				200,
				['Content-Type: application/x-www-form-urlencoded']
			);
			$matches1 = [];
			preg_match_all("(https:\/\/catalog\.s\.download\.windowsupdate\.com\/[./\-_a-zA-Z0-9]+[^'])", $response, $matches1);
			$matches2 = [];
			preg_match_all("(https:\/\/catalog\.sf\.dl\.delivery.mp\.microsoft\.com\/[./\-_a-zA-Z0-9]+[^'])", $response, $matches2);
			$matches = array_merge($matches1[0], $matches2[0]);

			$updates[] = [
				'title' => $title,
				'date' => trim($tds[4]->textContent),
				'size' => trim($tds[6]->getElementsByTagName('span')[1]->textContent),
				'links' => $matches,
			];
		}
		$lastPage = $doc->getElementById('ctl00_catalogBody_nextPage');
		if($allPages && !$lastPage) {
			#error_log($doc->getElementById('ctl00_catalogBody_searchDuration')->textContent);
			$updates = $updates + getUpdates($search, $mustIncludes, true, $page+1);
		}
		return $updates;
	}

	function createPackage(\CoreLogic $cl, array $links, string $familyName, string $version, string $notes) {
		$files = [];
		$commands = [];
		foreach($links as $link) {
			$fileName = uniqid().'.msu';
			$tmpFilePath = '/tmp/'.$fileName;
			$this->downloadFile($link, $tmpFilePath);
			$files[$fileName] = $tmpFilePath;
			$commands[] = 'wusa '.$fileName.' /quiet /norestart';
		}

		$insertId = $cl->createPackage(
			$familyName, $version, null/*license_count*/, $notes,
			implode(' & ', $commands), '0,3221225781', 0/*install_procedure_post_action*/, 0/*upgrade_behavior*/,
			'', '0', 0/*download_for_uninstall*/, 0/*uninstall_procedure_post_action*/,
			null/*compatible_os*/, null/*compatible_os_version*/, $files
		);
		return $insertId;
	}

}
