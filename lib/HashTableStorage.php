<?php

/**
 * хранилище key/value на mysql
 *
 * PHP version 5
 *
 * @category Website
 * @package  Application
 * @author   Vladimir Chmil <vladimir.chmil@gmail.com>
 * @license  http://mit-license.org/ MIT license
 * @link     http://xxx
 */

/**
 * хранилище key/value на mysql.
 *
 * Идея:
 * - использовать табл. mysql для хранения key/value
 * - engine таблицы - MEMORY или InnoDB (указ. в конструкторе)
 * - в случае MEMORY - работать непосредственно с табл (get/set).
 * - в случае InnoDB - в конструкторе читаем все записи в массив,
 * работаем с ним, в деструкторе пишем все назад в табл. INSERT'ы
 * завернуты в транзакции.
 *
 * !!! В mysql дефолтное ограничение по размеру табл. MEMORY - 16Mb !!!
 *
 * PHP version 5
 *
 * @category Website
 * @package  Application
 * @author   Vladimir Chmil <vladimir.chmil@gmail.com>
 * @license  http://mit-license.org/ MIT license
 * @link     http://xxx
 */
class HashTableStorage
{
    protected static $db;
    protected static $mem_storage;
    protected $table = "storage";
    protected $credentials = array(
        'user' => 'devtest',
        'pass' => 'devtest',
        'db'   => 'devtest',
        'host' => 'localhost'
    );
    protected $useMemoryTable;

    /**
     * @param string $table          имя табл.
     * @param bool   $useMemoryTable исп. MEMORY (true) или InnoDB (false)
     */
    public function __construct($table = "", $useMemoryTable = false)
    {
        if (! empty($table)) {
            $this->table = filter_var($table, FILTER_SANITIZE_STRING);
        }

        $this->useMemoryTable = $useMemoryTable;

        if (is_null(self::$db)) {
            self::$db = new mysqli(
                $this->credentials['host'],
                $this->credentials['user'],
                $this->credentials['pass'],
                $this->credentials['db']
            );
        }

        $this->initDBStorage();

        if (is_null(self::$mem_storage) && $this->useMemoryTable === false) {
            self::$mem_storage = array();
            $res               = self::$db->query("select `key`, `value` from {$this->table}");

            if ($res->num_rows != 0) {
                while ($row = $res->fetch_object()) {
                    self::$mem_storage[$row->key] = $row->value;
                }
            }
        }
    }

    /**
     * создает табл. если ее нет
     */
    protected function initDBStorage()
    {
        $res = self::$db->query("show tables like '{$this->table}'");

        if ($res->num_rows == 0) {
            $tblType = ($this->useMemoryTable === true) ? "MEMORY" : "InnoDB";

            $sql = <<<EOD
CREATE TABLE `{$this->table}` (
`key` VARCHAR(32),
`value` VARCHAR(10000),
PRIMARY KEY (`key`)
) ENGINE={$tblType} DEFAULT CHARSET=utf8 COLLATE utf8_bin;
EOD;

            $res = self::$db->query($sql);
        }
    }

    /**
     * в случае InnoDB - пишем в базу массив
     */
    public function __destruct()
    {
        if ($this->useMemoryTable === false) {
            self::$db->query("TRUNCATE TABLE `{$this->table}`");
            self::$db->query("START TRANSACTION");

            foreach (self::$mem_storage as $key => $val) {
                $stmt = self::$db->prepare(
                    "INSERT INTO {$this->table} (`key`,`value`) VALUES (?, ?)"
                );

                $stmt->bind_param("ss", $key, $val);

                $stmt->execute();
            }

            self::$db->query("COMMIT");
        }
    }

    public function set($key, $val)
    {
        if ($this->useMemoryTable === false) {
            self::$mem_storage[md5($key)] = serialize($val);
        } else {
            $stmt = self::$db->prepare(
                "INSERT INTO {$this->table} (`key`,`value`) VALUES (?, ?)"
            );

            $key = md5($key);
            $val = serialize($val);
            $stmt->bind_param("ss", $key, $val);
            $stmt->execute();
        }
    }

    public function get($key)
    {
        if ($this->useMemoryTable === false) {
            return unserialize(self::$mem_storage[md5($key)]);
        } else {
            $stmt = self::$db->prepare(
                "SELECT `value` FROM {$this->table} WHERE `key`=md5(?)"
            );

            $stmt->bind_param('s', $key);
            $stmt->execute();

            $stmt->bind_result($val);

            return unserialize($val);
        }
    }
}