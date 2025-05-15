<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class RestaurantsController extends Controller
{
        public function search(Request $request)
    {
        // รับค่าพารามิเตอร์จาก URL หรือใช้ค่าเริ่มต้น
        $keyword = $request->query('keyword', 'ร้านอาหาร');
        $locationName = $request->query('location', 'บางซื่อ');

        // แปลงชื่อสถานที่เป็นพิกัด
        $geoResponse = Http::get('https://maps.googleapis.com/maps/api/geocode/json', [
            'address' => $locationName,
            'key' => env('GOOGLE_MAPS_API_KEY'),
        ]);

        $geoData = $geoResponse->json();

        // ตรวจสอบว่าพบพิกัดหรือไม่
        if (!isset($geoData['results'][0])) {
            return response()->json(['message' => 'ไม่พบพิกัดของสถานที่ที่ระบุ'], 404);
        }

        $lat = $geoData['results'][0]['geometry']['location']['lat'];
        $lng = $geoData['results'][0]['geometry']['location']['lng'];

        // สร้างคีย์สำหรับเก็บข้อมูลในแคช
        $cacheKey = 'places_searchtext_' . md5($keyword . $lat . $lng);

        // ใช้ Places API v1 - searchText
        $data = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($keyword, $lat, $lng) {
            $response = Http::withHeaders([
                'X-Goog-Api-Key' => env('GOOGLE_MAPS_API_KEY'),
                'X-Goog-FieldMask' => 'places.displayName,places.formattedAddress,places.location',
            ])->post('https://places.googleapis.com/v1/places:searchText', [
                'textQuery' => $keyword,
                'locationBias' => [
                    'circle' => [
                        'center' => [
                            'latitude' => $lat,
                            'longitude' => $lng
                        ],
                        'radius' => 2000
                    ]
                ]
            ]);

            return $response->json();
        });

        // ตรวจสอบว่ามีผลลัพธ์หรือไม่
        if (empty($data['places'])) {
            return response()->json(['message' => 'ไม่พบผลลัพธ์ที่ตรงกับคำค้น'], 404);
        }

        return response()->json($data);
    }

}
