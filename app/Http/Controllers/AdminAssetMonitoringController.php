<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Asset;
use Illuminate\Http\Request;

class AdminAssetMonitoringController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', 'all');
        $search = $request->query('search');

        $query = Asset::with('user')->latest();

        if ($search) {
            $query->where('title', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%");
                  });
        }

        if ($status === 'pending') {
            $query->where('extension_status', 'pending');
        } elseif ($status === 'expired') {
            $query->where('is_lifetime', false)
                  ->whereDate('expires_at', '<', now()->startOfDay());
        } elseif ($status === 'expiring_soon') {
            $query->where('is_lifetime', false)
                  ->whereDate('expires_at', '>=', now()->startOfDay())
                  ->whereDate('expires_at', '<=', now()->addDays(7)->endOfDay());
        } elseif ($status === 'active') {
            $query->where(function ($q) {
                $q->where('is_lifetime', true)
                  ->orWhereDate('expires_at', '>', now()->addDays(7)->endOfDay());
            });
        }

        $assets = $query->paginate(10)->withQueryString();

        // Counter statistik
        $pendingCount = Asset::where('extension_status', 'pending')->count();
        $expiredCount = Asset::where('is_lifetime', false)->whereDate('expires_at', '<', now()->startOfDay())->count();
        $expiringSoonCount = Asset::where('is_lifetime', false)->whereDate('expires_at', '>=', now()->startOfDay())->whereDate('expires_at', '<=', now()->addDays(7)->endOfDay())->count();

        return view('admin.assets', compact('assets', 'pendingCount', 'expiredCount', 'expiringSoonCount', 'status', 'search'));
    }

    public function approveExtension(Asset $asset)
    {
        $asset->update(['extension_status' => 'approved']);
        ActivityLog::record('APPROVE_EXTENSION', "Admin menyetujui pembukaan perpanjangan aset ID: #{$asset->id} milik {$asset->user->email}");

        return back()->with('success', 'Permohonan disetujui. Pemilik aset sekarang dapat mengedit tanggal masa berlaku.');
    }

    public function rejectExtension(Asset $asset)
    {
        $asset->update(['extension_status' => 'none']);
        ActivityLog::record('REJECT_EXTENSION', "Admin menolak permohonan perpanjangan aset ID: #{$asset->id}");

        return back()->with('success', 'Permohonan perpanjangan ditolak.');
    }
}