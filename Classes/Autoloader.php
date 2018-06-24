<?php

declare(strict_types=1);

namespace PictShare\Classes;

use PictShare\Classes\Exceptions\ClassNotFoundException;

class Autoloader
{
    /**
     * Old autoloader, for backwards compatibility.
     *
     * @TODO: When everything is converted over to the namespace autoloader, remove this.
     *
     * @deprecated
     *
     * @param $className
     *
     * @return bool
     */
    public static function deprecatedAutoload(string $className): bool
    {
        if (file_exists(BASE_DIR . 'models/' . strtolower($className) . '.php')) {
            include_once BASE_DIR . 'models/' . strtolower($className) . '.php';

            return true;
        }

        return false;
    }

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
     *
     * @TODO Drop the old loader completely.
     */
    public static function init()
    {
        spl_autoload_register(
            function ($className) {
                // New style first.
                $loaded = static::namespaceAutoloader($className);

                if (!$loaded) {
                    static::deprecatedAutoload($className);
                }
            }
        );
    }
}
