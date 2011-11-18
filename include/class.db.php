<?php

class Garradin_DB extends SQLite3
{
    static protected $_instance = null;

    static public function getInstance()
    {
        return self::$_instance ?: self::$_instance = new Garradin_DB;
    }

    private function __clone()
    {
    }

    public function __construct()
    {
        $exists = file_exists(GARRADIN_DB_FILE) ? true : false;

        parent::__construct(GARRADIN_DB_FILE);

        if (!$exists)
        {
            $this->exec('BEGIN;');
            $this->exec(file_get_contents(GARRADIN_DB_SCHEMA));
            $this->exec('END;');
        }
    }

    public function escape($str)
    {
        return $this->escapeString($str);
    }

    public function e($str)
    {
        return $this->escapeString($str);
    }

    protected function _getArgType($arg, $name = '')
    {
        if (is_float($arg))
            return SQLITE3_FLOAT;
        elseif (is_numeric($arg))
            return SQLITE3_INTEGER;
        elseif (is_bool($arg))
            return SQLITE3_INTEGER;
        elseif (is_null($arg))
            return SQLITE3_NULL;
        elseif (is_string($arg))
            return SQLITE3_TEXT;
        else
            throw new InvalidArgumentException('Argument '.$name.' is of invalid type '.gettype($arg));
    }

    public function simpleStatement($query)
    {
        $statement = $this->prepare($query);

        if (func_num_args() == 2 && is_array(func_get_arg(1)))
        {
            if (count(func_get_arg(1)) != $statement->paramCount())
            {
                throw new LengthException('Only '.(func_num_args() - 1).' arguments in array, but '.$statement->paramCount().' are required by query.');
            }

            foreach (func_get_arg(1) as $key=>$value)
            {
                if (is_int($key))
                {
                    throw new InvalidArgumentException(__FUNCTION__ . ' requires second argument to be a named-associative array, but key '.$key.' is an integer.');
                }

                $statement->bindValue(':'.$key, $value, $this->_getArgType($value, $key));
            }
        }
        else
        {
            if (func_num_args() - 1 != $statement->paramCount())
            {
                throw new LengthException('Only '.(func_num_args() - 1).' arguments, but '.$statement->paramCount().' are required by query.');
            }

            for ($i = 1; $i < func_num_args(); $i++)
            {
                $arg = func_get_arg($i);
                $statement->bindValue($i, $arg, $this->_getArgType($arg, $i));
            }
        }

        return $statement->execute();
    }

    public function simpleStatementFetch($query, $mode = SQLITE3_BOTH)
    {
        return $this->_fetchResult($this->simpleStatement($query), $mode);
    }

    public function escapeAuto($value, $name = '')
    {
        $type = $this->_getArgType($value, $name);

        switch ($type)
        {
            case SQLITE3_FLOAT:
                return floatval($value);
            case SQLITE3_INTEGER:
                return intval($value);
            case SQLITE3_NULL:
                return 'NULL';
            case SQLITE3_TEXT:
                return '\'' . $this->escapeString($value) . '\'';
        }
    }

    /**
     * Returns a correct, escaped query from a query statement and list of arguments,
     * either as named array or as a list of indexed arguments.
     */
    protected function _getSimpleQuery($query, $args)
    {
        if (count($args) == 1 && is_array($args[0]))
        {
            $nb = preg_match_all('/:[a-z]+/', $query, $_matches);

            if (count($args[0]) != $nb)
            {
                throw new LengthException('Only '.count($args[0]).' arguments in array, but '.$nb.' are required by query.');
            }

            foreach ($args[0] as $key=>$value)
            {
                if (is_int($key))
                {
                    throw new InvalidArgumentException(__FUNCTION__ . ' requires second argument to be a named-associative array, but key '.$key.' is an integer.');
                }

                $query = str_replace(':'.$key, $this->escapeAuto($value, $key), $query);
            }
        }
        else
        {
            $nb = substr_count($query, '?');

            if (count($args) != $nb)
            {
                throw new LengthException('Only '.count($args).' arguments, but '.$nb.' are required by query.');
            }

            for ($i = 1; $i <= count($args); $i++)
            {
                $arg = $args[$i - 1];
                $arg = $this->escapeAuto($arg, $i);

                $pos = strpos($query, '?');
                $query = substr_replace($query, $arg, $pos, 1);
            }
        }

        return $query;
    }

    /**
     * Formats and escapes a statement and then returns the result of exec()
     */
    public function simpleExec($query)
    {
        $args = array_slice(func_get_args(), 1);
        $query = $this->_getSimpleQuery($query, $args);
        return $this->exec($query);
    }

    public function simpleQuerySingle($query, $all_row = false)
    {
        $args = array_slice(func_get_args(), 2);
        $query = $this->_getSimpleQuery($query, $args);
        return $this->querySingle($query, $all_row);
    }

    public function queryFetch($query, $mode = SQLITE3_BOTH)
    {
        return $this->_fetchResult($this->query($query));
    }

    public function queryFetchAssoc($query)
    {
        return $this->_fetchResultAssoc($this->query($query));
    }

    protected function _fetchResult($result, $mode)
    {
        $out = array();

        while ($row = $result->fetchArray($mode))
        {
            $out[] = $row;
        }

        $res->finalize();
        unset($res, $row);

        return $out;
    }

    protected function _fetchResultAssoc($result)
    {
        $out = array();

        while ($row = $result->fetchArray(SQLITE3_NUM))
        {
            $out[$row[0]] = $row[1];
        }

        $res->finalize();
        unset($res, $row);

        return $out;
    }

    public function __destruct()
    {
        $this->close();
    }
}

?>