<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function index()
    {
        \Log::info('ProfileController@index hit');
        $user = auth()->user();
        return view('admin.profile.index', compact('user'));
    }

    public function settings()
    {
        \Log::info('ProfileController@settings hit');
        $user = auth()->user();
        $admins = \App\Models\User::where('role', 'admin')->get();
        return view('admin.profile.settings', compact('user', 'admins'));
    }

    public function updateProfile(Request $request)
    {
        \Log::info('ProfileController@updateProfile hit', $request->except(['_token']));
        $user = auth()->user();
        
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'username' => 'nullable|string|max:255|unique:users,username,' . $user->id,
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'bio' => 'nullable|string|max:1000',
            'website' => 'nullable|url|max:255',
            'location' => 'nullable|string|max:255',
            'remove_avatar' => 'nullable|boolean',
        ]);

        $data = $request->only(['first_name', 'last_name', 'email', 'username', 'bio', 'website', 'location']);
        $data['name'] = $request->first_name . ' ' . $request->last_name;

        $data['name'] = $request->first_name . ' ' . $request->last_name;

        if ($request->remove_avatar && $user->avatar) {
            if (file_exists(public_path($user->avatar))) {
                unlink(public_path($user->avatar));
            }
            $data['avatar'] = null;
        }

        if ($request->hasFile('avatar')) {
            // Delete old avatar
            if ($user->avatar && file_exists(public_path($user->avatar))) {
                unlink(public_path($user->avatar));
            }

            $avatar = $request->file('avatar');
            $name = 'avatar_' . $user->id . '_' . time() . '.' . $avatar->getClientOriginalExtension();
            $path = 'uploads/avatars';
            $destinationPath = public_path($path);

            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0777, true);
            }

            $avatar->move($destinationPath, $name);
            $data['avatar'] = $path . '/' . $name;
        }

        $user->update($data);

        return redirect()->back()->with('success', 'Profile updated successfully.');
    }

    public function updateSettings(Request $request)
    {
        \Log::info('ProfileController@updateSettings hit', $request->except(['_token']));
        $user = auth()->user();
        
        // Merge current settings with new inputs
        $currentSettings = $user->profile_settings ?? [];
        $newSettings = $request->except(['_token']);
        
        // Handle nested notifications and security checkboxes
        // If a checkbox is not checked, it won't be in the request.
        // We need to ensure they are set to 'off' if they are missing from the request 
        // but were present in the form.
        
        if ($request->has('notifications')) {
            $notificationKeys = [
                'academic_material', 'academic_assignments', 'academic_exams',
                'system_payments', 'system_security', 'admission_new',
                'admission_payments', 'social_messages', 'social_forum'
            ];
            foreach ($notificationKeys as $key) {
                if (!isset($newSettings['notifications'][$key])) {
                    $newSettings['notifications'][$key] = 'off';
                }
            }
        }

        // Security settings
        $securityKeys = ['2fa_enabled', 'login_alerts'];
        foreach ($securityKeys as $key) {
            if (!isset($newSettings[$key])) {
                $newSettings[$key] = 'off';
            }
        }

        $user->profile_settings = array_merge($currentSettings, $newSettings);
        $user->save();

        return redirect()->back()->with('success', 'Settings updated successfully.');
    }

    public function updateSecurity(Request $request)
    {
        \Log::info('--- SECURITY UPDATE REQUEST START ---', $request->all());
        $user = auth()->user();
        
        // 1. Handle Password Update if requested
        if ($request->filled('new_password')) {
            \Log::info('--- PASSWORD CHANGE REQUESTED ---', ['email' => $user->email]);
            
            $request->validate([
                'current_password' => 'required',
                'new_password' => 'required|min:8|confirmed',
            ]);

            if (!Hash::check($request->current_password, $user->password)) {
                \Log::warning('Current password check failed for ' . $user->email);
                return redirect()->back()->with('error', 'Current password does not match.');
            }

            // DO NOT USE Hash::make here because the User model handles it via the 'hashed' cast
            $user->password = $request->new_password;

            
            \Log::info('Password attribute set for ' . $user->email);
        }

        // 2. Handle Security Settings
        $currentSettings = $user->profile_settings ?? [];
        $newSettings = $request->only(['2fa_enabled', 'session_timeout', 'login_alerts']);
        
        // Ensure checkboxes are 'off' if missing
        $checkboxes = ['2fa_enabled', 'login_alerts'];
        foreach ($checkboxes as $key) {
            $newSettings[$key] = $request->has($key) ? 'on' : 'off';
        }

        $user->profile_settings = array_merge($currentSettings, $newSettings);
        $user->save();

        // Re-authenticate if password was changed to keep session alive
        if ($request->filled('new_password')) {
            auth()->login($user->fresh());
        }

        return redirect()->back()->with('success', 'Security settings updated successfully.');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:8|confirmed',
        ]);

        $user = auth()->user();
        
        \Log::info('--- PASSWORD UPDATE START ---');
        \Log::info('User ID: ' . $user->id);
        \Log::info('User Email: ' . $user->email);
        \Log::info('Hash Before: ' . $user->password);

        if (!Hash::check($request->current_password, $user->password)) {
            \Log::warning('Password update failed: Current password mismatch for ' . $user->email);
            return redirect()->back()->with('error', 'Current password does not match.');
        }

        // Force Hash::make just to be absolutely sure, as 'hashed' cast might behave differently across versions
        $user->password = Hash::make($request->new_password);

        
        \Log::info('Hash After (Memory): ' . $user->password);
        
        $saved = $user->save();

        if ($saved) {
            \Log::info('User saved successfully. New Hash in DB: ' . $user->fresh()->password);
            \Log::info('--- PASSWORD UPDATE SUCCESS ---');
            
            auth()->login($user->fresh());
            return redirect()->back()->with('success', 'Password updated successfully.');
        }

        \Log::error('DB Save FAILED for ' . $user->email);
        return redirect()->back()->with('error', 'Failed to update password. Please try again.');
    }

    public function storeAdmin(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
        ]);

        $user = \App\Models\User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'name' => $request->first_name . ' ' . $request->last_name,
            'email' => $request->email,
            'password' => $request->password,

            'role' => 'admin',
            'institute_id' => auth()->user()->institute_id ?? 1,
        ]);

        return redirect()->back()->with('success', 'Admin created successfully.');
    }

    public function updateAdmin(Request $request, $id)
    {
        $admin = \App\Models\User::where('role', 'admin')->findOrFail($id);

        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $admin->id,
            'password' => 'nullable|string|min:8',
        ]);

        $data = [
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'name' => $request->first_name . ' ' . $request->last_name,
            'email' => $request->email,
        ];

        if ($request->filled('password')) {
            $data['password'] = $request->password;

        }

        $admin->update($data);

        return redirect()->back()->with('success', 'Admin updated successfully.');
    }

    public function deleteAdmin($id)
    {
        $admin = \App\Models\User::where('role', 'admin')->findOrFail($id);

        // Prevent self-deletion
        if ($admin->id === auth()->id()) {
            return redirect()->back()->with('error', 'You cannot delete your own account.');
        }

        $admin->delete();

        return redirect()->back()->with('success', 'Admin deleted successfully.');
    }
}
