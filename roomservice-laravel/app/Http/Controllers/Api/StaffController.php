<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\StaffAccount;
use Illuminate\Support\Facades\Hash;

class StaffController extends Controller
{
    public function index()
    {
        return response()->json(StaffAccount::orderBy('role')->orderBy('employee_id')->get());
    }

    public function waiters()
    {
        return response()->json(StaffAccount::where('role', 'WAITER')->where('is_active', 1)->get());
    }

    public function store(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|unique:staff_accounts',
            'username' => 'required|unique:staff_accounts',
            'full_name' => 'required',
            'role' => 'required',
            'password' => 'required|min:6'
        ]);

        $staff = StaffAccount::create([
            'employee_id' => $request->employee_id,
            'username' => $request->username,
            'full_name' => $request->full_name,
            'role' => $request->role,
            'password' => Hash::make($request->password),
            'is_active' => true
        ]);

        return response()->json(['success' => true, 'id' => $staff->id]);
    }

    public function update(Request $request, $id)
    {
        $staff = StaffAccount::findOrFail($id);
        
        $data = $request->all();
        if ($request->password) {
            $data['password'] = Hash::make($request->password);
        } else {
            unset($data['password']);
        }

        $staff->update($data);
        return response()->json(['success' => true]);
    }

    public function destroy($id)
    {
        if ($id == auth()->id()) {
            return response()->json(['success' => false, 'message' => 'Cannot delete your own account.']);
        }
        StaffAccount::findOrFail($id)->delete();
        return response()->json(['success' => true]);
    }
}