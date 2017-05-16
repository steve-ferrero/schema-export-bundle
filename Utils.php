<?php

namespace WebAtrio\Bundle\SchemaExportBundle;

class Utils {

    static function getClassName($namespace) {
        $path = explode('\\', $namespace);
        return array_pop($path);
    }

}
