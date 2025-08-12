<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Province;
use Illuminate\Http\Request;

class ProvinceController extends Controller
{
    /**
     * Lấy danh sách tất cả tỉnh thành
     */
    public function index(Request $request)
    {
        $query = Province::query();

        // Tìm kiếm theo từ khóa
        if ($request->has('keyword') && !empty($request->keyword)) {
            $keyword = $request->keyword;
            $query->where('name', 'like', "%{$keyword}%")
                  ->orWhere('code', 'like', "%{$keyword}%");
        }

        // Hỗ trợ tìm kiếm cho Select2 AJAX
        if ($request->has('q') && !empty($request->q)) {
            $q = $request->q;
            $query->where('name', 'like', "%{$q}%")
                  ->orWhere('code', 'like', "%{$q}%");
        }

        $provinces = $query->orderBy('name')->get();

        // Format cho Select2 nếu có tham số 'q' (AJAX request)
        if ($request->has('q')) {
            $results = $provinces->map(function($province) {
                return [
                    'id' => $province->id,
                    'text' => $province->name
                ];
            });
            return response()->json($results);
        }

        return response()->json([
            'success' => true,
            'data' => $provinces
        ]);
    }
    
    /**
     * Lấy danh sách tỉnh thành theo miền
     */
    public function getByRegion($region)
    {
        // Kiểm tra region hợp lệ
        if (!in_array($region, ['north', 'central', 'south'])) {
            return response()->json([
                'success' => false,
                'message' => 'Vùng miền không hợp lệ. Chỉ chấp nhận: north, central, south'
            ], 400);
        }
        
        $provinces = Province::where('region', $region)
            ->orderBy('name')
            ->get();
            
        return response()->json([
            'success' => true,
            'data' => $provinces,
            'region_name' => match($region) {
                'north' => 'Miền Bắc',
                'central' => 'Miền Trung',
                'south' => 'Miền Nam',
                default => 'Không xác định'
            }
        ]);
    }
    
    /**
     * Tìm kiếm tỉnh thành theo từ khóa
     */
    public function search(Request $request)
    {
        $keyword = $request->get('keyword', '');
        
        if (empty($keyword)) {
            return $this->index($request);
        }
        
        $provinces = Province::where('name', 'like', "%{$keyword}%")
            ->orWhere('code', 'like', "%{$keyword}%")
            ->orderBy('name')
            ->get();
            
        return response()->json([
            'success' => true,
            'data' => $provinces
        ]);
    }
    
    /**
     * Lấy thông tin chi tiết tỉnh thành
     */
    public function show($id)
    {
        $province = Province::find($id);
        
        if (!$province) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy tỉnh thành với ID: ' . $id
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => $province
        ]);
    }
}
