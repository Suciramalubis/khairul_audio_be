<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ShippingController extends Controller
{
    // 1. Fitur Pencarian Kecamatan Langsung (Direct Search V2)
    public function searchLocation(Request $request)
    {
        try {
            $keyword = $request->query('keyword');
            
            if (!$keyword) {
                return response()->json(['data' => []], 200);
            }

            $response = Http::withoutVerifying()
                ->withHeaders(['key' => env('RAJAONGKIR_API_KEY')])
                ->get("https://rajaongkir.komerce.id/api/v1/destination/domestic-destination?search={$keyword}&limit=10&offset=0");

            return response()->json($response->json(), 200);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal mencari lokasi: ' . $e->getMessage()], 500);
        }
    }

    // 2. Hitung Ongkir Berdasarkan Kecamatan Tujuan
    public function checkCost(Request $request)
    {
        try {
            $response = Http::withoutVerifying()
                ->asForm() 
                ->withHeaders(['key' => env('RAJAONGKIR_API_KEY')])
                ->post('https://rajaongkir.komerce.id/api/v1/calculate/domestic-cost', [
                    'origin' => '41053', 
                    'destination' => (string) $request->destination, 
                    'weight' => (int) $request->weight,
                    'courier' => strtolower($request->courier)
                ]);

            return response()->json($response->json(), 200);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal menghitung ongkos kirim: ' . $e->getMessage()], 500);
        }
    }
}