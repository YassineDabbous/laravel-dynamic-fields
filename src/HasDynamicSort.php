<?php

namespace YassineDabbous\DynamicQuery;

use Illuminate\Database\Eloquent\Builder;

trait HasDynamicSort {
    use HasDynamicCore;

    /**
     * Allowed columns for OrderBy clause.
     *
     * Examples:
     *      /endpoint?_sort=id,-price
     *      /endpoint?_sort[]=id&_sort[]=-price
     * 
    */
    public function dynamicSorts(): array {
        return [];
    }
     


    public function scopeDynamicOrderBy(Builder $q, array $allowed = [], array $default = [], array $ignore = []): Builder {

        $input = request()->input('_sort', []);

        if(is_string($input)){
            $input = explode(',', $input);
        }

        if(count($input)){
            $allSorts = count($allowed) ? $allowed : $this->dynamicSorts();
            $allowedSorts = array_filter($allSorts, fn($k) => !in_array($k, $ignore));

            $requestedSorts = [];
            foreach ($input as $value) {
                if(str_starts_with($value, '-')){
                    $requestedSorts[str_replace('-', '', $value)] = 'desc';
                } else {
                    $requestedSorts[$value] = 'asc';
                }
            }
    
            $filtered = array_intersect_key($requestedSorts, $this->toAssociative($allowedSorts));
    
            foreach ($filtered as $column => $direction) {
                $q->orderBy($column, $direction);
            }
        } else if(count($default)){
            $sorts = $this->toAssociative($default);
            foreach ($sorts as $column => $direction) {
                $q->orderBy($column, $direction == 'desc' ? 'desc' : 'asc'); 
            }
        }

        return $q;
    }

 
}
