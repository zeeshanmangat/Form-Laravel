<?php

namespace App\Http\Controllers;

use App\Models\UserForm;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class UserFormController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            return response()->json(UserForm::latest()->get());
        }
        return view('users.index');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:user_forms,email',
            'password' => 'required|min:6',
            'image'    => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $imagePath = $request->hasFile('image') ? $request->file('image')->store('users', 'public') : null;

        $user = UserForm::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'image'    => $imagePath,
        ]);

        return response()->json(['success' => true, 'user' => $user]);
    }

    public function show($id)
    {
        $user = UserForm::findOrFail($id);
        return response()->json($user);
    }

    public function update(Request $request, $id)
    {
        $user = UserForm::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:user_forms,email,' . $id,
            'password' => 'nullable|min:6',
            'image'    => $request->hasFile('image') ? 'image|mimes:jpeg,png,jpg,gif|max:2048' : 'nullable',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if ($request->hasFile('image')) {
            if ($user->image && Storage::disk('public')->exists($user->image)) {
                Storage::disk('public')->delete($user->image);
            }
            $user->image = $request->file('image')->store('users', 'public');
        }

        $user->name = $request->name;
        $user->email = $request->email;

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        return response()->json(['success' => true, 'user' => $user]);
    }

    public function destroy($id)
    {
        $user = UserForm::findOrFail($id);

        if ($user->image && Storage::disk('public')->exists($user->image)) {
            Storage::disk('public')->delete($user->image);
        }

        $user->delete();

        return response()->json(['success' => true]);
    }
}
