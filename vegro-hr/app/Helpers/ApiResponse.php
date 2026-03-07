<?php

namespace App\Helpers;

class ApiResponse
{
    public static function success($data = null, $message = "Operation successful", $code = 200)
    {
        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => $data
        ], $code);
    }
    public static function error($message = "Operation failed", $code = 400, $data = null)
    {
        return response()->json([
            'status' => 'error',
            'message' => $message,
            'data' => $data
        ], $code);
    }

    public static function notFound($message = "Resource not found")
    {
        return response()->json([
            'status' => 'error',
            'message' => $message
        ], 404);
    }
    public static function validationError($errors, $message = "Validation failed")
    {
        return response()->json([
            'status' => 'error',
            'message' => $message,
            'errors' => $errors
        ], 422);
    }

    public static function unauthorized($message = "Unauthorized")
    {
        return response()->json([
            'status' => 'error',
            'message' => $message
        ], 401);
    }

    public static function forbidden($message = "Forbidden")
    {
        return response()->json([
            'status' => 'error',
            'message' => $message
        ], 403);
    }

    public static function serverError($message = "Internal Server Error")
    {
        return response()->json([
            'status' => 'error',
            'message' => $message
        ], 500);
    }

    public static function created($data = null, $message = "Resource created successfully")
    {
        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => $data
        ], 201);
    }

    public static function noContent($message = "No content")
    {
        return response()->json([
            'status' => 'success',
            'message' => $message
        ], 204);
    }
}