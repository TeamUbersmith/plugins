<?php
// cURL Client
namespace DemoPlugin\Ifttt; // defined here for safety, so we can use generic class names like "Client" below

class Client
{
	private static $timeout = 30;
	private static $useragent = 'Ubersmith IFTTT Client 1.0';

	private $curl;

	public function __construct()
	{
	}

	public function send($url, $payload = null)
	{
		if (is_array($payload) ) {
			$payload = json_encode($payload);
		}

		// Execute request
		return $this->_call($url, $payload);
	}

	private function _call($url, $payload)
	{
		// set headers for JSON request
		$headers = [
			'Content-Type: application/json',
		];

		if (!is_resource($this->curl)) {
			$this->curl = curl_init();
			curl_setopt($this->curl, CURLOPT_HEADERFUNCTION, array($this,'_readHeader'));
		}

		curl_setopt($this->curl, CURLOPT_POST, 1);
		curl_setopt($this->curl, CURLOPT_URL, $url);
		curl_setopt($this->curl, CURLOPT_POSTFIELDS, $payload);

		// results into variable
		curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, 1);

		// user-agent & request headers
		curl_setopt($this->curl, CURLOPT_USERAGENT, static::$useragent);
		curl_setopt($this->curl, CURLOPT_HTTPHEADER, $headers);

		// timeout
		curl_setopt($this->curl, CURLOPT_TIMEOUT, static::$timeout);

		// follow up to 2 redirects
		curl_setopt($this->curl, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($this->curl, CURLOPT_MAXREDIRS, 2);

		$this->http_response_header = array();

		$response = curl_exec($this->curl);

		$this->http_response_code = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);

		if ($response === false) {
			$errnum = curl_errno($this->curl);
			$errstr = curl_error($this->curl);
			curl_close($this->curl);

			throw new \Exception('cURL Error: '. $errstr);
		}

		$this->content_type = curl_getinfo($this->curl, CURLINFO_CONTENT_TYPE);
		$this->content_size = curl_getinfo($this->curl, CURLINFO_CONTENT_LENGTH_DOWNLOAD);

		curl_close($this->curl);
		$this->curl = null;

		// Decompress if response is gzip encoded unless the content type is application/x-gzip.
		if (($this->content_type != 'application/x-gzip') && (strcmp(substr($response,0,2),"\x1f\x8b")) == 0) {
			$len1 = strlen($response);
			$response = gzdecode($response);
			$len2 = strlen($response);
			$this->content_size = $len2;
		}

		return $response;
	}

	// Reads all response headers one by one from cURL
	private function _readHeader($ch, $header)
	{
		$len = strlen($header);

		$header = trim($header);
		if ($header == '') {
			return $len;
		}

		$this->http_response_header[] = $header;

		$pos = stripos($header,'filename=');
		if ($pos !== FALSE) {
			$this->content_filename = substr($header, $pos + 9);
		}

		return $len;
	}

}


// end of script
