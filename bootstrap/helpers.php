<?php

use Carbon\Carbon;
use Illuminate\Support\Str;

if (!function_exists('resJson')) {
    function resJson($data, $message = null, $succeeded = true, $statusCode = 200, $headers = [], $options = 0): Illuminate\Http\JsonResponse
    {
        return response()->json([
            "message" => $message,
            "data" => $data,
            "succeeded" => $succeeded,
        ], $statusCode, $headers, $options);
    }
}

if (!function_exists('apiRoute')) {
    function apiRoute($url): string
    {
        return env('API_ENDPOINT') . $url;
    }
}

if (!function_exists('find_in')) {
    function find_in(array $arr, $cb)
    {
        for ($i = 0; $i < count($arr); $i++) {
            $check = $cb($arr[$i], $i);
            if ($check) return $arr[$i];
        }
        return false;
    }
}

if (!function_exists('resServerError')) {
    function resServerError($message = 'Something went wrong.', $data = [], $statusCode = 500): Illuminate\Http\JsonResponse
    {
        return response()->json([
            "message" => $message,
            "data" => $data,
            "succeeded" => false,
        ], $statusCode, [], 0);
    }
}

if (!function_exists('toFixed')) {
    function toFixed($number, $decimals)
    {
        return number_format($number, $decimals, '.', "");
    }
}

if (!function_exists('now')) {
	function now()
	{
		return Carbon::now();
	}
}

if (!function_exists('getNameFromURL')) {
	function getNameFromURL(string $url)
	{
		$ext = pathinfo($url, PATHINFO_EXTENSION);
		return now()->timestamp . '_' . Str::random(16) . '.' . $ext;
	}
}
