<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    private $komerceKey = '4btb6JDr63c305bfb85004e3cbHT961o'; 

    public function searchLocation(Request $request)
    {
        try {
            $keyword = $request->query('q');

            // ✅ MENGGUNAKAN ENDPOINT PENCARIAN KECAMATAN YANG BENAR (/domestic-destination)
            $response = Http::withoutVerifying()->withHeaders([
                'key' => $this->komerceKey
            ])->get('https://rajaongkir.komerce.id/api/v1/destination/domestic-destination', [
                'search' => $keyword,
                'limit'  => 10,
                'offset' => 0
            ]);

            return response()->json($response->json());
        } catch (\Exception $e) {
            return response()->json(['message' => 'Backend Error: ' . $e->getMessage()], 500);
        }
    }

    public function getProvinces()
    {
        try {
            $response = Http::withoutVerifying()->withHeaders([
                'key' => $this->komerceKey
            ])->get('https://rajaongkir.komerce.id/api/v1/destination/province');

            return response()->json($response->json());
        } catch (\Exception $e) {
            return response()->json(['message' => 'Backend Error: ' . $e->getMessage()], 500);
        }
    }

    public function getCities($province_id)
    {
        try {
            $response = Http::withoutVerifying()->withHeaders([
                'key' => $this->komerceKey
            ])->get('https://rajaongkir.komerce.id/api/v1/destination/city', [
                'province_id' => $province_id
            ]);

            return response()->json($response->json());
        } catch (\Exception $e) {
            return response()->json(['message' => 'Backend Error: ' . $e->getMessage()], 500);
        }
    }

    public function getSubdistricts($city_id)
    {
        try {
            $response = Http::withoutVerifying()->withHeaders([
                'key' => $this->komerceKey
            ])->get('https://rajaongkir.komerce.id/api/v1/destination/district', [
                'city_id' => $city_id
            ]);

            return response()->json($response->json());
        } catch (\Exception $e) {
            return response()->json(['message' => 'Backend Error: ' . $e->getMessage()], 500);
        }
    }
}