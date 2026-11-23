<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * Case-insensitive search across several columns that behaves the same on
 * SQLite, MySQL and PostgreSQL. LIKE wildcards in user input are escaped.
 */
trait Searchable
{
    /**
     * @param  list<string>  $columns
     */
    public function scopeSearch(Builder $query, ?string $term, array $columns): Builder
    {
        $term = trim((string) $term);

        if ($term === '') {
            return $query;
        }

        $operator = $query->getConnection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
        $escaped = '%'.addcslashes($term, '%_\\').'%';

        return $query->where(function (Builder $q) use ($columns, $operator, $escaped) {
            foreach ($columns as $column) {
                $q->orWhere($column, $operator, $escaped);
            }
        });
    }
}
