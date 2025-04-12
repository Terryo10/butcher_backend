<?php

namespace App\Http\Controllers;

use App\Models\EcocashKey;
use Paynow\Payments\Paynow;

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

    public function paynow()
    {
        $ecocashKey = EcocashKey::where('is_active', true)->first();

        if (!$ecocashKey) {
            throw new \Exception('No active Paynow integration credentials found.');
        }

        return new Paynow(
            $ecocashKey->integration_id,
            $ecocashKey->integration_key,
            $ecocashKey->return_url,
            $ecocashKey->result_url
        );
    }
}
