<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Testimonial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class TestimonialController extends Controller
{
    public function index(Request $request)
    {
        // Get the status query parameter
        $status = $request->query('status');
        
        // Build query conditionally
        $query = Testimonial::query();
        
        if ($status) {
            $query->where('status', $status);
        }
        
        return $query->latest()->get();
    }
    
    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_name' => 'required|string|max:255',
            'client_email' => 'required|email|max:255',
            'rating' => 'required|integer|min:1|max:5',
            'message' => 'required|string',
            'status' => 'sometimes|in:pending,approved,rejected',
        ]);
        
        // Set default status if not provided
        if (!isset($validated['status'])) {
            $validated['status'] = 'pending';
        }
        
        $testimonial = Testimonial::create($validated);
        
        return response()->json($testimonial, 201);
    }
    
    public function show(Testimonial $testimonial)
    {
        return response()->json($testimonial);
    }
    
    public function update(Request $request, Testimonial $testimonial)
    {
        try {
            
            // Validate the request
            $validator = Validator::make($request->all(), [
                'client_name' => 'sometimes|string|max:255',
                'client_email' => 'sometimes|email|max:255',
                'rating' => 'sometimes|integer|min:1|max:5',
                'message' => 'sometimes|string',
                'status' => 'sometimes|in:pending,approved,rejected',
            ]);
            
            if ($validator->fails()) {
                Log::error('Validation failed', ['errors' => $validator->errors()]);
                return response()->json([
                    'error' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            $validated = $validator->validated();
            
            // Update the testimonial
            $testimonial->update($validated);
            
            // Refresh the model to get the latest data
            $testimonial->refresh();
            
            return response()->json($testimonial, 200);
            
        } catch (\Exception $e) {
            Log::error('Failed to update testimonial', [
                'id' => $testimonial->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => 'Failed to update testimonial',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    public function destroy(Testimonial $testimonial)
    {
        try {
            // Delete the testimonial
            $testimonial->delete();
            
            return response()->json([
                'message' => 'Testimonial deleted successfully',
                'success' => true
            ], 200);
            
        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error('Failed to delete testimonial', [
                'id' => $testimonial->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => 'Failed to delete testimonial',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}