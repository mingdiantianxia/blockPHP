<?php 
namespace fky\func;
/**
 *  作用：array转xml
 */
function arrayToXml($arr){
    $xml = "<xml>";
    foreach ($arr as $key=>$val)
    {
         if (is_numeric($val))
         {
            $xml.="<".$key.">".$val."</".$key.">"; 

         }
         else{
            $xml.="<".$key."><![CDATA[".$val."]]></".$key.">";  
    }
    $xml.="</xml>";
    return $xml; 
    }
}  
