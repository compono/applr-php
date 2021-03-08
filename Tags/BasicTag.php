<?php

namespace Applr\Tags;

class BasicTag
{
	private function _toCamelCase($string) {
		$str = str_replace(' ', '', ucwords(str_replace('_', ' ', $string)));
		return $str;
	}

	protected function getSetterName($property) {
		return 'set' . $this->_toCamelCase($property);
	}

	protected function getAdderName($property) {
		return 'add' . $this->_toCamelCase(substr($property, 0, -1));
	}

	protected function getGetterName($property) {
		return 'get' . $this->_toCamelCase($property);
	}

	protected function _processCurrentTag($tag) {
		$xml = '';

		if (isset($tag->_xml['tag']) && $tag->_xml['tag']) {
			$xml .= '<' . $tag->_xml['tag'];

			/*
			 * processing attributes
			 * e.g. <tag attribute="value">
			 */
			foreach ($tag->_xml['attributes'] as $attribute) {
				$getter = $this->getGetterName($attribute);
				if (method_exists($tag, $getter)) {
					$value = $tag->$getter();
					if ($value) {
						$xml .= ' ' . $attribute . '="' . $value . '"';
					}
				}
			}

			/*
			 * processing elements inside tag
			 */

			if ((isset($tag->_xml['elements']) && $tag->_xml['elements']) || (isset($tag->_xml['element']) && $tag->_xml['element'])) {
				$xml .= '>';
				if (isset($tag->_xml['elements']) && $tag->_xml['elements']) {

					/*
					 * complex elements (array of elements)
					 * e.g. <tag><element1>value1</element1><element2>value2</element2></tag>
					 */

					foreach ($tag->_xml['elements'] as $element_key => $element) {
						//complex elements (array) with foreach processing
						if (is_array($element)) {
							if ($element['type'] == 'in_parent') {
								$xml .= '<' . $element_key . '>';
							}
							$getter = $this->getGetterName($element['elements']);
							if (method_exists($tag, $getter)) {
								$arr_objects = $tag->$getter();
								foreach ($arr_objects as $object) {
									$xml .= $this->_processCurrentTag($object);
								}
							}
							if ($element['type'] == 'in_parent') {
								$xml .= '</' . $element_key . '>';
							}
							//simple element
						} elseif (is_string($element)) {
							$getter = $this->getGetterName($element);
                            $value = false;
							if (method_exists($tag, $getter)) {
								$value = $tag->$getter();
							}
							if ($value) {
								//in case element inside tag is tag we need to process it
								if (is_object($value)) {
									$xml .= $this->_processCurrentTag($value);
									//if it's just a value then insert it in tag
								} else {
									$xml .= '<'.$element.'>';
										$xml .= '<![CDATA[' . $value . ']]>';
									$xml .= '</'.$element.'>';
								}
							}
						}
					}

					/*
					 * simple element
					 * e.g. <tag>element</tag>
					 */

				} elseif (isset($tag->_xml['element']) && $tag->_xml['element']) {
					$getter = $this->getGetterName($tag->_xml['element']);
                    $value = false;
					if (method_exists($tag, $getter)) {
						$value = $tag->$getter();
					}
					if ($value) {
						$xml .= '<![CDATA[' . $value . ']]>';
					}
				}

				//closing tag
				$xml .= '</' . $tag->_xml['tag'] . '>';
			} else {
				//closing tag
				$xml .= '/>';
			}
		}

		return $xml;
	}

	public function toXML() {
		return $this->_processCurrentTag($this);
	}

	protected function _setPropertes($properties) {
		if (is_array($properties) and $properties) {
			foreach ($properties as $property => $prop_value) {
				$setter = $this->getSetterName($property);
				if (method_exists($this, $setter)) {
					call_user_func(array(&$this, $setter), $prop_value);
				}
			}
		}
	}
}
