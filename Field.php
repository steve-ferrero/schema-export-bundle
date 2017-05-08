<?php

namespace WebAtrio\Bundle\SchemaExportBundle;

class Field {

    private $name;
    private $type;
    private $nullable;

    public function __construct($name, $type, $nullable = false) {
        $this->name = $name;
        $this->type = $type;
        $this->nullable = $nullable;
    }

    function getName() {
        return $this->name;
    }

    function getType() {
        return $this->type;
    }

    function getNullable() {
        return $this->nullable;
    }

    function setName($name) {
        $this->name = $name;
    }

    function setType($type) {
        $this->type = $type;
    }

    function setNullable($nullable) {
        $this->nullable = $nullable;
    }

}
