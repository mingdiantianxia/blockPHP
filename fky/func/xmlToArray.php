<?php 
namespace fky\func;
/**
 *  作用：将xml转为array
 */
function xmlToArray($xml)
{       
    //将XML转为array        
    $array_data = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);      
    return $array_data;
}

 ?>