<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\UserResource;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index()
    {
        // Return all users (paginate if many)
        $users = User::paginate(20);
        return UserResource::collection($users);

    }
}