<?php

namespace YassineDabbous\DynamicQuery;

use Illuminate\Database\Eloquent\Builder;

trait HasDynamicGroup {
    use HasDynamicCore;

    /**
     * Allowed columns for GroupBy clause.
     *
     * Examples:
     *      /endpoint?_group=name,price
     *      /endpoint?_group[]=name&_group[]=price
     * 
    */
    public function dynamicGroups(): array {
        return [];
    }
     


    public function scopeDynamicGroupBy(Builder $q, array $allowed = [], array $default = [], array $ignore = []): Builder {
        
        /** @var \Illuminate\Http\Request $request */
        $request = request();

        $input = $request->input('_group', []);
        if(is_string($input)){
            $input = explode(',', $input);
        }

        if(count($input)){
            $allGroups = count($allowed) ? $allowed : $this->dynamicGroups();
            $filtered = array_filter($allGroups, fn($k) => !in_array($k, $ignore));

            $requestedGroups = array_values($input);
    
            $allowedGroups = array_intersect($requestedGroups, $filtered);
    
            $q->groupBy($allowedGroups);
        } 
        else if(count($default)){
            $q->groupBy($default);
        }

        return $q;
    }

 
}
