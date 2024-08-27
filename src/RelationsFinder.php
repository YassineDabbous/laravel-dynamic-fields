<?php

namespace YassineDabbous\DynamicFields;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
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
    public function guessRelations(): array
    {
        $class = new ReflectionClass($this);

        return collect($class->getMethods())
            ->filter(fn (ReflectionMethod $method) => $this->hasRelationReturnType($method))
            ->map(fn (ReflectionMethod $method) => $method->getName())
            ->values()
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
}