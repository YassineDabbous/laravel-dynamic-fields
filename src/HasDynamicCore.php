<?php

namespace YassineDabbous\DynamicFields;

trait HasDynamicCore {


    /** All values ​​will be of type "array". */
    public function fixArray($array): array{
        $array = $this->toAssociative($array);
        foreach($array as $k => $v){
            if(is_null($v)){
                $array[$k] = [];
            } else if(!is_array($v)){
                $array[$k] = [$v];
            }
        }
        return $array;
    }


    /** Indexed to Associative Array */
    public function toAssociative($array): array{
        $arr = [];
        foreach($array as $k => $v){
            if(is_int($k)){
                $arr[$v] = null;
            } else {
                $arr[$k] = $v;
            }
        }
        return $arr;
    }

}
