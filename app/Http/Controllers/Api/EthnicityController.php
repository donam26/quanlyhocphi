<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ethnicity;
use Illuminate\Http\Request;

class EthnicityController extends Controller
{
    /**
     * Lấy danh sách dân tộc cho select2
     */
    public function index(Request $request)
    {
        $keyword = $request->get('keyword', '');
        $q = $request->get('q', ''); // Hỗ trợ Select2 AJAX

        $query = Ethnicity::query();

        if ($keyword) {
            $query->search($keyword);
        }

        // Hỗ trợ tìm kiếm cho Select2 AJAX
        if ($q) {
            $query->where('name', 'like', "%{$q}%");
        }

        $ethnicities = $query->orderBy('name')->get();

        // Format cho Select2 nếu có tham số 'q' (AJAX request)
        if ($request->has('q')) {
            $results = $ethnicities->map(function($ethnicity) {
                return [
                    'id' => $ethnicity->id,
                    'text' => $ethnicity->name
                ];
            });
            return response()->json($results);
        }

        return response()->json([
            'success' => true,
            'data' => $ethnicities
        ]);
    }
    
    /**
     * Lấy chi tiết một dân tộc
     */
    public function show($id)
    {
        $ethnicity = Ethnicity::find($id);
        
        if (!$ethnicity) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy dân tộc'
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => $ethnicity
        ]);
    }
}
