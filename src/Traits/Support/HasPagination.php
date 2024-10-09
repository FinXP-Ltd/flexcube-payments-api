<?php

namespace Finxp\Flexcube\Traits\Support;

use Illuminate\Pagination\LengthAwarePaginator;

trait HasPagination
{
    public function paginate($collection)
    {
        $page = request()->input('page') ?? 1;
        $limit = request()->input('limit') ?? 5;

        $currentItems = array_slice($collection->values()->toArray(), $limit * ($page - 1), $limit);

        $paginate = new LengthAwarePaginator(
            $currentItems,
            $collection->count(),
            $limit,
            $page
        );

        return $this->response(
            [
                $paginate
            ]
        );
    }
}