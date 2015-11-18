<?php

namespace Applr\Tags;

class Video extends BasicTag
{
    private $_ask = '';
    private $_name = '';

    private $_style;
    private $_maxtime = 0;

    protected $_xml = array(
        'tag' => 'question',
        'attributes' => array('limit', 'style'),
        'element' => 'ask'
    );

    function __construct($video) {
        if (isset($video['ask'])) {
            $this->setAsk($video['ask']);
        }
        if (isset($video['limit'])) {
            $this->setMaxtime($video['limit']);
        }
    }

    public function setAsk($ask) {
        $this->_ask = $ask;
    }

    public function getAsk() {
        return $this->_ask;
    }

    public function setMaxtime($maxtime) {
        $this->_maxtime = intval($maxtime);
    }

    public function getMaxtime() {
        return $this->_maxtime;
    }

    public function setStyle($style) {
        $this->_style = $style;
    }

    public function getStyle() {
        return $this->_style;
    }

    public function setName($name) {
        $this->_name = $name;
    }

    public function getName() {
        return $this->_name;
    }
}
