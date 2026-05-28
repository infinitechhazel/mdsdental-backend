<?php

namespace App\Http\Controllers;

use App\Models\CaseStudy;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CaseStudyController extends Controller
{
    // GET ALL
    public function index(Request $request)
    {
        $query = CaseStudy::query();

        // SEARCH
        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('patient_name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // CATEGORY FILTER
        if ($request->filled('category')) {
            $query->where('case_type', $request->category);
        }

        // SEVERITY FILTER (optional but useful)
        if ($request->filled('severity')) {
            $query->where('severity', $request->severity);
        }

        $cases = $query->latest()->paginate($request->per_page ?? 10);

        return response()->json($cases);
    }

    // STORE
    public function store(Request $request)
    {
        $request->validate([
            'category' => 'required',
            'treatment' => 'required',
            'before_image' => 'required|image',
            'after_image' => 'required|image',
            'result' => 'required',
            'duration' => 'nullable',
            'rating' => 'nullable|integer',
            'testimonial' => 'nullable',
            'patient' => 'nullable',
        ]);

        $case = CaseStudy::create([
            'category' => $request->category,
            'treatment' => $request->treatment,

            'before_image' => $this->handleImageUpload($request->file('before_image')),
            'after_image'  => $this->handleImageUpload($request->file('after_image')),

            'result' => $request->result,
            'duration' => $request->duration,
            'rating' => $request->rating ?? 5,
            'testimonial' => $request->testimonial,
            'patient' => $request->patient,
        ]);

        return response()->json([
            'success' => true,
            'data' => $case
        ]);
    }

    // UPDATE
    public function update(Request $request, $id)
    {
        $case = CaseStudy::findOrFail($id);

        $case->update([
            'category' => $request->category,
            'treatment' => $request->treatment,
            'result' => $request->result,
            'duration' => $request->duration,
            'rating' => $request->rating,
            'testimonial' => $request->testimonial,
            'patient' => $request->patient,
        ]);

        if ($request->hasFile('before_image')) {
            $case->before_image = $this->handleImageUpload($request->file('before_image'));
        }

        if ($request->hasFile('after_image')) {
            $case->after_image = $this->handleImageUpload($request->file('after_image'));
        }

        $case->save();

        return response()->json([
            'success' => true,
            'data' => $case
        ]);
    }

    // DELETE
    public function destroy($id)
    {
        CaseStudy::findOrFail($id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Case deleted'
        ]);
    }

    // YOUR IMAGE UPLOAD FUNCTION
    private function handleImageUpload($file): string
    {
        $filename = time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();

        $publicPath = public_path('images/cases');

        if (!file_exists($publicPath)) {
            mkdir($publicPath, 0755, true);
        }

        $file->move($publicPath, $filename);

        return '/images/cases/' . $filename;
    }
}
