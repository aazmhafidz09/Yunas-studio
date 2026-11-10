<?php

namespace App\Http\Controllers;

use App\Models\Additional;
use App\Models\AdditionalDefault;
use App\Models\Packet;
use Illuminate\Http\Request;

class AdditionalDefaultController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $packetId = $request->input('packet_id');
        
        if (!$packetId) {
            return redirect()->back()->with('error', 'Packet ID is required');
        }
        
        $packet = Packet::with('additionalDefaults.additional')->findOrFail($packetId);
        $additionals = Additional::all();
        
        return view('additional-defaults.index', compact('packet', 'additionals'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $packets = Packet::all();
        $additionals = Additional::all();
        
        return view('additional-defaults.create', compact('packets', 'additionals'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'packet_id' => 'required|exists:packets,id',
            'additional_id' => 'required|exists:additionals,id',
            'quantity' => 'required|integer|min:1',
            'note' => 'nullable|string',
        ]);
        
        // Check if this combination already exists
        $exists = AdditionalDefault::where('packet_id', $request->packet_id)
            ->where('additional_id', $request->additional_id)
            ->exists();
            
        if ($exists) {
            return redirect()->back()
                ->with('error', 'This additional is already added to this packet')
                ->withInput();
        }
        
        AdditionalDefault::create($request->all());
        
        return redirect()->route('additional-defaults.index', ['packet_id' => $request->packet_id])
            ->with('success', 'Default additional added successfully');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $additionalDefault = AdditionalDefault::with(['packet', 'additional'])->findOrFail($id);
        
        return view('additional-defaults.show', compact('additionalDefault'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $additionalDefault = AdditionalDefault::findOrFail($id);
        $packets = Packet::all();
        $additionals = Additional::all();
        
        return view('additional-defaults.edit', compact('additionalDefault', 'packets', 'additionals'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'packet_id' => 'required|exists:packets,id',
            'additional_id' => 'required|exists:additionals,id',
            'quantity' => 'required|integer|min:1',
            'note' => 'nullable|string',
        ]);
        
        $additionalDefault = AdditionalDefault::findOrFail($id);
        
        // Check if updating to a combination that already exists (except this one)
        $exists = AdditionalDefault::where('packet_id', $request->packet_id)
            ->where('additional_id', $request->additional_id)
            ->where('id', '!=', $id)
            ->exists();
            
        if ($exists) {
            return redirect()->back()
                ->with('error', 'This additional is already added to this packet')
                ->withInput();
        }
        
        $additionalDefault->update($request->all());
        
        return redirect()->route('additional-defaults.index', ['packet_id' => $request->packet_id])
            ->with('success', 'Default additional updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $additionalDefault = AdditionalDefault::findOrFail($id);
        $packetId = $additionalDefault->packet_id;
        
        $additionalDefault->delete();
        
        return redirect()->route('additional-defaults.index', ['packet_id' => $packetId])
            ->with('success', 'Default additional removed successfully');
    }
}
