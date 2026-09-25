<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

trait ApiResponse
{
    /**
     * Response success chuẩn
     */
    protected function success(
        mixed $data = null,
        string $message = 'Success',
        int $code = 200,
        array $meta = []
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => $data,
            'meta'    => (object) $meta,
        ], $code);
    }

    /**
     * Response error chuẩn production
     */
    protected function error(
        string $message = 'Error',
        int $code = 400,
        mixed $errors = null,
        array $meta = []
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors'  => $errors,
            'data'    => null,
            'meta'    => (object) $meta,
        ], $code);
    }

    /**
     * Response danh sách phân trang chuẩn Laravel style
     */
    protected function paginated(
        LengthAwarePaginator $paginator,
        string $message = 'Success'
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => $paginator->items(),
            'meta'    => [
                'current_page' => $paginator->currentPage(),
                'from'          => $paginator->firstItem(),
                'to'            => $paginator->lastItem(),
                'last_page'     => $paginator->lastPage(),
                'per_page'      => $paginator->perPage(),
                'total'         => $paginator->total(),
                'path'          => $paginator->path(),
            ],
            'links' => [
                'first' => $paginator->url(1),
                'last'  => $paginator->url($paginator->lastPage()),
                'prev'  => $paginator->previousPageUrl(),
                'next'  => $paginator->nextPageUrl(),
            ],
        ], 200);
    }
}