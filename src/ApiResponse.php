<?php

declare(strict_types=1);

namespace Tamedevelopers\Support;

use Symfony\Component\HttpFoundation\JsonResponse;


class ApiResponse{

    /**
     * Echo `json_encode` with response and message
     * Common HTTP status codes for API responses:
     * - 200 OK - Request succeeded. Example: Successful login, data fetched successfully.
     * - 201 Created - Resource created successfully. Example: User registered, item stored.
     * - 400 Bad Request - Invalid request. Example: Malformed JSON, missing parameters.
     * - 401 Unauthorized - Authentication failed. Example: Wrong password, invalid token.
     * - 403 Forbidden - Not allowed. Example: User without permission attempts action.
     * - 404 Not Found - Resource missing. Example: User ID not found, endpoint invalid.
     * - 419 Page Expired - CSRF mismatch/session expired. Example: Invalid CSRF token.
     * - 422 Unprocessable Entity - Validation failed. Example: Invalid email, missing fields.
     * - 500 Internal Server Error - Server bug. Example: Database failure, fatal exception.
     *
     * @param  int      $response
     * @param  mixed    $message
     * @param  int      $statusCode
     * @return void
     */
    public static function jsonEcho(int $response = 0, $message = null, int $statusCode = 200)
    {
        // Set the HTTP status code and header
        http_response_code($statusCode);
        header('Content-Type: application/json');

        // Create the response array and encode it as JSON
        echo json_encode([
            'response' => $response,
            'message' => $message
        ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    /**
     * Return a JSON response
     * Common HTTP status codes for API responses:
     * - 200 OK - Request succeeded. Example: Successful login, data fetched successfully.
     * - 201 Created - Resource created successfully. Example: User registered, item stored.
     * - 400 Bad Request - Invalid request. Example: Malformed JSON, missing parameters.
     * - 401 Unauthorized - Authentication failed. Example: Wrong password, invalid token.
     * - 403 Forbidden - Not allowed. Example: User without permission attempts action.
     * - 404 Not Found - Resource missing. Example: User ID not found, endpoint invalid.
     * - 419 Page Expired - CSRF mismatch/session expired. Example: Invalid CSRF token.
     * - 422 Unprocessable Entity - Validation failed. Example: Invalid email, missing fields.
     * - 500 Internal Server Error - Server bug. Example: Database failure, fatal exception.
     *
     * @param  string $status
     * @param  string $message
     * @param  mixed  $data
     * @param  int    $statusCode
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public static function json($status, $message, $data = null, int $statusCode = 200)
    {
        return new JsonResponse([
            'status'  => $status,   // "success" | "error"
            'message' => $message,
            'data'    => $data,
        ], $statusCode);
    }

    /**
     * Return a success JSON response
     *
     * @param  string $message
     * @param  mixed  $data
     * @param  int    $statusCode
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public static function success($message, $data = null,  int $statusCode = 200)
    {
        return self::json("success", $message, $data, $statusCode);
    }

    /**
     * Return an error JSON response
     *
     * @param  string $message
     * @param  mixed  $data
     * @param  int    $statusCode
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public static function error($message, $data = null, int $statusCode = 400)
    {
        return self::json("error", $message, $data, $statusCode);
    }

}