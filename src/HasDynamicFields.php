<?php

namespace YassineDabbous\DynamicFields;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

trait HasDynamicFields{
    
    /**
     * all selectable table columns
     */
    public function dynamicColumns(){
        return []; // 'id', 'account_id', 'name', 'icon'
    }

    /**
     * all visible relations
     */
    public function dynamicRelations(){
        return [];
    }

    /**
     * all visible appends with their columns dependencies
     */
    public function dynamicAppendsDepsColumns(){
        return [
            // 'icon_url' => 'icon' // icon_url depends on 'icon' columns
        ];
    }

    /**
     * all visible appends with their relations dependencies
     */
    public function dynamicAppendsDepsRelations(){
        return [
            // 'status_name' => 'status' // status_name depends on 'status' Relation
        ];
    }


    public function dynamicAggregates(){
        return [
            // 'employees_count' => fn($q) => $q->withCount('employees'),
            // 'employees_sum_salary' => fn($q) => $q->withSum('employees', 'salary'),
        ];
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

        if(count($this->dynamicColumns())){
            $deps = [];
            if(count($this->dynamicAppendsDepsColumns())){
                $appends = array_intersect(array_keys($this->dynamicAppendsDepsColumns()), $list);
                $filtered = array_filter(
                    $this->dynamicAppendsDepsColumns(),
                    fn ($key) => in_array($key, $appends),
                    ARRAY_FILTER_USE_KEY
                );
                $deps = array_values($filtered);
            }
            $columns = [
                ...array_intersect($this->dynamicColumns(), $list), 
                ...array_intersect($this->dynamicColumns(), $deps)
            ];
            // \Log::debug($columns);
            if(count($columns)){
                 $q->select(array_unique($columns));
            }
        }


        if(count($this->dynamicRelations())){
            $deps = [];
            if(count($this->dynamicAppendsDepsRelations())){
                $appends = array_intersect(array_keys($this->dynamicAppendsDepsRelations()), $list);
                $filtered = array_filter(
                    $this->dynamicAppendsDepsRelations(),
                    fn ($key) => in_array($key, $appends),
                    ARRAY_FILTER_USE_KEY
                );
                $deps = array_values($filtered);
            }
            $relations = [
                ...array_intersect($this->dynamicRelations(), $list), 
                ...array_intersect($this->dynamicRelations(), $deps)
            ];
            // \Log::debug($relations);
            if(count($relations)){
                $q->with(array_unique($relations));
            }
        }

        
        if(count($this->dynamicAggregates())){
            $cals = array_intersect(array_keys($this->dynamicAggregates()), $list);
            foreach ($cals as $value) {
                $this->dynamicAggregates()[$value]($q);
            }
        }

        return $q;
    }


    /** Append only requests fields. */
    public function dynamicAppend(array $list = []) {
        $columns = array_intersect(array_keys($this->dynamicAppendsDepsColumns()), $list);
        $this->setAppends($columns);
    }
}
