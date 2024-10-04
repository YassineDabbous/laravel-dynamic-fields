<?php

namespace YassineDabbous\DynamicFields;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;

trait HasDynamicFields{

    use HasDynamicCore;
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
        return $this->guessDynamicRelations();
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
        return $this->getMutatedAttributes();
    }


    /**
     * Model Aggregates as Closures.
     * Values can be: Closure, named scope, NULL.
     * Keys with an empty value will be treated as a named scope.
     * 
     * Example:
     *      return [
     *          'custom'                => null,                    // equal to ->custom() scope
     *          'another_custom'        => 'named_scope',           // equal to ->namedScope() scope
     *          'employees_count'       => fn($q) => $q->withCount('employees'),
     *          'employees_sum_salary'  => fn($q) => $q->withSum('employees', 'salary'),
     *      ];
     */
    public function dynamicAggregates(): array{
        return [];
    }


    /** Append only requests fields. */
    public function dynamicAppend(array $fields = [], array $ignore = []) {
        $list = $this->parseFields($fields);
        $list = array_diff($list, $ignore);
        if(count($list)){
            $this->setVisible($list);
            $dynamicAppends = $this->toAssociative($this->dynamicAppends());
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
    public function scopeDynamicSelect(Builder $q, array $fields = [], array $ignore = []): Builder {
        $list = $this->parseFields($fields);
        $list = array_diff($list, $ignore);
        if(count($list)==0){
            return $q;
        }
        
        $dynamicAppends = $this->toAssociative($this->dynamicAppends());
        $dynamicAppendsNames = array_keys($dynamicAppends);

        
        // add Appends dependencies to the list.
        if(count($dynamicAppendsNames)){
            $list = $this->recursiveDependencies($dynamicAppends, $list);
        }
        

        $dynamicRelations = $this->toAssociative($this->dynamicRelations());
        $dynamicRelationsNames = array_keys($dynamicRelations);

        
        // add Relations dependencies to the list.
        if(count($dynamicRelationsNames)){
            $list = $this->recursiveDependencies($dynamicRelations, $list);
        }


        $requestedRelations = array_intersect($dynamicRelationsNames, $list);
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
        
        $dynamicAggregates = $this->toAssociative($this->dynamicAggregates());
        $dynamicAggregatesNames = array_keys($dynamicAggregates);

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
            foreach ($requestedAggregates as $key) {
                $value = $this->dynamicAggregates()[$key];
                if(is_null($value)){
                    if($this->hasNamedScope(\Str::camel($key))){ 
                        $this->callNamedScope(\Str::camel($key), [$q]);
                    }
                    continue;
                }
                if(is_string($value)){
                    if($this->hasNamedScope(\Str::camel($value))){ 
                        $this->callNamedScope(\Str::camel($value), [$q]);
                    }
                    continue;
                }
                if($value instanceof \Closure){
                    $value($q);
                }
            }
        }

        return $q;
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
