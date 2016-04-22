<?php

namespace Applr\Tags;

class Ask extends BasicTag
{
	private $_ask;

	private $_limit;

	private $_required;

	protected $_xml = array(
		'tag' => 'ask',
		'element' => 'ask',
		'attributes' => array('limit', 'required')
	);

	function __construct($ask = array()) {
		if (isset($ask['ask'])){
			$this->setAsk($ask['ask']);
		}
		if (isset($ask['limit'])){
			$this->setLimit($ask['limit']);
		}
		if (isset($ask['required'])){
			$this->setRequired($ask['required']);
		}
	}

	public function setAsk($ask) {
		$this->_ask = $ask;
	}

	public function getAsk() {
		return $this->_ask;
	}

	public function setLimit($limit) {
		$this->_limit = $limit;
	}

	public function getLimit() {
		return $this->_limit;
	}

	public function setRequired($required) {
		$this->_required = $required;
	}

	public function getRequired() {
		return $this->_required;
	}
}
