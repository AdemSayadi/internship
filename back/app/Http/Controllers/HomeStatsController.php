<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HomeStatsController extends Controller
{
    public function index()
    {
        return response()->json([
            DB::table('users')->count();
            DB::table('reviews')->count()
            'bugs' => 95           // e.g. calculated % metric
        ]);
}
