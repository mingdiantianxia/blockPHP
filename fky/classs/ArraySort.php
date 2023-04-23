<?php
namespace fky\classs;

/**
 * @author mingdiantianxia
 * 数组排序类
 */
 
class ArraySort
{
    /**
     * 保持索引关联
     * @param $unsort  排序数组
     * @param $fields  排序字段,array("field"=>"字段名",'order'=>false),//desc
     * @return mixed
     */
    static function uasort($unsort, $fields)
    {
        if ( !is_array($unsort) || sizeof($unsort) <= 0 ) return $unsort;
        $sorted = self::do_uasort($unsort, $fields);
        return $sorted;
    }

    /**
     * 不保持索引关联
     * @param $unsort 排序数组
     * @param $fields 排序字段,array("field"=>"字段名",'order'=>false),//desc
     * @return mixed
     */
    static function multisort($unsort, $fields)
    {
        if ( !is_array($unsort) || sizeof($unsort) <= 0 ) return $unsort;
        $sorted = self::multi_sort($unsort, $fields);
        return $sorted;
    }

    static function multi_sort($unsort, $fields)
    {
        $sorted = $unsort;
        if (is_array($unsort))
        {
            $loadFields = array();
            foreach($fields as $sortfield)
            {
                $loadFields["field"][] = array(
                                "name" => $sortfield["field"],
                                "order" => isset($sortfield["order"])?$sortfield["order"]:null,
                                "nature" => isset($sortfield["nature"])?$sortfield["nature"]:null,
                                "caseSensitve" => isset($sortfield["caseSensitve"])?$sortfield["caseSensitve"]:null
                );
                $loadFields["data"][$sortfield["field"]] = array();
            }
            
            foreach ($sorted as $key => $row) {
                foreach($loadFields["field"] as $field) {
                    $value = $row[$field["name"]];
                    $loadFields["data"][$field["name"]][$key] = $value;
                }
            }
            $parameters = array();
            foreach($loadFields["field"] as $sortfield) {
                $array_data = $loadFields["data"][$sortfield["name"]];
                $caseSensitve = ( $sortfield["caseSensitve"] !== null ) ? $sortfield["caseSensitve"] : false;
                if (!$caseSensitve) $array_data = array_map('strtolower', $array_data);
                $parameters[] = $array_data;
                if ( $sortfield["order"] !== null ) $parameters[] = ( $sortfield["order"] ) ? SORT_ASC : SORT_DESC;
                if ( $sortfield["nature"] !== null ) $parameters[] = ( $sortfield["nature"] ) ? SORT_REGULAR : SORT_STRING;
            }
            $parameters[] = &$sorted;
            call_user_func_array("array_multisort", $parameters);
        }
        return $sorted;
    }


    static function do_uasort($unsort, $fields)
    {
        $sorted = $unsort;
        uasort($sorted,  function (&$a, &$b) use ($fields) {
		        foreach($fields as $sortfield)
		        {
		            $_field = $sortfield["field"];
		            $_order = isset($sortfield["order"]) ? $sortfield["order"] : true;
		            $_caseSensitve = isset($sortfield["caseSensitve"]) ? $sortfield["caseSensitve"] : false;
		            $_nature = isset($sortfield["nature"]) ? $sortfield["nature"] : false;
		            if ($_field != "")
		            {
		                $retval  = 0;
		                if ($_nature)
		                {
		                    if ($_caseSensitve)
		                    {
		                        $compare = strnatcmp($a[$_field], $b[$_field]);
		                    }
		                    else
		                    {
		                        $compare = strnatcasecmp($a[$_field], $b[$_field]);
		                    }
		                }
		                else
		                {
		                    if ($_caseSensitve)
		                    {
		                        $compare = strcmp($a[$_field], $b[$_field]);
		                    }
		                    else
		                    {
		                        $compare = strcasecmp($a[$_field], $b[$_field]);
		                    }
		                }
		                if ($compare !== 0 && !$_order) $compare = ($compare > 0) ? -1 : 1;
		            }
		            if ($compare !== 0) break;
		        }
		        return $compare;
	    });
        return $sorted;
    }
}