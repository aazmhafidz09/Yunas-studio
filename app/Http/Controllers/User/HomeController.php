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
        $query = Packet::with(['product', 'additionalDefaults.additional'])
            ->where('is_active', 1)
            ->latest();

        if ($request->has('product_id') && $request->product_id) {
            $query->where('product_id', $request->product_id);
        }

        $packets = $this->withRetouchLimit($query->get())->groupBy('product.name');

        // Hapus ->where('is_active', 1) dari Product
        $products = $this->productsWithPackets();

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

        $packets = $this->withRetouchLimit(
            Packet::with(['product', 'additionalDefaults.additional'])
                ->where('product_id', $product->id)
                ->where('is_active', 1)
                ->latest()
                ->get()
        );

        // Hapus ->where('is_active', 1) dari Product
        $products = $this->productsWithPackets();

        return view('userPage.home', compact(
            'packets',
            'products',
            'product'
        ));
    }

    private function productsWithPackets()
    {
        $products = Product::with(['packets' => function ($q) {
            $q->where('is_active', 1)->orderBy('price')->with('additionalDefaults.additional');
        }])->latest()->get();

        $products->each(fn ($product) => $this->withRetouchLimit($product->packets));

        return $products;
    }

    /**
     * Expose the edit (retouch) quota — taken from the packet's "Retouch N Photo" default
     * additional on the admin product page — to the front-end packet data.
     */
    private function withRetouchLimit($packets)
    {
        return $packets->each(function (Packet $packet) {
            $packet->append('retouch_limit')->makeHidden('additionalDefaults');
        });
    }
}
