<?php

namespace App\Models\Traits;

trait OptimizedQueries
{
    /**
     * Scope for common eager loading patterns
     */
    public function scopeWithRelations($query, array $relations = [])
    {
        $defaultRelations = $this->getDefaultRelations();
        $relations = array_merge($defaultRelations, $relations);
        
        return $query->with($relations);
    }

    /**
     * Get default relations for this model
     */
    protected function getDefaultRelations(): array
    {
        return property_exists($this, 'defaultRelations') 
            ? $this->defaultRelations 
            : [];
    }
}
