<?php

namespace App\Support;

/**
 * Class Database
 *
 * @package App\Support
 */
class Database
{
    /**
     * Contains all created contains
     * @var array
     */
    protected $storedConnections = [];

    /**
     * Last used connection
     * @var \PDO
     */
    protected $lastUsedConnection;

    /**
     * Database configuration
     * @see __construct
     * @var array
     */
    protected $dbConfig;

    /**
     * Default query options
     * @var array
     */
    protected $defaultQueryOptions = [
        'connection' => 'mysql',
    ];

    /**
     * Database constructor
     *
     * @param array $dbConfig database configurations with keys:
     *                        ['configuration_id'] (array)
     *                        ['host']     (string) host name
     *                        ['username'] (string) username
     *                        ['pass']     (string) password
     *                        ['dbname']   (string) database name
     */
    public function __construct($dbConfig)
    {
        $this->dbConfig = $dbConfig;

        if ($dbConfig['default']) {
            $this->defaultQueryOptions['connection'] = $dbConfig['default'];
        }
    }

    /**
     * Execute query on selected database connection
     *
     * @param string $sql     SQL statatement with named parameters
     * @param array  $data    array of values (bound parameters)
     * @param array  $options Options
     *
     * @return \PDOStatement|false
     */
    public function execute($sql, $data = [], $options = [])
    {
        $options = array_merge($this->defaultQueryOptions, $options);
        try {
            $db = $this->getConnection($options['connection']);
        } catch (\Exception $e) {
            if (config('app.show_errors')) {
                print_exception($e);
            }

            return false;
        }

        $stmt = $db->prepare($sql);

        if (!empty($data)) {
            foreach ($data as $key => $value) {
                if (is_int($value)) {
                    $stmt->bindValue("$key", $value, \PDO::PARAM_INT);
                } else {
                    $stmt->bindValue("$key", $value);
                }
            }
        }

        $stmt->PDO = $db;
        try {
            $stmt->execute();
        } catch (\Exception $e) {
            if (config('app.show_errors')) {
                print_exception($e);
            }

            return false;
        }

        return $stmt;
    }

    /**
     * Create and return connection by id
     *
     * @param string $con connection id
     *
     * @throws \Exception
     * @return \PDO
     */
    protected function getConnection($con)
    {
        // Check if connection already exists
        if (empty($this->storedConnections[$con])) {
            // if connection if exists in configuration, create new connection
            if (array_key_exists($con, $this->dbConfig)) {
                $conf = $this->dbConfig[$con];
                try {
                    $dbOptions                     = isset($conf['options']) ? $conf['options'] : [];
                    $this->storedConnections[$con] = new \PDO(
                        'mysql:host=' . $conf['host'] . ';dbname=' . $conf['database'] . ';charset=' . $conf['charset'],
                        $conf['username'],
                        $conf['password'],
                        $dbOptions
                    );

                    $this->storedConnections[$con]->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
                } catch (\PDOException $e) {
                    throw new \Exception("Could not create db connection for db: " . $con, 0, $e);
                }
            } else {
                throw new \Exception("No configuration for selected db");
            }
        }

        $this->lastUsedConnection = $con;

        return $this->storedConnections[$con];
    }
}
