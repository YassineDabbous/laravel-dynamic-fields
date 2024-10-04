<?php

namespace YassineDabbous\DynamicQuery;

trait HasDynamicQuery{
    
    use HasDynamicFields, HasDynamicFilter, HasDynamicSort, HasDynamicSort, HasDynamicGroup;

    /** One method for all scopes. */
    public function dynamicQuery(): mixed{
        $result = static::dynamicSelect()->dynamicFilter()->dynamicOrderBy()->dynamicGroupBy()->dynamicPaginate();
        $result->dynamicAppend();
        return $result;
    }
}
