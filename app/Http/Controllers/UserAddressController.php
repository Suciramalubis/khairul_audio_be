<?php

namespace App\Http\Controllers;

use App\Models\UserAddress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserAddressController extends Controller
{
    public function index(Request $request)
    {
        // Mengambil alamat milik user yang sedang login, utamakan yang default di atas
        $addresses = UserAddress::where('user_id', Auth::id())
            ->orderByDesc('is_default')
            ->orderByDesc('created_at')
            ->get();
            
        return response()->json($addresses);
    }

    public function store(Request $request)
    {
        // ✅ Validasi disesuaikan dengan data yang dikirim dari React (Format Komerce)
        $data = $request->validate([
            'label'            => 'nullable|string|max:50',
            'recipient_name'   => 'required|string|max:100',
            'phone'            => 'required|string|max:20',
            'address'          => 'required|string',
            'province_id'      => 'required|string',
            'province_name'    => 'required|string',
            'city_id'          => 'required|string',
            'city_name'        => 'required|string',
            'subdistrict_id'   => 'required|string',
            'subdistrict_name' => 'required|string',
            'postal_code'      => 'nullable|string|max:10',
            'is_default'       => 'boolean',
        ]);

        $data['user_id'] = Auth::id();

        // Jika alamat baru ini dijadikan default, matikan default pada alamat lain
        if (!empty($data['is_default'])) {
            UserAddress::where('user_id', $data['user_id'])
                ->update(['is_default' => false]);
        }

        $address = UserAddress::create($data);
        return response()->json($address, 201);
    }

    public function update(Request $request, $id)
    {
        $address = UserAddress::where('user_id', Auth::id())->findOrFail($id);

        // ✅ Validasi yang sama untuk proses Edit
        $data = $request->validate([
            'label'            => 'nullable|string|max:50',
            'recipient_name'   => 'required|string|max:100',
            'phone'            => 'required|string|max:20',
            'address'          => 'required|string',
            'province_id'      => 'required|string',
            'province_name'    => 'required|string',
            'city_id'          => 'required|string',
            'city_name'        => 'required|string',
            'subdistrict_id'   => 'required|string',
            'subdistrict_name' => 'required|string',
            'postal_code'      => 'nullable|string|max:10',
            'is_default'       => 'boolean',
        ]);

        if (!empty($data['is_default'])) {
            UserAddress::where('user_id', Auth::id())
                ->where('id', '!=', $id)
                ->update(['is_default' => false]);
        }

        $address->update($data);
        return response()->json($address);
    }

    public function destroy($id)
    {
        $address = UserAddress::where('user_id', Auth::id())->findOrFail($id);
        $address->delete();
        
        return response()->json(['message' => 'Alamat berhasil dihapus']);
    }

    public function setDefault($id)
    {
        $userId = Auth::id();
        
        // Reset semua ke false dulu
        UserAddress::where('user_id', $userId)->update(['is_default' => false]);
        
        // Set alamat yang dipilih jadi true
        $address = UserAddress::where('user_id', $userId)->findOrFail($id);
        $address->update(['is_default' => true]);
        
        return response()->json(['message' => 'Alamat utama berhasil diubah', 'data' => $address]);
    }
}