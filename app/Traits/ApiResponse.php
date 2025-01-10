<?php

// app/Traits/ApiResponse.php

namespace App\Traits;

trait ApiResponse
{
    /**
     *
     * @param mixed $data
     * @param string $message
     * @param int $code
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendResponse($data, $message, $code = 200)
    {
        return response()->json([
            'code' => $code,
            'message' => $message,
            'data' => $data
        ], $code);
    }

    /**
     *
     * @param string $message
     * @param int $code
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendError($message, $code = 500)
    {
        return response()->json([
            'code' => $code,
            'message' => $message
        ], $code);
    }

    /**
     *
     * @param mixed $errors
     * @param int $code
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendErrorWithValidation($errors, $code = 422)
    {
        return response()->json([
            'code' => $code,
            'message' => 'Validation failed',
            'errors' => $errors
        ], $code);
    }

    /**
     *
     * @param mixed $requestId
     * @param string $message
     * @param int $code
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendErrorWithRequestId($requestId, $message, $code = 500)
    {
        return response()->json([
            'code' => $code,
            'message' => $message,
            'request_id' => $requestId
        ], $code);
    }
}
