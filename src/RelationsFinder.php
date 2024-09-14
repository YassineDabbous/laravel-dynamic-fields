<?php

namespace YassineDabbous\DynamicFields;

use Illuminate\Database\Eloquent\Relations\Relation;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionUnionType;

trait RelationsFinder
{

    /** 
     * Guess relations from methods
     * @return array<string>
     */
    public function guessDynamicRelations(): array
    {
        $class = new ReflectionClass($this);

        return collect($class->getMethods())
            ->filter(fn (ReflectionMethod $method) => $this->hasRelationReturnType($method))
            ->mapWithKeys(fn (ReflectionMethod $method) => [$method->getName() => $this->guessRelationColumns($method)])
            ->toArray();
    }



    protected function hasRelationReturnType(ReflectionMethod $method) : bool
    {
        if ($method->getReturnType() instanceof ReflectionNamedType) {
            $returnType = $method->getReturnType()->getName();

            return is_a($returnType, Relation::class, true);
        }

        if ($method->getReturnType() instanceof ReflectionUnionType) {
            foreach ($method->getReturnType()->getTypes() as $type) {
                $returnType = $type->getName();

                if (is_a($returnType, Relation::class, true)) {
                    return true;
                }
            }
        }

        return false;
    }

    
    protected function guessRelationColumns(ReflectionMethod $method) 
    {
        $relation = $method->invoke($this);
        return match (true) {
            is_a($relation, \Illuminate\Database\Eloquent\Relations\MorphOne::class) =>  $relation->getLocalKeyName(),
            is_a($relation, \Illuminate\Database\Eloquent\Relations\MorphMany::class) => $relation->getLocalKeyName(),
            is_a($relation, \Illuminate\Database\Eloquent\Relations\MorphTo::class) =>     [$relation->getMorphType(), $relation->getForeignKeyName()],
            is_a($relation, \Illuminate\Database\Eloquent\Relations\MorphToMany::class) => [$relation->getMorphType(), $relation->getForeignKeyName()],
            is_a($relation, \Illuminate\Database\Eloquent\Relations\HasOne::class) => $relation->getLocalKeyName(),
            is_a($relation, \Illuminate\Database\Eloquent\Relations\HasMany::class) => $relation->getLocalKeyName(),
            is_a($relation, \Illuminate\Database\Eloquent\Relations\HasOneThrough::class) => $relation->getLocalKeyName(),
            is_a($relation, \Illuminate\Database\Eloquent\Relations\HasManyThrough::class) => $relation->getLocalKeyName(),
            is_a($relation, \Illuminate\Database\Eloquent\Relations\BelongsTo::class) => $relation->getForeignKeyName(),
            is_a($relation, \Illuminate\Database\Eloquent\Relations\BelongsToMany::class) => $relation->getParentKeyName(),
            default => null,
        };
    }
}