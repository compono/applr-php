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

	const API_ENDPOINT = 'https://www.applr.io/api/v1/';

    const API_ENDPOINT_BETA = 'https://beta.applr.io/api/v1/';

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

    private $environment = 'production';

	public $write_log = false;

	protected $reporting_defaults = array(
		'limit' => 100
	);

	private $additionalHeaders = array();

	function __construct($apiKey, $environment = 'production') {
		if (!$apiKey) {
			throw new Exception\EmptyApiKeyException('Please provide API key');
		}

		$this->_apiKey = $apiKey;

		if ($environment == 'beta') {
			$this->environment = 'beta';
		}
	}

	public function createJob($job = array()) {
		$this->job = new Tags\Job($job);
	}

	public function postJob() {
		$this->additionalHeaders[] = 'Content-Type: text/xml';
		$this->write_log = true;
		return $this->postXML($this->job->toXML());
	}

	public function postXML($xml) {
		return $this->_makeCall('jobs', null, $xml);
	}

	protected function _getApiKey() {
		return $this->_apiKey;
	}

	public function getReporting($options = array()) {
		$options['token'] = $this->_getApiKey();
		$options = array_merge($options, $this->reporting_defaults);

		return $this->_makeCall('api_keys/reports.json', $options, null);
	}

	protected function _makeCall($method, $params, $data) {
		$apiCall = $this->getAPIEndpoint() . $method;

		if (!$this->_ch) {
			$this->_ch = curl_init();
		}

		$headerData = array(
			'Accept: application',
			'Authorization: Token token=' . $this->_getApiKey()

		);

		$headers = array_merge($headerData, $this->additionalHeaders);

		if (is_array($params) and $params) {
			$apiCall .= '?' . http_build_query($params);
		}

		$log[] = $apiCall . ' api_key: ' . $this->_getApiKey();
		$log[] = $data;


		curl_setopt($this->_ch, CURLOPT_URL, $apiCall);
		curl_setopt($this->_ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($this->_ch, CURLOPT_CONNECTTIMEOUT, 20);
		curl_setopt($this->_ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($this->_ch, CURLOPT_USERAGENT, 'applr-php');

		curl_setopt($this->ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, 0);

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
		$log[] = $info['http_code'];
		//not good response code

		if (!($info['http_code'] >= 200 && $info['http_code'] < 300)) {
			if ($info['http_code'] == 401) {
				throw new Exception\InvalidApiKeyException($info['http_code'] . ': ' . $response);
			} elseif ($info['http_code'] == 400) {
				throw new Exception\BadRequest($info['http_code'] . ': ' . $response);
			} else {
				throw new Exception\ApiCallException($info['http_code'] . ': ' . $response);
			}
		}

		if ($this->_debug) {
			rewind($verbose);
			$verboseLog = stream_get_contents($verbose);

			echo "<pre>\nVerbose information:\n", htmlspecialchars($verboseLog), "</pre>\n";
		}
		$log[] = 'response: ' . $response;
		if($this->write_log)
			$this->writeLog($log);
		
		if ($response) {
			$json_decoded = json_decode($response, true);
			if ($json_decoded) {
				return $json_decoded;
			}

			if (strpos($response, 'applr.io/l/') !== false) {
				$response = array('job_path' => $response);
			}
		}

		return $response;
	}

	public function isApiKeyValid() {
		$result = false;

		$response = file_get_contents($this->getAPIEndpoint() . '/api_keys/status?token=' . $this->_apiKey);

		if ($response == 'Key is Valid') {
			$result = true;
		}

		return $result;
	}

    protected function getAPIEndpoint() {
        if ($this->environment == 'production') {
            return self::API_ENDPOINT;
        } else {
            return self::API_ENDPOINT_BETA;
        }
    }

    public function setEnvimonmentBeta() {
        $this->environment = 'beta';
    }

    public function setEnvironmentProduction() {
        $this->environment = 'production';
    }

	public function getServicesList() {
		return $this->_makeCall('services/avalible', false, false);
	}

	public function getEnabledServicesList() {
		return $this->_makeCall('services/enabled', array('api_key' => $this->_apiKey), false);
	}

	public function isServiceEnabled($service) {
		$response = $this->_makeCall('services/is_enable', array(
			'api_key' => $this->_apiKey,
			'name' => $service
		), false);

		$result = false;

		if ($response['service_enabled'] == 'Yes') {
			$result = true;
		}

		return $result;
	}

	public function isVideoServiceEnabled() {
		return $this->isServiceEnabled('Video Recording');
	}

	public function isGeoLocationEnabled() {
		return $this->isServiceEnabled('Geo Location');
	}

	public function createTheme($name) {
		$data = array('theme_name' => $name);

		return $this->_makeCall('themes', false,$data);
	}

	public function getThemesList() {
		$themes = $this->_makeCall('themes', array(
			'api_key' => $this->_apiKey,
		), false);

		sort($themes);
		return $themes;
	}

//	public function getPreview( $page_data ) {
//		$page_obj = new Tags\Job($page_data);
//		$xml = $page_obj->toXML();
//		echo $xml;
//		die;
//		return $this->_makeCall('jobs/preview', false, $xml);
//	}

	protected function writeLog($log = array()) {
		if(!is_array($log) || !count($log))
			return false;

		$date = date('Y-m-d');

		$fPath = SNAP_APPLICATION_PATH . '/tmp/applr';
		$fName = sprintf('applr_xml_%s.txt', $date);

		if(!is_dir($fPath))
			mkdir($fPath, 0777, true);

		$writer = new \Zend_Log_Writer_Stream($fPath . '/' . $fName);
		$fileLog = new \Zend_Log($writer);
		$fileLog->info('--- start ---');
		foreach($log as $l)
			$fileLog->info($l);

		$fileLog->info('--- end ---');
	}

}