<?php

namespace YassineDabbous\DynamicFields;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\ServiceProvider;

class DynamicFieldsServiceProvider extends ServiceProvider
{
    public function boot()
    {        
        // dynamic fields macro
        $macro = function () {
            $list = request()->input('_fields', []);
            if (is_string($list)) {
                $list = explode(',', $list);
            }
            // if(count($list)==0){
            //     return $this;
            // }
            foreach ($this as $key => $value) {
                $value->dynamicAppend($list);
            }
            // return $this;
        };

        Collection::macro('dynamicAppend', $macro);


        //  Pagination Marco
        $macro = function (int $maxResults = null, int $defaultSize = null) {
            $maxResults = $maxResults ?? 30;
            $defaultSize = $defaultSize ?? 10;
            $request = request();
            $size = (int) $request->input('per_page', $defaultSize);
            if ($size <= 0) {
                $size = $defaultSize;
            }
            if ($size > $maxResults) {
                $size = $maxResults;
            }
            if($request->list_all == true){
                $this->get();
            }
            return $request->input('page', 0) == 1 ? $this->paginate($size) : $this->simplePaginate($size);
        };

        EloquentBuilder::macro('superPaginate', $macro);
        BaseBuilder::macro('superPaginate', $macro);
        BelongsToMany::macro('superPaginate', $macro);
        HasManyThrough::macro('superPaginate', $macro);
    }


}
