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

use Pop\Db\Parser;

/**
 * Record class
 *
 * @category   Pop
 * @package    Pop_Db
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2016 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    3.0.0
 */
class Record
{

    /**
     * Data set result constants
     * @var string
     */
    const ROW_AS_ARRAY       = 'ROW_AS_ARRAY';
    const ROW_AS_ARRAYOBJECT = 'ROW_AS_ARRAYOBJECT';
    const ROW_AS_RECORD      = 'ROW_AS_RECORD';

    /**
     * Database connection(s)
     * @var array
     */
    protected static $db = ['default' => null];

    /**
     * Record result object
     * @var Record\Result
     */
    protected $result = null;

    /**
     * Table name
     * @var string
     */
    protected $table = null;

    /**
     * Table prefix
     * @var string
     */
    protected $prefix = null;

    /**
     * Primary keys
     * @var array
     */
    protected $primaryKeys = ['id'];

    /**
     * Constructor
     *
     * Instantiate the database record object.
     *
     * Optional parameters are an array of values, db adapter,
     * or a table name
     *
     * @throws Exception
     * @return Record
     */
    public function __construct()
    {
        $args    = func_get_args();
        $columns = null;
        $table   = null;
        $db      = null;

        foreach ($args as $arg) {
            if (is_array($arg) || ($arg instanceof \ArrayAccess) || ($arg instanceof \ArrayObject)) {
                $columns = $arg;
            } else if ($arg instanceof Adapter\AbstractAdapter) {
                $db = $arg;
            } else if (is_string($arg)) {
                $table = $arg;
            }
        }

        if (null !== $db) {
            $class = get_class($this);
            $class::setDb($db);
        }

        if (!static::hasDb()) {
            throw new Exception('Error: A database connection has not been set.');
        }

        if (null !== $table) {
            $this->setTable($table);
        }

        // Set the table name from the class name
        if (null === $this->table) {
            $this->setTableFromClassName(get_class($this));
        }

        $this->result = new Record\Result(static::db(), $this->getFullTable(), $this->getPrimaryKeys(), $columns);
        if (null !== $columns) {
            $this->result->setColumns($columns);
        }
    }

    /**
     * Set DB connection
     *
     * @param  Adapter\AbstractAdapter $db
     * @param  string                  $prefix
     * @param  boolean                 $isDefault
     * @return void
     */
    public static function setDb(Adapter\AbstractAdapter $db, $prefix = null, $isDefault = false)
    {
        if (null !== $prefix) {
            static::$db[$prefix] = $db;
        }

        $class = get_called_class();
        static::$db[$class] = $db;

        if (($isDefault) || ($class === __CLASS__)) {
            static::$db['default'] = $db;
        }
    }

    /**
     * Check is the class has a DB adapter
     *
     * @return boolean
     */
    public static function hasDb()
    {
        $result = false;
        $class  = get_called_class();

        if (isset(static::$db[$class])) {
            $result = true;
        } else if (isset(static::$db['default'])) {
            $result = true;
        } else {
            foreach (static::$db as $prefix => $adapter) {
                if (substr($class, 0, strlen($prefix)) == $prefix) {
                    $result = true;
                }
            }
        }

        return $result;
    }

    /**
     * Get DB adapter
     *
     * @throws Exception
     * @return Adapter\AbstractAdapter
     */
    public static function db()
    {
        $class = get_called_class();

        if (isset(static::$db[$class])) {
            return static::$db[$class];
        } else if (isset(static::$db['default'])) {
            return static::$db['default'];
        } else {
            $dbAdapter = null;
            foreach (static::$db as $prefix => $adapter) {
                if (substr($class, 0, strlen($prefix)) == $prefix) {
                    $dbAdapter = $adapter;
                }
            }
            if (null !== $dbAdapter) {
                return $dbAdapter;
            } else {
                throw new Exception('No database adapter was found.');
            }
        }
    }

    /**
     * Find by ID static method
     *
     * @param  mixed  $id
     * @param  string $resultsAs
     * @return Record\Result
     */
    public static function findById($id, $resultsAs = Record::ROW_AS_RECORD)
    {
        return (new static())->getResult()->findById($id, $resultsAs);
    }

    /**
     * Find by static method
     *
     * @param  array  $columns
     * @param  array  $options
     * @param  string $resultsAs
     * @return Record\Result
     */
    public static function findBy(array $columns = null, array $options = null, $resultsAs = Record::ROW_AS_RECORD)
    {
        return (new static())->getResult()->findBy($columns, $options, $resultsAs);
    }

    /**
     * Find all static method
     *
     * @param  array  $options
     * @param  string $resultsAs
     * @return Record\Result
     */
    public static function findAll(array $options = null, $resultsAs = Record::ROW_AS_RECORD)
    {
        return (new static())->getResult()->findBy(null, $options, $resultsAs);
    }

    /**
     * Static method to execute a custom prepared SQL statement.
     *
     * @param  mixed  $sql
     * @param  mixed  $params
     * @param  string $resultsAs
     * @return Record\Result
     */
    public static function execute($sql, $params, $resultsAs = Record::ROW_AS_RECORD)
    {
        return (new static())->getResult()->execute($sql, $params, $resultsAs);
    }

    /**
     * Static method to execute a custom SQL query.
     *
     * @param  mixed  $sql
     * @param  string $resultsAs
     * @return Record\Result
     */
    public static function query($sql, $resultsAs = Record::ROW_AS_RECORD)
    {
        return (new static())->getResult()->query($sql, $resultsAs);
    }

    /**
     * Static method to get the total count of a set from the DB table
     *
     * @param  array  $columns
     * @param  string $resultsAs
     * @return int
     */
    public static function getTotal(array $columns = null, $resultsAs = Record::ROW_AS_RECORD)
    {
        return (new static())->getResult()->getTotal($columns, $resultsAs);
    }

    /**
     * Set the table prefix
     *
     * @param  string $prefix
     * @return Record
     */
    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;
        return $this;
    }

    /**
     * Set the table
     *
     * @param  string $table
     * @return Record
     */
    public function setTable($table)
    {
        $this->table = $table;
        return $this;
    }

    /**
     * Set the table from a class name
     *
     * @param  string $class
     * @return Record
     */
    public function setTableFromClassName($class)
    {
        if (strpos($class, '_') !== false) {
            $cls = substr($class, (strrpos($class, '_') + 1));
        } else if (strpos($class, '\\') !== false) {
            $cls = substr($class, (strrpos($class, '\\') + 1));
        } else {
            $cls = $class;
        }
        return $this->setTable(Parser\Table::parse($cls));
    }

    /**
     * Set the primary keys
     *
     * @param  array $keys
     * @return Record
     */
    public function setPrimaryKeys(array $keys)
    {
        $this->primaryKeys = $keys;
        return $this;
    }

    /**
     * Get the record result object
     *
     * @return Record\Result
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * Get the table prefix
     *
     * @return string
     */
    public function getPrefix()
    {
        return $this->prefix;
    }

    /**
     * Get the table
     *
     * @return string
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * Get the full table name (prefix + table)
     *
     * @return string
     */
    public function getFullTable()
    {
        return $this->prefix . $this->table;
    }

    /**
     * Get the primary keys
     *
     * @return array
     */
    public function getPrimaryKeys()
    {
        return $this->primaryKeys;
    }

}