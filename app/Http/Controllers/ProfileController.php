<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'avatar' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.unique' => 'Email ini sudah terdaftar.',
            'avatar.image' => 'File harus berupa gambar.',
            'avatar.max' => 'Ukuran foto maksimal 2MB.',
        ]);

        $updateData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
        ];

        // Jika user mengunggah avatar baru
        if ($request->hasFile('avatar')) {
            // Hapus avatar lama dari Supabase jika ada
            if ($user->avatar_url) {
                // Ekstrak nama file/path relatif dari URL penuh
                $oldPath = parse_url($user->avatar_url, PHP_URL_PATH);
                $filePath = ltrim(strstr($oldPath, 'avatars/'), '/');
                if ($filePath) {
                    Storage::disk('supabase')->delete($filePath);
                }
            }

            // Simpan foto baru dengan format yang sama seperti Register
            $file = $request->file('avatar');
            $fileName = 'avatars/' . uniqid() . '.' . $file->getClientOriginalExtension();
            Storage::disk('supabase')->put($fileName, file_get_contents($file), 'public');
            
            $updateData['avatar_url'] = Storage::disk('supabase')->url($fileName);
        }

        $user->update($updateData);

        ActivityLog::record('UPDATE_PROFILE', "Memperbarui profil akun (Nama/Email/Avatar)");

        return back()->with('success', 'Profil Anda berhasil diperbarui.');
    }
}