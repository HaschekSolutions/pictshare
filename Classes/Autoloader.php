<?php

declare(strict_types=1);

namespace PictShare\Classes;

use PictShare\Classes\Exceptions\ClassNotFoundException;

class Autoloader
{
    /**
     * New autoloader, supporting namespaces. PSR-4 style.
     *
     * @param $className
     *
     * @return bool
     *
     * @throws \DomainException
     */
    public static function namespaceAutoloader(string $className): bool
    {
        $prefix = 'PictShare\\';
        // Does the class use this namespace prefix?
        $len = mb_strlen($prefix);

        if (strncmp($prefix, $className, $len) !== 0) {
            return false;
        }

        $relativeClass = mb_substr($className, $len);
        $endPath       = str_replace('\\', '/', $relativeClass) . '.php';
        $file          = BASE_DIR . $endPath;

        if (file_exists($file) === true) {
            include_once $file;

            return true;
        }

        throw new ClassNotFoundException('Class ' . $className . ' not found.');
    }

    /**
     * Temporary shim around the real autoloader.
     */
    public static function init()
    {
        spl_autoload_register('static::namespaceAutoloader');
    }
}
