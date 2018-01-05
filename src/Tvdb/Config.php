<?php

namespace Leedch\Trakt\Tvdb;

use Leedch\Mysql\Mysql;

/**
 * Description of Config
 *
 * @author leed
 */
class Config extends Mysql
{
    protected $id;
    protected $name;
    protected $value;
    protected $createDate;
    
    protected $entries;
    
    protected function getTableName() : string
    {
        return tvdb_mysqlTableConfig;
    }
    
    /**
     * Gets a single config by its name
     * @param string $name
     * @return array
     */
    protected function getByName(string $name)
    {
        $arrResult = $this->getAllRows(['*'], ["`name` = '".$name."'"], [], [0,1]);
        $result = array_pop($arrResult);
        return $result;
    }

    /**
     * Updates or Creates a Trakt Configuration
     * @param string $name
     * @param string $value
     */
    public function setConfig(string $name, string $value)
    {
        $config = new Config();
        $data = $this->getByName($name);
        if ($data) {
            $config->loadWithData($data);
        }
        $config->name = $name;
        $config->value = $value;
        $config->createDate = strftime("%Y-%m-%d %H:%M:%S");
        $config->save();
    }
    
    public function getConfig(string $name)
    {
        $row = $this->getByName($name);
        if (isset($row['value'])) {
            return (string) $row['value'];
        }
        return null;
    }
    
    public function getConfigRow(string $name)
    {
        $row = $this->getByName($name);
        if (isset($row['value'])) {
            return (array) $row;
        }
        return null;
    }
}
