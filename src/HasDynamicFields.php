<?php

namespace YassineDabbous\DynamicFields;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;

trait HasDynamicFields{

    use RelationsFinder;

    protected $deepFields = [];


    /**
     * All selectable table columns
     */
    public function dynamicColumns(): array {
        return [];
    }


    /**
     * Accessable Model Relations with their dependencies.
     * Example:
     *     return [
     *       'user' => 'user_id',                                        // "user" relation depends on 'user_id' column
     *       'commentable' => ['commentable_type', 'commentable_id'],    // "morphable" relation depends on 'morphable_type' and 'morphable_id' columns
     *       'replies' => null,                                          // "replies" relation doesn't has dependencies
     *     ];
     */
    public function dynamicRelations(): array{
        return $this->guessRelations();
    }


    /**
     * All visible appends with their dependencies.
     * Example:
     *   return [
     *        'status_name'     => 'status',                            // "status_name" depends on 'status' relation
     *        'full_name'       => ['first_name', 'last_name'],         // "full_name" depends on 'first_name' and 'last_name' columns
     *        'custom_key',                                             // "custom_key" doesn't has dependencies
     *   ];
     */
    public function dynamicAppends(): array{
        return [];
    }


    /**
     * Model Aggregates as Closures.
     * Example:
     *      return [
     *          'employees_count'       => fn($q) => $q->withCount('employees'),
     *          'employees_sum_salary'  => fn($q) => $q->withSum('employees', 'salary'),
     *      ];
     */
    public function dynamicAggregates(): array{
        return [];
    }


    /** Append only requests fields. */
    public function dynamicAppend(array $fields = []) {
        $list = $this->parseFields($fields);
        if(count($list)){
            $this->setVisible($list);
            $dynamicAppends = $this->fixArray($this->dynamicAppends());
            $columns = array_intersect(array_keys($dynamicAppends), $list);
            if(count($columns)){
                $this->setAppends($columns);
            }

            // add appends to child relations
            foreach ($this->deepFields as $key => $deepFs) {
                if($this->{$key} instanceof EloquentCollection){
                    foreach ($this->{$key} as $relation) {
                        $relation->dynamicAppend($deepFs);
                    }
                } 
                else if($this->{$key} instanceof Model){
                    $this->{$key}?->dynamicAppend($deepFs);
                }
            }
        }
    }


    /** Select requested columns, eager load relations and call aggregates. */
    public function scopeDynamicSelect(Builder $q, array $fields = []) {
        $list = $this->parseFields($fields);
        if(count($list)==0){
            return $q;
        }
        
        $dynamicAppends = $this->fixArray($this->dynamicAppends());
        $dynamicAppendsNames = array_keys($dynamicAppends);

        
        // add Appends dependencies to the list.
        if(count($dynamicAppendsNames)){
            $requestedAppends = array_intersect($dynamicAppendsNames, $list);
            $filtered = array_filter(
                $dynamicAppends,
                fn ($key) => in_array($key, $requestedAppends),
                ARRAY_FILTER_USE_KEY
            );
            foreach($filtered as $deps){
                if(is_null($deps)){
                    continue;
                }
                if(is_array($deps)){
                    foreach ($deps as $dep) {
                        $list[] = $dep;
                    }
                } else {
                    $list[] = $deps;
                }
            }
        }
        

        $dynamicRelations = $this->fixArray($this->dynamicRelations());
        $dynamicRelationsNames = array_keys($dynamicRelations);
        $requestedRelations = array_intersect($dynamicRelationsNames, $list);

        
        // add Relations dependencies to the list.
        if(count($dynamicRelationsNames)){
            $filtered = array_filter(
                $dynamicRelations,
                fn ($key) => in_array($key, $requestedRelations),
                ARRAY_FILTER_USE_KEY
            );
            foreach($filtered as $deps){
                if(is_null($deps)){
                    continue;
                }
                if(is_array($deps)){
                    foreach ($deps as $dep) {
                        $list[] = $dep;
                    }
                } else {
                    $list[] = $deps;
                }
            }
        }



        if(count($requestedRelations)){
            $rs = array_unique($requestedRelations);
            foreach ($rs as $r) {
                if(array_key_exists($r, $this->deepFields)) {
                    $q->with($r, fn($rq) => $rq->dynamicSelect($this->deepFields[$r]));
                } else {
                    $q->with($r);
                }
            }
        }
        
        
        $dynamicAggregatesNames = array_keys($this->dynamicAggregates());

        if(!in_array('*', $list)){
            if(count($this->dynamicColumns())){
                $requestedColumns = array_intersect($this->dynamicColumns(), $list);
            } else {
                $x = [
                    ...$dynamicRelationsNames,
                    ...$dynamicAggregatesNames,
                    ...$dynamicAppendsNames
                ];
                $requestedColumns = array_diff($list, $x);
            }

            
            if(count($requestedColumns)){
                 $q->select(array_unique($requestedColumns));
            }
        }


        // *Aggregates must be called after selection
        if(count($dynamicAggregatesNames)){
            $requestedAggregates = array_intersect($dynamicAggregatesNames, $list);
            foreach ($requestedAggregates as $aggr) {
                $this->dynamicAggregates()[$aggr]($q);
            }
        }

        return $q;
    }

    
    /** Indexed to Associative Array */
    public function fixArray($array): array{
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

    public function parseFields(array $fields = []): array {
        $fields = count($fields) ? $fields : request()->input('_fields', []);
        if (is_string($fields)) {
            $fields = explode(',', $fields);
        }
        if(count($fields)==0){
            return [];
        }

        $list = [];
        foreach($fields as $f){
            $r = explode(':', $f);
            $list[] = $r[0];
            if(count($r) == 2){
                $this->deepFields[$r[0]] = explode('|', $r[1]);
            }
        }
        return $list;
    }
 
}
