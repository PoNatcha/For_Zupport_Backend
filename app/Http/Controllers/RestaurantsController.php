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
        $keyword = $request->query('keyword', 'ร้านอาหาร');
        $locationName = $request->query('location', 'บางซื่อ');
    
        // แปลงชื่อสถานที่เป็นพิกัดด้วย Geocoding API
        $geoResponse = Http::get('https://maps.googleapis.com/maps/api/geocode/json', [
            'address' => $locationName,
            'key' => env('GOOGLE_MAPS_API_KEY'),
        ]);
    
        if (!$geoResponse->successful()) {
            return response()->json(['error' => 'ไม่สามารถเชื่อมต่อบริการระบุตำแหน่งได้ในขณะนี้'], 500);
        }
    
        $geoData = $geoResponse->json();
    
        if ($geoData['status'] !== 'OK' || empty($geoData['results'])) {
            return response()->json(['error' => 'ไม่พบสถานที่ที่คุณระบุ กรุณาลองใหม่'], 404);
        }
    
        $lat = $geoData['results'][0]['geometry']['location']['lat'];
        $lng = $geoData['results'][0]['geometry']['location']['lng'];
    
        // สร้าง cache key
        $cacheKey = 'places_nearby_' . md5($keyword . $lat . $lng);
    
        // ดึงข้อมูลจาก Places API แบบเดิม (nearbysearch)
        $data = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($keyword, $lat, $lng) {
            $response = Http::get('https://maps.googleapis.com/maps/api/place/nearbysearch/json', [
                'location' => $lat . ',' . $lng,
                'radius' => 2000,
                'type' => 'restaurant',
                'keyword' => $keyword,
                'key' => env('GOOGLE_MAPS_API_KEY'),
            ]);
    
            return $response->json();
        });
    
        if (!isset($data['results']) || empty($data['results'])) {
            return response()->json(['error' => 'ไม่พบร้านอาหารที่ตรงกับคำค้นของคุณ'], 404);
        }
    
        // เพิ่มรูปภาพจาก photoReference ถ้ามี
        $resultsWithPhotos = array_map(function($place) {
            if (isset($place['photos'][0]['photoReference'])) {
                $photoReference = $place['photos'][0]['photoReference'];
                $photoUrl = 'https://maps.googleapis.com/maps/api/place/photo?maxwidth=400&photo_reference=' . $photoReference . '&key=' . env('GOOGLE_MAPS_API_KEY');
                $place['photoUrl'] = $photoUrl; // เพิ่ม URL รูปภาพเข้าไปในข้อมูล
            } else {
                $place['photoUrl'] = null; // หรือใส่ URL ของภาพ placeholder เช่น 'default_image.jpg'
            }
            return $place;
        }, $data['results']);
    
        // ส่งข้อมูลที่มีรูปภาพเพิ่มเข้าไป
        return response()->json($resultsWithPhotos);
    }
    
}
