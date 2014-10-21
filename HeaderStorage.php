<?php
/*
 * This file is a part of Solve framework.
 *
 * @author Alexandr Viniychuk <alexandr.viniychuk@icloud.com>
 * @copyright 2009-2014, Alexandr Viniychuk
 * created: 10/21/14 3:08 PM
 */

namespace Solve\Http;


use Solve\Storage\ArrayStorage;

class HeaderStorage {

    /**
     * @var ArrayStorage
     */
    private $_storage;

    public function __construct() {
        $this->_storage = new ArrayStorage();
    }

    public function add($name, $value) {
        if ($this->_storage->has($name)) {
            if (!is_array($this->_storage->$name)) {
                $this->_storage->$name = array($this->_storage->$name);
            }
            $this->_storage[$name][] = $value;
        } else {
            $this->_storage->$name = $value;
        }
        return $this;
    }

    public function addFromString($header) {
        $header = explode(':', $header);
        $this->add(trim($header[0]), trim($header[1]));
        return $this;
    }

    public function setFromStringsArray($array) {
        foreach($array as $header) {
            $this->addFromString($header);
        }
        return $this;
    }

    public function set($name, $value) {
        $this->_storage->$name = $value;
        return $this;
    }

    public function remove($name) {
        $this->_storage->offsetUnset($name);
        return $this;
    }

    public function get($name) {
        return $this->_storage->get($name);
    }

    public function getAll() {
        return $this->_storage->getArray();
    }

}