<?php

if (! function_exists('app')) {
    /**
     * Get the available Application instance.
     *
     * @return \App\Application
     */
    function app()
    {
        return \App\Application::getInstance();
    }
}

if (! function_exists('root_path')) {
    /**
     * Get the path to the root of the project.
     *
     * @param string $path
     *
     * @return string
     */
    function root_path($path = '')
    {
        $rootPath = app()->getRootPath();

        if ($path !== '') {
            // we want to strip directory separator from the start of the additional path string
            $path = \App\Support\Str::stripSlash($path, \App\Support\Str::LEAD_SLASH);
            // so we can concatenate those values with a single directory separator
            $rootPath .= '/'.$path;
        }

        return $rootPath;
    }
}

if (! function_exists('relative_path')) {
    /**
     * Get the path relative to the currently executing script.
     *
     * @param string $path
     *
     * @return string
     */
    function relative_path($path = '')
    {
        $relativePath = app()->getRelativePath();

        if ($path !== '') {
            // we want to strip forward slash from the start of the additional path string
            $path = \App\Support\Str::stripSlash($path, \App\Support\Str::LEAD_SLASH);
            // so we can concatenate those values with a single forward slash
            $relativePath .= $path;
        }

        return $relativePath;
    }
}

if (! function_exists('domain_path')) {
    /**
     * Get the domain "path" (wether forced or actual).
     *
     * @param string $path
     *
     * @return string
     */
    function domain_path($path = '')
    {
        $domainPath = app()->getDomainPath();

        // we want to strip forward slash from the end of the domain path
        $domainPath = \App\Support\Str::stripSlash($domainPath, \App\Support\Str::TAIL_SLASH);

        if ($path !== '') {
            // and we want to strip forward slash from the start of the additional path string
            $path = \App\Support\Str::stripSlash($path, \App\Support\Str::LEAD_SLASH);
            // so we can concatenate those values with a single forward slash
            $domainPath .= '/'.$path;
        }

        return $domainPath;
    }
}

if (! function_exists('config')) {
    /**
     * Get / set the specified configuration value.
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    function config($key = null, $default = null)
    {
        if (is_null($key)) {
            return app()->getConfig();
        }

        return app()->getConfig()->get($key, $default);
    }
}

if (! function_exists('env')) {
    /**
     * Gets the value of an environment variable. Supports boolean, empty and null.
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    function env($key, $default = null)
    {
        $value = getenv($key);

        if ($value === false) {
            return value($default);
        }

        switch (strtolower($value)) {
            case 'true':
            case '(true)':
                return true;
            case 'false':
            case '(false)':
                return false;
            case 'empty':
            case '(empty)':
                return '';
            case 'null':
            case '(null)':
                return null;
        }


        if (strlen($value) > 1 &&
            mb_strpos($value, '"') === 0 &&
            mb_substr($value, -mb_strlen($value), null, 'UTF-8')
        ) {
            return substr($value, 1, -1);
        }

        return $value;
    }
}

if (! function_exists('value')) {
    /**
     * Return the default value of the given value.
     *
     * @param mixed $value
     *
     * @return mixed
     */
    function value($value)
    {
        return $value instanceof Closure ? $value() : $value;
    }
}

if (! function_exists('is_cli')) {
    /**
     * Determine whether script is running in CLI.
     *
     * @return bool
     */
    function is_cli()
    {
        return php_sapi_name() == 'cli';
    }
}
