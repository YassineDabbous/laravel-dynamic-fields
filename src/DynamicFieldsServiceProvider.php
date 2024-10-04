<?php

namespace YassineDabbous\DynamicQuery;

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
        $macro = function (array $fields = [], array $ignore = []) {
            foreach ($this as $model) {
                $model->dynamicAppend($fields, $ignore);
            }
        };

        Collection::macro('dynamicAppend', $macro);


        //  Pagination Marco
        $macro = function (?int $maxPerPage = null, bool $allowGet = true, $columns = ['*'], $pageName = 'page', $page = null, $total = null) {
            $request = request();
            /** @var \Illuminate\Database\Eloquent\Builder $this  */
            if($allowGet && $request->input('_get_all', false)){
                $this->get($columns);
            }

            $maxPerPage ??= 30;
            $defaultSize = 10;
            $size = (int) $request->input('per_page', $defaultSize);
            if ($size <= 0) {
                $size = $defaultSize;
            }
            if ($size > $maxPerPage) {
                $size = $maxPerPage;
            }

            return $request->input($pageName, 0) == 1 ? $this->paginate($size, $columns, $pageName, $page, $total) : $this->simplePaginate($size, $columns, $pageName, $page);
        };

        EloquentBuilder::macro('dynamicPaginate', $macro);
        BaseBuilder::macro('dynamicPaginate', $macro);
        BelongsToMany::macro('dynamicPaginate', $macro);
        HasManyThrough::macro('dynamicPaginate', $macro);
    }


}
