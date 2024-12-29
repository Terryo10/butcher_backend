<?php

namespace App\Http\Controllers;

abstract class Controller
{
    public function jsonError($statusCode = 500, $message = "Unexpected Error"): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            "success" => false,
            "message" => $message
        ], $statusCode);
    }

    public function jsonSuccess($statusCode = 200, $message = "Request Successful", $data = [], $key): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            "success" => true,
            "message" => $message,
            $key => $data
        ], $statusCode);
    }
}
