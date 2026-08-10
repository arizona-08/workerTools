<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AuthController extends Controller
{
   function login(Request $request){
    $name = $request->input("name");

    return response()->json([
        "message" => "login successful",
        "name" => $name
    ]);
   }

   function register(Request $request){
    $queries = $request->query();

    return response()->json([
        "message" => "Registered successfully",
        "queries" => $queries
    ]);
   }
}
