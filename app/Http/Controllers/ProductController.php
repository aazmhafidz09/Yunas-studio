<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $products = Product::latest()->get();
        return view('products.index', compact('products'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('products.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:15360',
        ]);

        $data = $request->except('image');
        
        if ($request->hasFile('image')) {
            $imageName = time() . '.' . $request->image->extension();
            $request->image->storeAs('public/products', $imageName);
            $data['image'] = 'products/' . $imageName;
        }

        $product = Product::create($data);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Product created successfully :)',
                'data' => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'description' => $product->description,
                    'image' => $product->image,
                    'image_url' => $product->image ? Storage::url($product->image) : null,
                    'packets_count' => 0
                ]
            ]);
        }

        return redirect()->route('products.index')
                         ->with('success', 'Product created successfully :)');
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product)
    {
        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'description' => $product->description,
                    'image' => $product->image,
                    'image_url' => $product->image ? Storage::url($product->image) : null,
                    'packets_count' => $product->packets()->count()
                ]
            ]);
        }
        return view('products.show', compact('product'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Product $product)
    {
        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'description' => $product->description,
                    'image' => $product->image,
                    'image_url' => $product->image ? Storage::url($product->image) : null,
                    'packets_count' => $product->packets()->count()
                ]
            ]);
        }
        return view('products.edit', compact('product'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Product $product)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:15360',
        ]);

        $data = $request->except('image');
        
        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($product->image) {
                Storage::delete('public/' . $product->image);
            }
            
            $imageName = time() . '.' . $request->image->extension();
            $request->image->storeAs('public/products', $imageName);
            $data['image'] = 'products/' . $imageName;
        }

        $product->update($data);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Product updated successfully :)',
                'data' => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'description' => $product->description,
                    'image' => $product->image,
                    'image_url' => $product->image ? Storage::url($product->image) : null,
                    'packets_count' => $product->packets()->count()
                ]
            ]);
        }

        return redirect()->route('products.index')
                         ->with('success', 'Product updated successfully :)');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        // Check if product has packets
        if ($product->packets()->count() > 0) {
            if (request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete product with packets :)'
                ], 422);
            }
            return redirect()->route('products.index')
                             ->with('error', 'Cannot delete product with packets :)');
        }

        // Delete image if exists
        if ($product->image) {
            Storage::delete('public/' . $product->image);
        }
        
        $product->delete();

        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Product deleted successfully :('
            ]);
        }

        return redirect()->route('products.index')
                         ->with('success', 'Product deleted successfully :(');
    }
    
    /**
     * Toggle the status of the product.
     */
    public function toggleStatus(Product $product)
    {
        $product->update([
            'is_active' => !$product->is_active
        ]);
        
        $status = $product->is_active ? 'activated' : 'deactivated';
        
        return redirect()->back()
                         ->with('success');
    }
}
