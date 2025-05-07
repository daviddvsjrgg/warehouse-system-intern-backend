<?php

namespace App\Http\Controllers\Traits;

trait ApiResponse{
    public function sendSuccessResponse($status, $message, $resource, $statusCode) {
        return response()->json(
            [
                'status_code'  => $statusCode,
                'success'      => $status,
                'message'      => $message,
                'data'         => $resource,
            ], $statusCode
        );
    }
    public function sendErrorResponse($status, $message, $resource, $statusCode) {
        return response()->json(
            [
                'status_code'  => $statusCode,
                'success'      => $status,
                'message'      => $message,
                'data'         => $resource,
            ], $statusCode
        );
    }
}



