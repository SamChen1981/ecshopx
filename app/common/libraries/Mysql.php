<?php

namespace app\common\libraries;

use think\Db;

/**
 * MYSQL 公用类库
 */
class Mysql extends Db
{
    public $link_id = null;

    // fetch_array
    // affected_rows
    // num_rows
    // fetchRow

    public function query($sql, $type = '')
    {
        $m = strtolower(substr(ltrim(trim($sql), '('), 0, 6));
        if ($m == 'select' || substr($m, 0, 4) == 'desc' || substr($m, 0, 4) == 'show') {
            $query = parent::query($sql);
        } else {
            $query = parent::execute($sql);
        }

        return $query;
    }

    public function error()
    {
        return '';
    }

    public function errno()
    {
        return '';
    }

    public function insert_id()
    {
        return parent::getLastInsID();
    }

    public function version()
    {
        return '';
    }

    public function ping()
    {
        return '';
    }

    // TODO
    public static function escape_string($unescaped_string, $db)
    {
        return mysqli_real_escape_string($GLOBALS['db']->link_id, $unescaped_string);
    }

    public function ErrorMsg($message = '', $sql = '')
    {
        if ($message) {
            echo "<b>info</b>: $message\n\n<br /><br />";
        } else {
            echo "<b>MySQL server error report:";
            exit($this->error_message);
        }
    }

    public function selectLimit($sql, $num, $start = 0)
    {
        if ($start == 0) {
            $sql .= ' LIMIT ' . $num;
        } else {
            $sql .= ' LIMIT ' . $start . ', ' . $num;
        }

        return $this->query($sql);
    }

    public function getOne($sql, $limited = false)
    {
        if ($limited == true) {
            $sql = trim($sql . ' LIMIT 1');
        }

        $res = $this->query($sql);
        if ($res !== false) {
            return reset($res[0]);
        } else {
            return false;
        }
    }

    public function getOneCached($sql, $cached = 'FILEFIRST')
    {
        return false;
    }

    public function getAll($sql)
    {
        $res = $this->query($sql);
        if ($res !== false) {
            return $res;
        } else {
            return false;
        }
    }

    public function getAllCached($sql, $cached = 'FILEFIRST')
    {
        return false;
    }

    public function getRow($sql, $limited = false)
    {
        if ($limited == true) {
            $sql = trim($sql . ' LIMIT 1');
        }

        $res = $this->query($sql);
        if ($res !== false) {
            return $res[0];
        } else {
            return false;
        }
    }

    public function getRowCached($sql, $cached = 'FILEFIRST')
    {
        return false;
    }

    public function getCol($sql)
    {
        $res = $this->query($sql);
        if ($res !== false) {
            $arr = array();
            foreach ($res as $row) {
                $arr[] = $row[0];
            }

            return $arr;
        } else {
            return false;
        }
    }

    public function getColCached($sql, $cached = 'FILEFIRST')
    {
        return false;
    }

    public function autoExecute($table, $field_values, $mode = 'INSERT', $where = '', $querymode = '')
    {
        $field_names = $this->getCol('DESC ' . $table);

        $sql = '';
        if ($mode == 'INSERT') {
            $fields = $values = array();
            foreach ($field_names as $value) {
                if (array_key_exists($value, $field_values) == true) {
                    $fields[] = $value;
                    $values[] = "'" . $field_values[$value] . "'";
                }
            }

            if (!empty($fields)) {
                $sql = 'INSERT INTO ' . $table . ' (' . implode(', ', $fields) . ') VALUES (' . implode(', ', $values) . ')';
            }
        } else {
            $sets = array();
            foreach ($field_names as $value) {
                if (array_key_exists($value, $field_values) == true) {
                    $sets[] = $value . " = '" . $field_values[$value] . "'";
                }
            }

            if (!empty($sets)) {
                $sql = 'UPDATE ' . $table . ' SET ' . implode(', ', $sets) . ' WHERE ' . $where;
            }
        }

        if ($sql) {
            return $this->query($sql, $querymode);
        } else {
            return false;
        }
    }

    public function autoReplace($table, $field_values, $update_values, $where = '', $querymode = '')
    {
        $field_descs = $this->getAll('DESC ' . $table);

        $primary_keys = array();
        foreach ($field_descs as $value) {
            $field_names[] = $value['Field'];
            if ($value['Key'] == 'PRI') {
                $primary_keys[] = $value['Field'];
            }
        }

        $fields = $values = array();
        foreach ($field_names as $value) {
            if (array_key_exists($value, $field_values) == true) {
                $fields[] = $value;
                $values[] = "'" . $field_values[$value] . "'";
            }
        }

        $sets = array();
        foreach ($update_values as $key => $value) {
            if (array_key_exists($key, $field_values) == true) {
                if (is_int($value) || is_float($value)) {
                    $sets[] = $key . ' = ' . $key . ' + ' . $value;
                } else {
                    $sets[] = $key . " = '" . $value . "'";
                }
            }
        }

        $sql = '';
        if (empty($primary_keys)) {
            if (!empty($fields)) {
                $sql = 'INSERT INTO ' . $table . ' (' . implode(', ', $fields) . ') VALUES (' . implode(', ', $values) . ')';
            }
        } else {
            if (!empty($fields)) {
                $sql = 'INSERT INTO ' . $table . ' (' . implode(', ', $fields) . ') VALUES (' . implode(', ', $values) . ')';
                if (!empty($sets)) {
                    $sql .= 'ON DUPLICATE KEY UPDATE ' . implode(', ', $sets);
                }
            }
        }

        if ($sql) {
            return $this->query($sql, $querymode);
        } else {
            return false;
        }
    }
}
