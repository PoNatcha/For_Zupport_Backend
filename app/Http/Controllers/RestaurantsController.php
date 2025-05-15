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
    // set default 
    $keyword = $request->query('keyword');
    if ($keyword === null) {
        $keyword = 'ร้านอาหาร';
    }
    $locationName = $request->query('location');
    if ($locationName === null) {
        $locationName = 'บางซื่อ';
    }
    $radius = $request->query('radius');
    if (!is_numeric($radius) || $radius <= 0) {
        $radius = 2000;
    }

    // ใช้ geo ในการหาสถานที่
    $geoResponse = Http::get('https://maps.googleapis.com/maps/api/geocode/json', [
        'address' => $locationName,
        'region' => 'th',
        'key' => env('GOOGLE_MAPS_API_KEY'),
    ]);

    if (!$geoResponse->successful()) {
        return response()->json(['error' => 'ไม่สามารถเชื่อมต่อบริการระบุตำแหน่งได้'], 500);
    }

    $geoData = $geoResponse->json();

    if ($geoData['status'] !== 'OK' || empty($geoData['results'])) {
        return response()->json(['error' => 'ไม่พบสถานที่ที่ระบุ กรุณาลองใหม่'], 404);
    }

    $location = $geoData['results'][0]['geometry']['location'];
    $cacheKey = 'places_nearby_' . md5($keyword . $location['lat'] . $location['lng'] . $radius);

    // cache 10 นาที
    $data = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($keyword, $location, $radius) {
        $response = Http::get('https://maps.googleapis.com/maps/api/place/nearbysearch/json', [
            'location' => $location['lat'] . ',' . $location['lng'],
            'radius' => $radius,
            'type' => 'restaurant',
            'keyword' => $keyword,
            'language' => 'th',
            'key' => env('GOOGLE_MAPS_API_KEY'),
        ]);
        return $response->json();
    });

    if (empty($data['results'])) {
        return response()->json(['error' => 'ไม่พบร้านอาหารที่ตรงกับคำค้น'], 404);
    }

    $results = array_map(function ($place) {
        $place['photoUrl'] = isset($place['photos'][0]['photo_reference'])
            ? 'https://maps.googleapis.com/maps/api/place/photo?maxwidth=400&photo_reference=' . $place['photos'][0]['photo_reference'] . '&key=' . env('GOOGLE_MAPS_API_KEY')
            : null;
        return $place;
    }, $data['results']);

    return response()->json($results);
}

}
