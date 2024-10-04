<?php

namespace YassineDabbous\DynamicQuery;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\ServiceProvider;

class DynamicFieldsServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Getting dynamic model from morph map.
        EloquentBuilder::macro('dynamicModel', function(?string $default = null, array $whitelist = []){
            $type = request()->input('_model', $default);
            if(!$type){
                throw new HttpResponseException(response('morph alias required', 400));
            }
            if(count($whitelist) && !in_array($type, $whitelist)){
                // $type = $default;
                throw new HttpResponseException(response('unauthorized morph alias', 403));
            }
            $class = Relation::getMorphedModel($type);
            if(!$class){
                throw new HttpResponseException(response('unknown morph alias', 400));
            }
            // \Log::error($class);
            return new $class;
        });


        // Dynamic model appends
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
            if($allowGet && $request->boolean('_get_all', false)){
                if($limit = $request->integer('_limit', 0)){
                    $this->limit($limit);
                }
                return $this->get($columns);
            }

            $maxPerPage ??= 30;
            $defaultSize = 10;
            $size = (int) $request->integer('per_page', $defaultSize);
            if ($size <= 0) {
                $size = $defaultSize;
            }
            if ($size > $maxPerPage) {
                $size = $maxPerPage;
            }

            return $request->integer($pageName, 0) == 1 ? $this->paginate($size, $columns, $pageName, $page, $total) : $this->simplePaginate($size, $columns, $pageName, $page);
        };

        EloquentBuilder::macro('dynamicPaginate', $macro);
        BaseBuilder::macro('dynamicPaginate', $macro);
        BelongsToMany::macro('dynamicPaginate', $macro);
        HasManyThrough::macro('dynamicPaginate', $macro);
    }


}
