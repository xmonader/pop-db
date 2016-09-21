<?php
/**
 * Pop PHP Framework (http://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2016 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 */

/**
 * @namespace
 */
namespace Pop\Db;

/**
 * Db class
 *
 * @category   Pop
 * @package    Pop\Db
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2016 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    4.0.0
 */
class Db
{

    /**
     * Method to connect to a database and return the database adapter object
     *
     * @param  string $adapter
     * @param  array  $options
     * @param  string $prefix
     * @throws Exception
     * @return Adapter\AbstractAdapter
     */
    public static function connect($adapter, array $options, $prefix = '\Pop\Db\Adapter\\')
    {
        $class = $prefix . ucfirst(strtolower($adapter));

        if (!class_exists($class)) {
            throw new Exception('Error: The database adapter ' . $class . ' does not exist.');
        }

        return new $class($options);
    }

    /**
     * Check the database connection
     *
     * @param  string $adapter
     * @param  array  $options
     * @param  string $prefix
     * @return mixed
     */
    public static function check($adapter, array $options, $prefix = '\Pop\Db\Adapter\\')
    {
        $result = null;
        $error  = ini_get('error_reporting');

        error_reporting(E_ERROR);

        try {
            $class = $prefix . ucfirst(strtolower($adapter));

            if (!class_exists($class)) {
                $result = 'Error: The database adapter ' . $class . ' does not exist.';
            } else {
                $connection = new $class($options);
            }
        } catch (\Exception $e) {
            $result = $e->getMessage();
        }

        error_reporting($error);
        return $result;
    }

    /**
     * Install a database schema
     *
     * @param  string $sql
     * @param  string $adapter
     * @param  array  $options
     * @param  string $prefix
     * @throws Exception
     * @return void
     */
    public static function install($sql, $adapter, array $options, $prefix = '\Pop\Db\Adapter\\')
    {

    }

    /**
     * Get the available database adapters
     *
     * @return array
     */
    public static function getAvailableAdapters()
    {
        $pdoDrivers = (class_exists('Pdo', false)) ? \PDO::getAvailableDrivers() : [];

        return [
            'mysqli' => (class_exists('mysqli', false)),
            'oracle' => (function_exists('oci_connect')),
            'pdo'    => [
                'mysql'  => (in_array('mysql', $pdoDrivers)),
                'pgsql'  => (in_array('pgsql', $pdoDrivers)),
                'sqlite' => (in_array('sqlite', $pdoDrivers)),
                'sqlsrv' => (in_array('sqlsrv', $pdoDrivers))
            ],
            'pgsql'  => (function_exists('pg_connect')),
            'sqlite' => (class_exists('Sqlite3', false)),
            'sqlsrv' => (function_exists('sqlsrv_connect'))
        ];
    }

    /**
     * Determine if a database adapter is available
     *
     * @param  string $adapter
     * @return boolean
     */
    public static function isAvailable($adapter)
    {
        $adapter = strtolower($adapter);
        $result  = false;
        $type    = null;

        $pdoDrivers = (class_exists('Pdo', false)) ? \PDO::getAvailableDrivers() : [];
        if (strpos($adapter, 'pdo_') !== false) {
            $type    = substr($adapter, 4);
            $adapter = 'pdo';
        }

        switch ($adapter) {
            case 'mysql':
            case 'mysqli':
                $result = (class_exists('mysqli', false));
                break;
            case 'oci':
            case 'oracle':
                $result = (function_exists('oci_connect'));
                break;
            case 'pdo':
                $result = (in_array($type, $pdoDrivers));
                break;
            case 'pgsql':
                $result = (function_exists('pg_connect'));
                break;
            case 'sqlite':
                $result = (class_exists('Sqlite3', false));
                break;
            case 'sqlsrv':
                $result = (function_exists('sqlsrv_connect'));
                break;
        }

        return $result;
    }

}