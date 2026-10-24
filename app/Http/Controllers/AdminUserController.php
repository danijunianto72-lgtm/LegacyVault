<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class AdminUserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with(['activeSubscription.plan', 'subscription.plan'])
            ->withCount(['assets', 'beneficiaries']);

        // 1. Pencarian (Nama, Email, WhatsApp, ID)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('id', $search);
            });
        }

        // 2. Filter Role (admin / user)
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        // 3. Filter Paket berdasarkan activeSubscription
        if ($request->filled('plan_id')) {
            $freePlan = Plan::where('slug', 'free')->first();
            if ($request->plan_id === 'free' || ($freePlan && (int)$request->plan_id === $freePlan->id)) {
                $query->whereDoesntHave('activeSubscription', function ($q) use ($freePlan) {
                    if ($freePlan) {
                        $q->where('plan_id', '!=', $freePlan->id);
                    }
                });
            } else {
                $query->whereHas('activeSubscription', function ($q) use ($request) {
                    $q->where('plan_id', $request->plan_id);
                });
            }
        }

        $users = $query->latest()->paginate(10)->withQueryString();
        $plans = Plan::all();

        $freePlanId = Plan::where('slug', 'free')->value('id');
        $stats = [
            'total' => User::count(),
            'admins' => User::where('role', 'admin')->count(),
            'subscribed' => User::whereHas('activeSubscription', function ($q) use ($freePlanId) {
                if ($freePlanId) {
                    $q->where('plan_id', '!=', $freePlanId);
                }
            })->count(),
        ];

        return view('admin.users', compact('users', 'plans', 'stats'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:25'],
            'gender' => ['nullable', 'in:male,female'],
            'birth_date' => ['nullable', 'date'],
            'role' => ['required', 'in:user,admin'],
            'plan_id' => ['nullable', 'exists:plans,id'],
            'plan_expires_at' => ['nullable', 'date'],
            'password' => ['nullable', 'string', 'min:8'],
        ]);

        if ($user->id === $request->user()->id && $validated['role'] !== 'admin') {
            return back()->with('error', 'Anda tidak dapat menurunkan role Admin pada akun Anda sendiri.');
        }

        $updateData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'gender' => $validated['gender'] ?? null,
            'birth_date' => $validated['birth_date'] ?? null,
            'role' => $validated['role'],
        ];

        if (!empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $user->update($updateData);

        // Kelola Langganan (Subscription) User
        $selectedPlan = !empty($validated['plan_id']) ? Plan::find($validated['plan_id']) : null;

        if (!$selectedPlan || $selectedPlan->slug === 'free') {
            // Jika disetel ke Free, nonaktifkan langganan aktif yang ada
            $user->subscriptions()->where('status', 'active')->update(['status' => 'cancelled']);
        } else {
            // Update langganan aktif saat ini atau buat baru
            $activeSub = $user->activeSubscription;
            $endsAt = !empty($validated['plan_expires_at']) ? $validated['plan_expires_at'] : null;

            if ($activeSub) {
                $activeSub->update([
                    'plan_id' => $selectedPlan->id,
                    'ends_at' => $endsAt,
                    'status' => 'active',
                ]);
            } else {
                Subscription::create([
                    'user_id' => $user->id,
                    'plan_id' => $selectedPlan->id,
                    'starts_at' => now(),
                    'ends_at' => $endsAt,
                    'status' => 'active',
                ]);
            }
        }

        ActivityLog::record(
            'ADMIN_UPDATE_USER',
            "Admin memperbarui data pengguna #{$user->id} ({$user->email}) - Role: {$user->role}"
        );

        return back()->with('success', "Data pengguna {$user->name} berhasil diperbarui.");
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri yang sedang aktif!');
        }

        if ($user->avatar_url) {
            $oldPath = parse_url($user->avatar_url, PHP_URL_PATH);
            $filePath = ltrim(strstr($oldPath, 'avatars/'), '/');
            if ($filePath) {
                Storage::disk('supabase')->delete($filePath);
            }
        }

        $userName = $user->name;
        $userEmail = $user->email;
        $user->delete();

        ActivityLog::record(
            'ADMIN_DELETE_USER',
            "Admin menghapus permanen akun pengguna: {$userName} ({$userEmail})"
        );

        return back()->with('success', "Akun pengguna {$userName} beserta seluruh datanya telah dihapus.");
    }
}