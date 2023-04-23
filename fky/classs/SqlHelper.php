<?php
namespace fky\classs;

/**
 * sql工具类
 * Class SqlHelper
 * @package app\common\base
 */
class SqlHelper
{
    /**
     * 创建多个字段的in查询条件
     * @param $inFieldArr -字段数组
     * @param $valueArr -对应的数值二维数组，如：[[1,'good'], [1,'good']]
     * @return string
     */
    public static function buildSqlWhereForMultifieldIn($inFieldArr,$valueArr)
    {
        $fieldStr = '';
        $valueStr = '';
        foreach ($inFieldArr as $fieldItem) {
            if (strpos($fieldItem, '.') !== false) {
                $fieldItem = explode('.', $fieldItem);
                $fieldItem = $fieldItem[0] . '.`' . $fieldItem[1] . '`';
                $fieldStr .= $fieldItem . ',';
            } else {
                $fieldStr .= '`' . $fieldItem . '`' . ',';
            }
        }

        $fieldStr = trim($fieldStr, ',');

        foreach ($valueArr as $vItem) {
            $vItem = array_map(function ($item) {
                if (is_int($item)) {
                    return $item;
                }

                if (stripos($item, '"') !== false && stripos($item, "'") !== false) {
                    //同时有单引号双引号
                    $item = str_replace("'", "\'", $item);
                    $item = "'" . $item . "'";
                } elseif(stripos($item, '"') !== false) {
                    //有双引号,用单引号括起来
                    $item = "'" . $item . "'";
                } elseif(stripos($item, "'") !== false) {
                    //有单引号,用双引号括起来
                    $item = '"' . $item . '"';
                } else {
                    $item = "'" . $item . "'";
                }

                return $item;
            }, $vItem);

            $valueStr .= '('.implode(',', $vItem).'),';
        }
        $valueStr = trim($valueStr, ',');


        $sqlStr = '(' . $fieldStr . ') in ('. $valueStr .')';
        return $sqlStr;
    }

    /**
     * 创建 int字段+一个字段 多个in查询条件
     * @param $inFieldArr -字段名数组
     * @param $valueArr -对应的数值二维数组，如：[[int,字段值], [1,'good']]
     * @return string
     */
    public static function buildSqlWhereForId2FieldManyIn($inFieldArr,$valueArr)
    {
        $fieldMap = [];
        foreach ($inFieldArr as $fieldItem) {
            if (strpos($fieldItem, '.') !== false) {
                $fieldItem = explode('.', $fieldItem);
                $fieldItem = $fieldItem[0] . '.`' . $fieldItem[1] . '`';
                $fieldMap[] = $fieldItem;
            } else {
                $fieldMap[] = '`' . $fieldItem . '`';
            }
        }

        $id2FieldArr = [];
        foreach ($valueArr as $vItem) {
            $id2FieldArr[$vItem[0]][] = $vItem[1];
        }

        $sqlStr = '';
        foreach ($id2FieldArr as $id => $fieldArr) {
            $sqlStr .= " ({$fieldMap[0]}=$id and {$fieldMap[1]} in";

            $fieldArr = array_map(function ($item) {
                if (is_int($item)) {
                    return $item;
                }

                if (stripos($item, '"') !== false && stripos($item, "'") !== false) {
                    //同时有单引号双引号
                    $item = str_replace("'", "\'", $item);
                    $item = "'" . $item . "'";
                } elseif(stripos($item, '"') !== false) {
                    //有双引号,用单引号括起来
                    $item = "'" . $item . "'";
                } elseif(stripos($item, "'") !== false) {
                    //有单引号,用双引号括起来
                    $item = '"' . $item . '"';
                } else {
                    $item = "'" . $item . "'";
                }

                return $item;
            }, $fieldArr);

            $sqlStr .= '('.implode(',', $fieldArr).')) or';
        }

        return trim($sqlStr, 'or');
    }

    /**
     * 构建批量insert sql语句
     * @param $table -表
     * @param $data -二维数组
     * @return string
     */
    public static function buildInsertAllSql($table, $data)
    {
        $insertKeys = [];
        $valueStr = '';

        foreach ($data as $index => $item) {
            foreach ($item as $key => $value) {
                if (0 === $index) {
                    $insertKeys[] = "`{$key}`";
                }

                if (!is_int($value)) {
                    $item[$key] = "'" . $value . "'";
                }
            }

            $valueStr .= '('.implode(',', $item).'),';
        }
        unset($data);

        //生成insert
        $valueStr = trim($valueStr, ',');
        $fieldStr = implode(",", $insertKeys);
        return "insert into `{$table}` ({$fieldStr}) values  {$valueStr}";
    }



    /**
     * 构建批量insert失败则update sql语句
     * @param $table -表
     * @param $data -二维数组
     * @return string
     */
    public static function buildInsertToUpdateSql($table, $data, $incrFields=[], $replaceFields=[])
    {
        $insertKeys = [];
        $valueStr = '';

        foreach ($data as $index => $item) {
            foreach ($item as $key => $value) {
                if (0 === $index) {
                    $insertKeys[] = "`{$key}`";
                }

                if (!is_int($value)) {
                    $item[$key] = "'" . $value . "'";
                }
            }

            $valueStr .= '('.implode(',', $item).'),';
        }
        unset($data);
        $duplicateKey = "";

        if ($incrFields) {
            foreach ($incrFields as $field) {
                if (in_array("`{$field}`", $insertKeys)) {
                    $duplicateKey .= " `{$field}`=`{$field}`+VALUES(`{$field}`),";
                }
            }
        }

        if ($replaceFields) {
            foreach ($replaceFields as $field) {
                if (in_array($field, $insertKeys)) {
                    $duplicateKey .= " `{$field}`=VALUES(`{$field}`),";
                }
            }
        }

        if (!empty($duplicateKey)) {
            $duplicateKey = " ON DUPLICATE KEY UPDATE" . $duplicateKey;
            $duplicateKey = trim($duplicateKey, ',');
        }

        //生成insert
        $valueStr = trim($valueStr, ',');
        $fieldStr = implode(",", $insertKeys);
        return "insert into `{$table}` ({$fieldStr}) values  {$valueStr} {$duplicateKey}";
    }

    /**
     * 构建更新sql语句
     * @param $table
     * @param $data
     * @param $where
     * @return string
     */
    public static function buildUpdateSql($table, $data, $where)
    {
        $updateStr = '';
        $whereStr = '';

        foreach ($data as $key => $item) {
            if (!is_int($item)) {
                $item = "'" . $item . "'";
            }
            $updateStr .= " `{$key}`=". $item .',';
        }
        unset($data);

        foreach ($where as $key => $val)
        {
            if (!is_int($val)) {
                $val = "'" . $val . "'";
            }
            $whereStr .= " `{$key}`=". $val .' AND';
        }
        unset($where);

        $updateStr = trim($updateStr, ',');
        $whereStr = trim($whereStr, 'AND');

        return "UPDATE `{$table}` SET {$updateStr} WHERE {$whereStr}";
    }

    /**
     * 构建递增更新sql语句
     * @param $table
     * @param $data
     * @param $where
     * @return string
     */
    public static function buildIncrSql($table, $data, $where)
    {
        $updateStr = '';
        $whereStr = '';

        foreach ($data as $key => $step) {
            $updateStr .= " `{$key}`=`{$key}`+{$step},";
        }
        unset($data);

        foreach ($where as $key => $val)
        {
            if (!is_int($val)) {
                $val = "'" . $val . "'";
            }
            $whereStr .= " `{$key}`=". $val .' AND';
        }
        unset($where);

        $updateStr = trim($updateStr, ',');
        $whereStr = trim($whereStr, 'AND');

        return "UPDATE `{$table}` SET {$updateStr} WHERE {$whereStr}";
    }

    /**
     * 构建删除语句sql语句
     * @param $table
     * @param $where
     * @return string
     */
    public static function buildDeleteSql($table, $where)
    {
        $whereStr = '';
        foreach ($where as $key => $val)
        {
            if (is_array($val)) {
                $val = array_map(function ($v) {
                    if (!is_int($v)) {
                        return "'{$v}'";
                    }
                    return $v;
                }, $val);
                $val = 'in(' . implode(",", $val) . ')';
            }
            elseif (!is_int($val)) {
                $val = "='" . $val . "'";
            } else {
                $val = "=" . $val;
            }

            $whereStr .= " `{$key}`". $val .' AND';
        }
        $whereStr = trim($whereStr, 'AND');

        return "DELETE FROM `{$table}` WHERE {$whereStr}";
    }
}