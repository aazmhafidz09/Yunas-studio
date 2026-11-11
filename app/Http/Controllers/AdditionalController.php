<?php

namespace App\Http\Controllers;

use App\Models\Additional;
use App\Models\User;
use Illuminate\Http\Request;

class AdditionalController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $search = request('search');
        $additionals = Additional::query()
            ->when($search, function($query) use ($search) {
                $query->where('name', 'like', '%'.$search.'%');
            })
            ->orderBy('name') // optional sorting
            ->paginate(10);
//        return $additionals;
        return view('admin.additionals.index', compact('additionals'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.additionals.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'=>'required',
            'price'=>'required',
        ]);

        $additional = Additional::create($validated);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Additional added successfully.',
                'data' => $additional
            ]);
        }

        return redirect()->route('additionals.index')->with('success', 'Additional added successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Additional $additional)
    {
        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'data' => $additional
            ]);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Additional $additional)
    {
        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'data' => $additional
            ]);
        }

        return view('admin.additionals.edit', compact('additional'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Additional $additional)
    {
        $validated = $request->validate([
            'name'=>'required',
            'price'=>'required',
        ]);
        $additional->update($validated);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Additional updated successfully.',
                'data' => $additional
            ]);
        }

        return redirect()->route('additionals.index')->with('success', 'Additional updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Additional $additional)
    {
        $additional->delete();

        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Additional deleted successfully.'
            ]);
        }

        return redirect()->route('additionals.index')->with('success', 'Additional deleted successfully.');
    }
}
