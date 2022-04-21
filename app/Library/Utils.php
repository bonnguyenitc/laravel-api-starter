<?php

namespace App\Library;

class Utils
{
    static function generateResponse($success = false, $data = null, $status = HttpStatusCode::HTTP_OK)
    {
        return response()->json([
            "success" => $success,
            "data" => $data
        ], $status);
    }
}
