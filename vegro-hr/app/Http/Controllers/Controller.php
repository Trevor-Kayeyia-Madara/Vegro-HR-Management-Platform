<?php

namespace App\Http\Controllers;
use App\Helpers\ApiResponse;

abstract class Controller
{
    public function index()
    {
        return ApiResponse::success([], "Welcome to VEGRO HR BACKEND", 501);
    }

    public function dashboard()
    {
        return ApiResponse::success([], "Dashboard data not implemented yet", 501);
    }
}
