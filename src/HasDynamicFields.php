<?php

namespace YassineDabbous\DynamicFields;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

trait HasDynamicFields{

    use RelationsFinder;
    /**
     * all selectable table columns
     */
    public function dynamicColumns(): array {
        return []; // 'id', 'account_id', 'name', 'icon'
    }


    /**
     * all visible relations
     */
    public function dynamicRelations(): array{
        // return [
        //     'account',
        //      'posts' => 'user_id', // status_name depends on 'status' relation
        //      'icon_url' => ['icon', 'disk'], // icon_url depends on 'icon' and 'disk' columns
        // ];
        return $this->guessRelations();
    }

    /**
     * all visible appends with their dependencies
     */
    public function dynamicAppends(): array{
        return [
            // 'full_name',
            //  'status_name' => 'status' // status_name depends on 'status' relation
            //  'icon_url' => ['icon', 'disk'] // icon_url depends on 'icon' and 'disk' columns
        ];
    }

    public function dynamicAggregates(): array{
        return [
            // 'employees_count' => fn($q) => $q->withCount('employees'),
            // 'employees_sum_salary' => fn($q) => $q->withSum('employees', 'salary'),
        ];
    }

    public function fixArray($array){
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


    /** Select only requested fields. */
    public function scopeDynamicSelect(EloquentBuilder $q) {
        $list = request()->input('_fields', []);
        if (is_string($list)) {
            $list = explode(',', $list);
        }
        if(count($list)==0){
            return $q;
        }
        
        $dynamicAppends = $this->fixArray($this->dynamicAppends());
        $dynamicAppendsNames = array_keys($dynamicAppends);

        
        // adding appends dependencies to the list.
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

        
        // adding appends dependencies to the list.
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
            $q->with(array_unique($requestedRelations));
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


        // aggregations need to be called after select  
        if(count($dynamicAggregatesNames)){
            $requestedAggregates = array_intersect($dynamicAggregatesNames, $list);
            foreach ($requestedAggregates as $aggr) {
                $this->dynamicAggregates()[$aggr]($q);
            }
        }

        return $q;
    }


    /** Append only requests fields. */
    public function dynamicAppend(array $list = []) {
        // $this->setVisible($list);
        $dynamicAppends = $this->fixArray($this->dynamicAppends());
        $columns = array_intersect(array_keys($dynamicAppends), $list);
        $this->setAppends($columns);
    }
}
