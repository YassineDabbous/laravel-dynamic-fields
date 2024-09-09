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


    /** Collect child values. */
    public function recursiveDependencies(array $associative, array $keys): array{
        $more = true;
        $requested = array_intersect(array_keys($associative), $keys);
        while ($more) {
            $filtered = array_filter(
                $associative,
                fn ($key) => in_array($key, $requested),
                ARRAY_FILTER_USE_KEY
            );
            $requested = [];
            $more = false;
            foreach($filtered as $deps){
                if(is_null($deps)){
                    continue;
                }
                $more = true;
                if(is_array($deps)){
                    foreach ($deps as $dep) {
                        $keys[] = $dep;
                        $requested[] = $dep;
                    }
                } else {
                    $keys[] = $deps;
                    $requested[] = $deps;
                }
            }
        }
        return $keys;
    }

}
