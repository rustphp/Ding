<?php
/**
 * Ding autoloader, you will surely need this.
 *
 * @category Ding
 * @package  Autoloader
 * @author   Marcelo Gornstein <marcelog@gmail.com>
 * @license  http://marcelog.github.com/ Apache License 2.0
 * @link     http://marcelog.github.com/
 *
 * Copyright 2011 Marcelo Gornstein <marcelog@gmail.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 */
namespace Ding\Autoloader;
/**
 * Ding autoloader, you will surely need this.
 *
 * @package Ding\Autoloader
 */
class Autoloader {
    /**
     * Called by php to load a given class. Returns true if the class was
     * successfully loaded.
     *
     * @param string $class
     *
     * @return bool
     */
    public static function load(string $class) : bool {
        if (class_exists($class, false)) {
            return true;
        }
        $classFile=str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';
        $file=stream_resolve_include_path($classFile);
        if ($file && file_exists($file)) {
            include $file;
            return true;
        }
        return false;
    }

    /**
     * You need to use this function to autoregister this loader.
     *
     * @see spl_autoload_register()
     *
     * @return boolean
     */
    public static function register() : bool {
        return spl_autoload_register('\Ding\Autoloader\Autoloader::load');
    }
}
