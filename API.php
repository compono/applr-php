<?php

namespace Applr;

use Applr\Exception;
use Applr\Tags;

class API {

	/**
	 * Debug flag
	 * @var bool
	 */

	private $_debug = false;

	/**
	 * API endpoint base url
	 */

	const API_ENDPOINT = 'https://applr.io/api/v1/';

	/**
	 * API key
	 */

	private $_apiKey;

	/**
	 * cURL handler object
	 */

	private $_ch;

	/**
	 * Job tag
	 */

	public $job;

	/**
	 * Default reporting options
	 * @var array
	 */

	protected $reporting_defaults = array(
		'limit' => 100
	);

	function __construct($apiKey) {
		if (!$apiKey) {
			throw new Exception\EmptyApiKeyException('Please provide API key');
		}

		$this->_apiKey = $apiKey;
	}

	public function createJob($job = array()) {
		$this->job = new Tags\Job($job);
	}

	public function postJob() {
		return $this->postXML($this->job->toXML());
	}

	public function postXML($xml) {
		return $this->_makeCall('jobs', null, $xml);
	}

	protected function _getApiKey() {
		return $this->_apiKey;
	}

	protected function _makeCall($method, $params, $data) {
		$apiCall = self::API_ENDPOINT . $method;

		if (!$this->_ch) {
			$this->_ch = curl_init();
		}

		$headerData = array(
			'Accept: application',
			'Authorization: Token token=' . $this->_getApiKey()
		);

		if (is_array($params) and $params) {
			$apiCall .= '?' . http_build_query($params);
		}

		curl_setopt($this->_ch, CURLOPT_URL, $apiCall);
		curl_setopt($this->_ch, CURLOPT_HTTPHEADER, $headerData);
		curl_setopt($this->_ch, CURLOPT_CONNECTTIMEOUT, 20);
		curl_setopt($this->_ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($this->_ch, CURLOPT_USERAGENT, 'applr-php');
		curl_setopt($this->_ch, CURLOPT_SSL_VERIFYPEER, false);

		if ($data) {
			curl_setopt($this->_ch, CURLOPT_POST, true);
			curl_setopt($this->_ch, CURLOPT_POSTFIELDS, $data);
		}

		if ($this->_debug) {
			$verbose = fopen('php://temp', 'rw+');
			curl_setopt($this->_ch, CURLOPT_STDERR, $verbose);
			curl_setopt($this->_ch, CURLOPT_VERBOSE, true);
		}

		$response = curl_exec($this->_ch);

		$info = curl_getinfo($this->_ch);

		//not good response code
		if (!($info['http_code'] >= 200 && $info['http_code'] < 300)) {
			if ($info['http_code'] == 401) {
				throw new Exception\InvalidApiKeyException($info['http_code'] . ': ' . $response);
			} elseif ($info['http_code'] == 400) {
				throw new Exception\BadRequest($info['http_code'] . ': ' . $response);
			} else {
				throw new Exception\ApiCallException($info['http_code'] . '('.$apiCall.'): ' . $response);
			}
		}

		if ($this->_debug) {
			rewind($verbose);
			$verboseLog = stream_get_contents($verbose);

			echo "<pre>\nVerbose information:\n", htmlspecialchars($verboseLog), "</pre>\n";
		}

		if ($response) {
			$json_decoded = json_decode($response, true);
			if ($json_decoded) {
				return $json_decoded;
			}

			if (strpos($response, 'applr.io/l/') === 0) {
				$response = array('job_path' => $response);
			}
		}

		return $response;
	}

	public static function isApiKeyValid($api_key) {
		$result = false;

		$response = file_get_contents(self::API_ENDPOINT . '/api_keys/status?token=' . $api_key);

		if ($response == 'Key is Valid') {
			$result = true;
		}

		return $result;
	}

	public function getReporting($options = array()) {
		$options['token'] = $this->_getApiKey();
		$options = array_merge($options, $this->reporting_defaults);

		return $this->_makeCall('api_keys/reports.json', $options, null);
	}
}