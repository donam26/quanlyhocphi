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
        
        $query = Ethnicity::query();
        
        if ($keyword) {
            $query->search($keyword);
        }
        
        $ethnicities = $query->orderBy('name')->get();
        
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
