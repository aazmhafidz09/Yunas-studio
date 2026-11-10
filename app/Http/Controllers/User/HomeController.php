<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Product;
use App\Models\Additional;
use App\Models\Packet;

class HomeController extends Controller
{
    public function index(Request $request)
{
    $query = Packet::with('product')
        ->where('is_active', 1)
        ->latest();

    if ($request->has('product_id') && $request->product_id) {
        $query->where('product_id', $request->product_id);
    }

    $packets = $query->get()->groupBy('product.name');

    // Hapus ->where('is_active', 1) dari Product
    $products = Product::with(['packets' => function($q) {
        $q->where('is_active', 1)->orderBy('price');
    }])->latest()->get();

    $all_additionals = Additional::where('price', '>', 0)
        ->orderBy('name')
        ->get();

    return view('userPage.home', compact(
        'packets',
        'products',
        'all_additionals'
    ));
}

public function product($id)
{
    $product = Product::findOrFail($id);

    $packets = Packet::with('product')
        ->where('product_id', $product->id)
        ->where('is_active', 1)
        ->latest()
        ->get();

    // Hapus ->where('is_active', 1) dari Product
    $products = Product::with(['packets' => function($q) {
    $q->where('is_active', 1)->orderBy('price');
}])->latest()->get();

    return view('userPage.home', compact(
        'packets',
        'products',
        'product'
    ));
}

}