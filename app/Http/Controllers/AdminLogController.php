<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class AdminLogController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');

        $query = ActivityLog::with('user')->latest();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('action', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($qu) use ($search) {
                      $qu->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        $activityLogs = $query->paginate(10)->withQueryString();

        return view('admin.logs', compact('activityLogs', 'search'));
    }
}