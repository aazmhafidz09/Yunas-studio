<?php

namespace App\Http\Controllers\User;
use App\Http\Controllers\Controller;
use App\Models\Packet;
use App\Models\Product;
use App\Models\PrintSize; // This is the line that fixes the error
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PacketController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Packet::with('product')->latest();
        
        if ($request->has('product_id') && $request->product_id) {
            $query->where('product_id', $request->product_id);
        }
        
        $packets = $query->get();
        $products = Product::all();
        
        return view('userPage.components.desktop.package-section', compact('packets', 'products'));
    }

    /**
     * Display packets by product.
     */
    public function product($id)
    {
        $product = Product::findOrFail($id);
        $packets = Packet::where('product_id', $product->id)->latest()->get();
        $products = Product::all();
        
        return view('userPage.components.desktop.package-section', compact('packets', 'products', 'product'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $products = Product::all();
        return view('packets.create', compact('products'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric',
            'max_photos_for_edit' => 'required|integer|min:0',
            'product_id' => 'required|exists:products,id',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:15360',
        ]);

        $data = $request->except('image');
        
        if ($request->hasFile('image')) {
            $imageName = time() . '.' . $request->image->extension();
            $request->image->storeAs('public/packets', $imageName);
            $data['image'] = 'packets/' . $imageName;
        }

        Packet::create($data);

        return redirect()->route('packets.index')
                         ->with('success', 'Packet created successfully :)');
    }

    /**
     * Display the specified resource.
     */
    public function show(Packet $packet)
    {
        $packet->load(['product', 'additionalDefaults.additional']);
        return view('packets.show', compact('packet'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Packet $packet)
    {
        $products = Product::all();
        $printSizes = PrintSize::orderBy('name')->get(); // Get all available print sizes
        return view('packets.edit', compact('packet', 'products', 'printSizes'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Packet $packet)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric',
            'max_photos_for_edit' => 'required|integer|min:0',
            'product_id' => 'required|exists:products,id',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:15360',
        ]);

        $data = $request->except('image');
        
        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($packet->image) {
                Storage::delete('public/' . $packet->image);
            }
            
            $imageName = time() . '.' . $request->image->extension();
            $request->image->storeAs('public/packets', $imageName);
            $data['image'] = 'packets/' . $imageName;
        }

        $packet->update($data);

        return redirect()->route('packets.index')
                         ->with('success', 'Packet updated successfully :)');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Packet $packet)
    {
        // Delete image if exists
        if ($packet->image) {
            Storage::delete('public/' . $packet->image);
        }
        
        $packet->delete();

        return redirect()->route('packets.index')
                         ->with('success', 'Packet deleted successfully :(');
    }
    
    /**
     * Toggle the status of the packet.
     */
    public function toggleStatus(Packet $packet)
    {
        $packet->update([
            'is_active' => !$packet->is_active
        ]);
        
        $status = $packet->is_active ? 'activated' : 'deactivated';
        
        return redirect()->back()
                         ->with('success');
    }

    /**
     * Add a print option to a packet.
     */
    public function addPrintOption(Request $request, Packet $packet)
    {
        $request->validate([
            'print_size_id' => 'required|exists:print_sizes,id',
            'quantity' => 'required|integer|min:1',
        ]);

        // Attach the print size with the specified quantity
        $packet->printOptions()->attach($request->print_size_id, ['quantity' => $request->quantity]);

        return redirect()->back()->with('success', 'Print option added successfully.');
    }

    /**
     * Remove a print option from a packet.
     */
    public function removePrintOption(Packet $packet, PrintSize $printSize)
    {
        $packet->printOptions()->detach($printSize->id);

        return redirect()->back()->with('success', 'Print option removed successfully.');
    }
}