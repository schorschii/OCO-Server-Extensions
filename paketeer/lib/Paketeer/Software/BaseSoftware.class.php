<?php

namespace Paketeer\Software;

abstract class BaseSoftware {

	const CLASSES = [
		'Windows11_24H2',
		'Windows10_22H2',
	];

	function __construct() {
	}

	protected function apiCall(string $method, string $url, string|null $body=null, int $expectedStatusCode=200, array|null $header=null) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		#curl_setopt($ch, CURLOPT_VERBOSE, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		$response = curl_exec($ch);
		curl_close($ch);

		$statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		if($expectedStatusCode && $statusCode !== $expectedStatusCode)
			throw new \Exception('Unexpected status code '.$statusCode.' '.$response);

		return $response;
	}

	private int $downloadBytes = -1;
	private int $downloadCurrent = 0;
	private int $downloadCount = 1;
	protected function downloadFiles(array $downloads) {
		if(count($downloads) == 0) return;
		$this->downloadCount = count($downloads);
		$this->downloadCurrent = 0;
		foreach($downloads as $url => $destPath) {
			$this->downloadFile($url, $destPath);
			$this->downloadCurrent += 1;
		}
	}
	protected function downloadFile(string $url, string $destPath) {
		$fh = fopen($destPath, 'w');
		register_shutdown_function('unlink', $destPath);

		if($this->downloadBytes === -1) ob_start();
		$this->downloadBytes = 0;

		$ch = curl_init();
		curl_setopt_array($ch, [
			CURLOPT_FILE    => $fh,
			CURLOPT_TIMEOUT => 28800,
			CURLOPT_URL     => $url,
			CURLOPT_PROGRESSFUNCTION => [$this, 'downloadProgress'],
			CURLOPT_NOPROGRESS => false,
		]);
		curl_exec($ch);
		curl_close($ch);
		fclose($fh);

		// send 101% as indicator for animated progress bar
		echo str_pad('101', 4096)."\n";
		ob_flush(); flush();
	}
	private function downloadProgress($resource, $download_size, $downloaded, $upload_size, $uploaded) {
		if($download_size > 0 && $downloaded-$this->downloadBytes > 10*1024*1024) {
			echo str_pad(($downloaded / $download_size * 100) / $this->downloadCount + (100 / $this->downloadCount * $this->downloadCurrent), 4096)."\n";
			ob_flush(); flush();
			#error_log($downloaded / $download_size * 100);
			$this->downloadBytes = $downloaded;
		}
	}

}
