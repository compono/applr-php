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

	public function updateJob($jobID){
		$this->additionalHeaders[] = 'Content-Type: text/xml';
		$this->write_log = true;

		return $this->_makeCall('jobs', array('job_id' => $jobID), $this->job->toXML(), 'PUT');
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

	protected function _makeCall($method, $params, $data, $request_method = '') {
		$apiCall = $this->getAPIEndpoint() . $method;

		$http_client = new \Zend_Http_Client();
		$config = \Zend_Registry::get( 'config' );

		$headerData = array(
			'Accept: application',
			'Authorization: Token token=' . $this->_getApiKey()

		);

		$headers = array_merge($headerData, $this->additionalHeaders);

		if (is_array($params) && $request_method != 'PUT')
			$apiCall .= '?' . http_build_query($params);

		elseif($request_method == 'PUT')
			$apiCall .= '/'. $params['job_id'];


		$log[] = $apiCall . ' api_key: ' . $this->_getApiKey();
		$log[] = $data;

		$http_client->setConfig($config->zend->http_client->options->toArray());
		$http_client->setUri($apiCall);
		$http_client->setHeaders($headers);
		$http_client->setConfig(array('useragent' => 'applr-php', 'timeout' => 20));

		if ($data) {
//			$http_client->setRawData($data, 'text/xml');
			if($request_method == 'PUT') {
				$http_client->setMethod('PUT');
				$http_client->setRawData($data, 'text/xml');
			}
			else {
				$http_client->setMethod('POST');
				if(is_array($data))
					$http_client->setParameterPost($data);
				else
					$http_client->setRawData($data, 'text/xml');
			}

		}

		$request = $http_client->request();
		$response = $request->getBody();

		$request_status = $request->getStatus();
		$log[] = $request_status;
		//not good response code
		$log[] = 'response: ' . $response;

		if($this->write_log)
			$this->writeLog($log);

		if (!($request_status >= 200 && $request_status < 300)) {
			$exception_string = json_encode(array (
				'http_code' => $request_status,
				'response' => $response
			));

			if ($request_status == 401)
				throw new Exception\InvalidApiKeyException($exception_string);
			elseif ($request_status == 400)
				throw new Exception\BadRequest($exception_string);
			else
				throw new Exception\ApiCallException($exception_string);
		}

		if ($this->_debug) {
			Zend_Registry::get( 'logger' )->captureMessage( "Verbose information: ", null, array(
				array('extra' => array(
					'http_code' => $request_status,
					'response' => $response,
				)),
			));

			$logs[] = "Verbose information: " . $response;
		}

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

		return $this->_makeCall('themes', false, $data, 'POST');
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

		$fPath = \Zend_Registry::get('config')->idibu->paths->logs . '/applr';
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