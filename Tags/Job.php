<?php

namespace Applr\Tags;

class Job extends BasicTag
{
	private $_reference = '';

	private $_logo = '';

	private $_theme_id = '';

	private $_redirect_url = '';

	private $_title = '';

	private $_description = '';

	private $_labels = array();

	private $_sinks = array();

	private $_questions = array();

	private $_geo_location;

	protected $_xml = array(
		'tag' => 'job',
		'attributes' => array(),
		'elements' => array(
			'reference',
			'logo',
			'redirect_url',
			'title',
			'description',
			'theme_id',
			'label' => array(
				'elements' => 'labels',
				'type' => 'foreach'
			),
			'sinks' => array(
				'elements' => 'sinks',
				'type' => 'in_parent'
			),
			'questions' => array(
				'elements' => 'questions',
				'type' => 'in_parent'
			),
			'geo_location'
		)
	);

	protected $_root_tag = 'job';

	function __construct($job = array()) {
		if (is_array($job) and $job) {
			foreach ($job as $property => $prop_value) {
				switch ($property) {
					case 'reference':
					case 'logo':
					case 'redirect_url':
					case 'title':
					case 'description':
					case 'geo_location':
					case 'theme_id':
						$setter = $this->getSetterName($property);
						if (method_exists($this, $setter)) {
							call_user_func(array(&$this, $setter), $prop_value);
						}
						break;

					case 'labels':
					case 'sinks':
					case 'questions':
						$adder = $this->getAdderName($property);
						foreach ($prop_value as $prop_value_piece) {
							if (method_exists($this, $adder)) {
								call_user_func(array(&$this, $adder), $prop_value_piece);
							}
						}
					default:
						break;
				}
			}
		}
	}

	public function setReference($reference) {
		$this->_reference = $reference;
	}

	public function getReference() {
		return $this->_reference;
	}

	public function setLogo($logo) {
		$this->_logo = $logo;
	}

	public function getLogo() {
		return $this->_logo;
	}

	public function setThemeId($theme) {
		$this->_theme_id = $theme;
	}

	public function getThemeId() {
		return $this->_theme_id;
	}

	public function setRedirectURL($redirect_url) {
		$this->_redirect_url = $redirect_url;
	}

	public function getRedirectURL() {
		return $this->_redirect_url;
	}

	public function setTitle($title) {
		$this->_title = $title;
	}

	public function getTitle() {
		return $this->_title;
	}

	public function setDescription($description) {
		$this->_description = $description;
	}

	public function getDescription() {
		return $this->_description;
	}

	public function addLabel(array $label) {
		$this->_labels[] = new Label($label);
	}

	public function getLabels() {
		return $this->_labels;
	}

	public function addSink(array $sink) {
		$this->_sinks[] = new Sink($sink);
	}

	public function getSinks() {
		return $this->_sinks;
	}

	public function addQuestion(array $question) {
		$this->_questions[] = new Question($question);
	}

	public function getQuestions() {
		return $this->_questions;
	}

	/**
	 * @param mixed $geo_location
	 */
	public function setGeoLocation($geo_location) {
		$this->_geo_location = new GeoLocation($geo_location);
	}

	/**
	 * @return mixed
	 */
	public function getGeoLocation() {
		return $this->_geo_location;
	}
}
