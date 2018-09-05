<?php

namespace Applr\Tags;

class Question extends BasicTag
{
	private $_name = '';

	private $_ask;

	private $_style;

	private $_answers = array();

	protected $_xml = array(
		'tag' => 'question',
		'attributes' => array('style', 'name'),
		'elements' => array(
			'ask',
			'answer' => array(
				'elements' => 'answers',
				'type' => 'foreach'
			)
		)
	);

	function __construct($question = array()) {
		if (is_array($question) and $question) {
			foreach ($question as $property => $prop_value) {
				switch ($property) {
					case 'name':
					case 'ask':
					case 'style':
					case 'limit':
					case 'required':
						$setter = $this->getSetterName($property);
						if (method_exists($this, $setter)) {
							call_user_func(array(&$this, $setter), $prop_value);
						}
						break;
					case 'answers':
						$this->addAnswers($prop_value);
						break;
					default:
						break;
				}
			}
		}
	}

	public function setName($name) {
		$this->_name = $name;
	}

	public function getName() {
		return $this->_name;
	}

	public function setAsk($ask) {
		if (!$this->_ask) {
			$this->_ask = new Ask();
		}
		$this->_ask->setAsk($ask);
	}

	public function getAsk() {
		return $this->_ask;
	}

	public function setStyle($style) {
		$this->_style = $style;
	}

	public function getStyle() {
		return $this->_style;
	}

	public function setLimit($limit) {
		if (!$this->_ask) {
			$this->_ask = new Ask();
		}
		$this->_ask->setLimit($limit);
	}

	public function getLimit() {
		if ($this->_ask) {
			return $this->_ask->getLimit();
		}

		return false;
	}

	public function setRequired($required) {
		if (!$this->_ask) {
			$this->_ask = new Ask();
		}
		$this->_ask->setRequired($required);
	}

	public function getRequired() {
		if ($this->_ask) {
			return $this->_ask->getRequired();
		}

		return false;
	}

	public function addAnswers($answers) {
		foreach ($answers as $answer) {
			$this->addAnswer($answer);
		}
	}

	public function addAnswer($answer) {
		$this->_answers[] = new Answer($answer);
	}

	public function getAnswers() {
		return $this->_answers;
	}
}
