<?php

namespace App\Http\Controllers;

use App\Models\Additional;
use App\Models\Packet;
use App\Models\Product;
use App\Models\SelectedPhoto;
use App\Models\SelectedPrint;
use App\Models\Transaksi;
use App\Models\User;
use App\Models\Role;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use ZipArchive;

class TransaksiController extends Controller
{

    /**
     * Helper function to get the consistent folder name for a transaction.
     */
    private function getTransactionFolderName(Transaksi $transaksi): string
    {
        return 'photos/' . $transaksi->customer_name . "_" . str_replace('/', '_', $transaksi->receipt_code);
    }

    /**
     * Display a listing of the resource.
     */

    public function index(Request $request)
    {
        // 1. Setup pagination options once at the top
        $perPageOptions = [5, 10, 20, 50, 100];
        $perPage = $request->input('per_page', 10);
        if (!in_array($perPage, $perPageOptions)) {
            $perPage = 10; // Default to 10 if an invalid value is provided
        }

        // 2. Build the base query with all necessary relationships
        $query = Transaksi::with(['packet.product', 'packet.printOptions', 'user', 'additionals']);
        $user = auth()->user();

        // 3. Apply role-specific scope
        if ($user->isUser()) {
            $query->where('phone_number', $user->username);
        }

        // 4. Apply all common filters to the query
        $search = $request->input('search');
        $query->when($search, function ($q) use ($search) {
            $q->where(function($sub) use ($search) {
                $sub->where('receipt_code', 'like', "%$search%")
                    ->orWhere('customer_name', 'like', "%$search%");
            });
        })
        ->when($request->filled('payment_status'), function ($q) use ($request) {
            $q->where('status', $request->payment_status);
        })
        ->when($request->filled('process_status'), function ($q) use ($request) {
            $q->where('process_status', $request->process_status);
        })
        ->when($request->filled('packet_id'), function ($q) use ($request) {
            $q->where('packet_id', $request->packet_id);
        })
        ->when($request->filled('start_date'), function ($q) use ($request) {
            $q->whereDate('created_at', '>=', $request->start_date);
        })
        ->when($request->filled('end_date'), function ($q) use ($request) {
            $q->whereDate('created_at', '<=', $request->end_date);
        });

        // 5. Clone the query *before* sorting and pagination for accurate financial totals
        $financialQuery = clone $query;

        // 6. Apply sorting
        $sortBy = $request->input('sort_by', 'created_at');
        $sortDirection = $request->input('sort_direction', 'desc');
        if (in_array($sortBy, ['total_price', 'created_at'])) {
            $query->orderBy($sortBy, $sortDirection);
        } else {
            $query->orderBy('created_at', 'desc'); // Default sort
        }

        // 7. Paginate the final query
        $transactions = $query->paginate($perPage)->withQueryString();

        // 8. Prepare data and return the correct view based on role
        $paymentStatuses = ['belum dibayar', 'dp', 'sudah dibayar'];
        $processStatuses = ['Belum Foto', 'Pilih Foto', 'Siap Edit', 'Proses Edit', 'Selesai Editing', 'Siap Cetak', 'Proses Cetak', 'Selesai'];

        if ($user->isUser()) {
            // Data for user filters
            $userPacketIds = (clone $financialQuery)->distinct()->pluck('packet_id');
            $packetsForFilter = Packet::with('product')->whereIn('id', $userPacketIds)->orderBy('name')->get()->groupBy('product.name');
            
            return view('user.transaction.transaksi', compact(
                'transactions', 'packetsForFilter', 'paymentStatuses', 'processStatuses', 'perPageOptions'
            ));

        } else { // Admin view
            // Perform financial calculations AND counts on the filtered query
            $totalBelumDibayar = (clone $financialQuery)->where('status', 'belum dibayar')->sum('total_price');
            $countBelumDibayar = (clone $financialQuery)->where('status', 'belum dibayar')->count();
            
            $totalDpPaid = (clone $financialQuery)->where('status', 'dp')->sum('dp_amount');
            $countDp = (clone $financialQuery)->where('status', 'dp')->count();

            $totalSudahDibayar = (clone $financialQuery)->where('status', 'sudah dibayar')->sum('total_price');
            $countSudahDibayar = (clone $financialQuery)->where('status', 'sudah dibayar')->count();

            $totalProfit = $totalDpPaid + $totalSudahDibayar;
            
            // Calculate the overall, unfiltered profit
            $totalOverallProfit = Transaksi::sum('total_price');

            // NEW: Get a list of all transaction directories that have a non-empty RAW folder
            $allPhotoDirs = collect(Storage::disk('public')->directories('photos'))
                ->mapWithKeys(function ($dir) {
                    // Extract the folder name part (e.g., 'Verindra_INV_20250829_18')
                    $folderName = basename($dir);
                    // Check if the RAW subdirectory within this folder is not empty
                    if (!empty(Storage::disk('public')->files($dir . '/RAW'))) {
                        return [$folderName => true];
                    }
                    return [$folderName => false];
                })->filter();

            // Data for filters
            $packetsForFilter = Packet::with('product')->whereHas('product')->orderBy('name')->get()->groupBy('product.name');
            $paymentStatuses = ['belum dibayar', 'dp', 'sudah dibayar'];
            $processStatuses = ['Belum Foto', 'Pilih Foto', 'Siap Edit', 'Proses Edit', 'Selesai Editing', 'Siap Cetak', 'Proses Cetak', 'Selesai'];

            return view('admin.transaction.transaksi', compact(
                'transactions', 'perPageOptions',
                'totalBelumDibayar', 'countBelumDibayar',
                'totalDpPaid', 'countDp',
                'totalSudahDibayar', 'countSudahDibayar',
                'totalProfit', 'totalOverallProfit',
                'packetsForFilter', 'paymentStatuses', 'processStatuses',
                'allPhotoDirs'
            ));
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $packets = Packet::with('product')->where('is_active', 1)->get()->groupBy('product.name');
        $all_additionals = Additional::where('price', '>', 0)->orderBy('name')->get();
        return view('admin.transaction.create', compact('packets', 'all_additionals'));
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'customer_name'  => ['required', 'string', 'max:50'],
            'phone_number'   => ['required', 'string', 'max:20'],
            'status'         => ['required', 'in:belum dibayar,dp,sudah dibayar'],
            'packet_id'      => ['required', 'exists:packets,id'],
            'additionals'    => ['nullable', 'array'],
            'additionals.*.quantity' => ['required', 'integer', 'min:1'],
            'additionals.*.price'    => ['required', 'numeric', 'min:0'],
            'discount'       => ['nullable', 'numeric', 'min:0'],
            'dp_amount'      => ['nullable', 'numeric', 'min:0', 'required_if:status,dp'],
            'note'           => ['nullable', 'string'],
        ]);

        DB::beginTransaction();
        try {
            $user = User::firstOrCreate(
                ['username' => $validatedData['phone_number']],
                [
                    'name' => $validatedData['customer_name'],
                    'password' => Hash::make($validatedData['phone_number']),
                    'role_id' => Role::where('name', 'User')->firstOrFail()->id,
                ]
            );

            $packet = Packet::findOrFail($validatedData['packet_id']);
            $subtotal = $packet->price;
            $discount = $validatedData['discount'] ?? 0;

            if (!empty($validatedData['additionals'])) {
                foreach ($validatedData['additionals'] as $details) {
                    $subtotal += $details['quantity'] * $details['price'];
                }
            }

            $totalPrice = $subtotal - $discount;

            $transaksi = Transaksi::create([
                'user_id'         => $user->id,
                'customer_name'   => $validatedData['customer_name'],
                'phone_number'    => $validatedData['phone_number'],
                'status'          => $validatedData['status'],
                'packet_id'       => $validatedData['packet_id'],
                'receipt_code'    => 'TEMP-' . uniqid(),
                'total_price'     => max(0, $totalPrice),
                'dp_amount'       => $validatedData['status'] === 'dp' ? $validatedData['dp_amount'] : null,
                'discount'        => $discount,
                'note'            => $validatedData['note'],
            ]);

            if (!empty($validatedData['additionals'])) {
                $syncData = [];
                foreach ($validatedData['additionals'] as $id => $details) {
                    $syncData[$id] = ['quantity' => $details['quantity'], 'price' => $details['price']];
                }
                $transaksi->additionals()->sync($syncData);
            }

            $transaksi->receipt_code = "INV/" . Carbon::now()->format('Ymd') . "/" . $transaksi->transaction_id;
            $transaksi->save();

            DB::commit();

            // UPDATED: Folder Creation Logic for local 'public' disk
            try {
                $folderName = $this->getTransactionFolderName($transaksi);
                $subfolders = ['RAW', 'Pilih Edit', 'Result', 'Pilih Cetak'];

                foreach ($subfolders as $subfolder) {
                    Storage::disk('public')->makeDirectory($folderName . '/' . $subfolder);
                }
            } catch (\Exception $e) {
                Log::error('Failed to create local photo directories for transaction ' . $transaksi->receipt_code . ': ' . $e->getMessage());
            }

            return redirect()->route('transaksi.index')->with('success', 'Transaction created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Transaction store error: ' . $e->getMessage());
            return redirect()->back()->withInput()->with('error', 'Failed to create transaction.');
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    // In app/Http/Controllers/TransaksiController.php

    public function edit(string $id)
    {
        $transaksi = Transaksi::with(['packet', 'additionals'])->findOrFail($id);
        
        // This now matches the 'create' method
        $packets = Packet::with('product')->where('is_active', 1)->get()->groupBy('product.name');
        
        $all_additionals = Additional::where('price', '>', 0)->orderBy('name')->get();
        $canPrint = $transaksi->hasPrintableItems();

        return view('admin.transaction.edit', compact('transaksi', 'packets', 'all_additionals', 'canPrint'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $transaksi = Transaksi::with('user')->findOrFail($id);

        $validatedData = $request->validate([
            'customer_name'  => ['required', 'string', 'max:50'],
            'phone_number'   => ['required', 'string', 'max:20', Rule::unique('users', 'username')->ignore($transaksi->user_id)],
            'status'         => ['required', 'in:belum dibayar,dp,sudah dibayar'],
            'process_status' => ['required', Rule::in(['Belum Foto', 'Pilih Foto', 'Siap Edit', 'Proses Edit', 'Selesai Editing', 'Siap Cetak', 'Proses Cetak', 'Selesai'])],
            'packet_id'      => ['required', 'exists:packets,id'],
            'additionals'    => ['nullable', 'array'],
            'additionals.*.quantity' => ['required', 'integer', 'min:1'],
            'additionals.*.price'    => ['required', 'numeric', 'min:0'],
            'discount'       => ['nullable', 'numeric', 'min:0'],
            'dp_amount'      => ['nullable', 'numeric', 'min:0', 'required_if:status,dp'],
            'note'           => ['nullable', 'string'],
        ]);

        DB::beginTransaction();
        try {
            if ($transaksi->user) {
                $transaksi->user->update([
                    'name' => $validatedData['customer_name'],
                    'username' => $validatedData['phone_number'],
                ]);
            }

            $packet = Packet::findOrFail($validatedData['packet_id']);
            $subtotal = $packet->price;
            $discount = $validatedData['discount'] ?? 0;

            if (!empty($validatedData['additionals'])) {
                foreach ($validatedData['additionals'] as $details) {
                    $subtotal += $details['quantity'] * $details['price'];
                }
            }

            $totalPrice = $subtotal - $discount;

            // Simpan status sebelumnya untuk pengecekan perubahan status
            $previousStatus = $transaksi->status;
            
            $transaksi->update([
                'customer_name'   => $validatedData['customer_name'],
                'phone_number'    => $validatedData['phone_number'],
                'status'          => $validatedData['status'],
                'process_status'  => $validatedData['process_status'],
                'packet_id'       => $validatedData['packet_id'],
                'total_price'     => max(0, $totalPrice),
                'dp_amount'       => $validatedData['status'] === 'dp' ? $validatedData['dp_amount'] : null,
                'discount'        => $discount,
                'note'            => $validatedData['note'],
            ]);
            
            
            if ($previousStatus !== 'sudah dibayar' && $validatedData['status'] === 'sudah dibayar') {
                
                $description = "Billed To:\nName: {$transaksi->customer_name}\nPhone: {$transaksi->phone_number}\nInvoice Details:\nTransaction Date: " . $transaksi->created_at->format('d-m-Y');
                
               
                $categoryId = \App\Models\ExpenseCategory::where('name', 'Transaction')->first()->id ?? 1;
                
                
                \App\Models\Expense::create([
                    'name' => $transaksi->receipt_code,
                    'description' => $description,
                    'amount' => $transaksi->total_price,
                    'paid_amount' => $transaksi->total_price,
                    'remaining_amount' => 0,
                    'expense_date' => now(),
                    'category_id' => $categoryId,
                    'type' => 'income', 
                    'is_paid' => true,
                ]);
            }

            $syncData = [];
            if (!empty($validatedData['additionals'])) {
                foreach ($validatedData['additionals'] as $additional_id => $details) {
                    $syncData[$additional_id] = ['quantity' => $details['quantity'], 'price' => $details['price']];
                }
            }
            $transaksi->additionals()->sync($syncData);

            DB::commit();
            return redirect()->route('transaksi.index')->with('success', 'Transaction updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Transaction update error: ' . $e->getMessage());
            return redirect()->back()->withInput()->with('error', 'Failed to update transaction.');
        }
    }

    /**
     * Get the default additionals for a given packet via AJAX.
     */
    public function getDefaultAdditionals(Packet $packet)
    {
        $combinedDefaults = $packet->combined_defaults;
        return response()->json($combinedDefaults);
    }

    /**
     * Handle inline status updates from the index page.
     */
    // In app/Http/Controllers/TransaksiController.php

    public function updateStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'field' => ['required', Rule::in(['status', 'process_status'])],
            'value' => ['required', 'string'],
            'dp_amount' => ['nullable', 'numeric', 'min:0', 'required_if:value,dp'],
        ]);

        $transaksi = Transaksi::findOrFail($id);
        $field = $validated['field'];
        $value = $validated['value'];

        if ($field === 'status' && !in_array($value, ['belum dibayar', 'dp', 'sudah dibayar'])) {
            return redirect()->back()->with('error', 'Invalid payment status value.');
        } elseif ($field === 'process_status' && !in_array($value, ['Belum Foto', 'Pilih Foto', 'Siap Edit', 'Proses Edit', 'Selesai Editing', 'Siap Cetak', 'Proses Cetak', 'Selesai'])) {
            return redirect()->back()->with('error', 'Invalid process status value.');
        }

        // --- CORRECTED LOGIC ---
        // If the admin is setting the status to "Siap Cetak", we intercept it to prepare the files first.
        if ($field === 'process_status' && $value === 'Siap Cetak') {
            return $this->preparePrintFiles($transaksi);
        }
        // --- END OF CORRECTED LOGIC ---

        try {
            $transaksi->{$field} = $value;

            // Your friend's code for creating expenses is preserved below.
            if ($field === 'status') {
                $transaksi->dp_amount = ($value === 'dp') ? $validated['dp_amount'] : null;
                
                if ($value === 'sudah dibayar') {
                    $description = "Billed To:\nName: {$transaksi->customer_name}\nPhone: {$transaksi->phone_number}\nInvoice Details:\nTransaction Date: " . $transaksi->created_at->format('d-m-Y');
                    
                    $categoryId = \App\Models\ExpenseCategory::where('name', 'Transaction')->first()->id ?? 1;
                    
                    \App\Models\Expense::create([
                        'name' => $transaksi->receipt_code,
                        'description' => $description,
                        'amount' => $transaksi->total_price,
                        'paid_amount' => $transaksi->total_price,
                        'remaining_amount' => 0,
                        'expense_date' => now(),
                        'category_id' => $categoryId,
                        'type' => 'income',
                        'is_paid' => true,
                    ]);
                }
            }

            if ($field === 'process_status' && $value === 'Pilih Foto') {
                $transaksi->selectedPhotos()->delete();
                $folderName = 'photos/' . str_replace('/', '_', $transaksi->receipt_code);
                $pilihEditPath = $folderName . '/Pilih Edit';
                Storage::disk('public')->delete(Storage::disk('public')->files($pilihEditPath));
            }

            $transaksi->save();

            return redirect()->back()->with('success', 'Status updated successfully.');
        } catch (\Exception $e) {
            report($e);
            return redirect()->back()->with('error', 'Failed to update status: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $transaksi = Transaksi::findOrFail($id);
        
        try {
            // Use the local 'public' disk
            $folderName = $this->getTransactionFolderName($transaksi);
            if (Storage::disk('public')->exists($folderName)) {
                Storage::disk('public')->deleteDirectory($folderName);
            }
        } catch (\Exception $e) {
            Log::error('Failed to delete local photo directory for transaction ' . $transaksi->receipt_code . ': ' . $e->getMessage());
        }

        $transaksi->delete();
        return redirect()->route('transaksi.index')->with('success', 'Transaction deleted successfully.');
    }

    // This method is now for selecting photos TO BE EDITED
    // In app/Http/Controllers/TransaksiController.php

    public function viewSelectForEdit(Transaksi $transaksi)
    {
        try {
            $transaksi->load('packet.printOptions', 'selectedPhotos', 'selectedPrints', 'additionals');

            if (!$transaksi->packet) {
                return redirect()->back()->with('error', 'Transaction is not linked to a valid packet.');
            }

            $photoData = $this->getPhotoDirectoryData($transaksi, 'RAW');
            
            // 1. Ambil kuota dari Paket Utama
            $printAllowances = $transaksi->packet->printOptions->pluck('pivot.quantity', 'name')->toArray();

            // 2. Tambahkan kuota dari Additional (Cetak)
            foreach ($transaksi->additionals as $additional) {
                // Filter hanya yang mengandung kata "Cetak" atau "Print"
                if (stripos($additional->name, 'Cetak') !== false || stripos($additional->name, 'Print') !== false) {
                    $name = $additional->name;
                    $qty = $additional->pivot->quantity;

                    // Jika nama sudah ada di daftar paket (misal: "4R"), tambahkan qty
                    // Kita coba cari apakah ada key yang cocok sebagian
                    $merged = false;
                    foreach ($printAllowances as $key => $val) {
                        // Jika nama additional (misal "Cetak 4R") mengandung nama paket ("4R")
                        if (stripos($name, $key) !== false) {
                            $printAllowances[$key] += $qty;
                            $merged = true;
                            break;
                        }
                    }

                    // Jika tidak ada yang cocok, tambahkan sebagai entry baru
                    if (!$merged) {
                        // Jika key sudah ada (persis sama stringnya), tambahkan qty
                        if (isset($printAllowances[$name])) {
                            $printAllowances[$name] += $qty;
                        } else {
                            $printAllowances[$name] = $qty;
                        }
                    }
                }
            }

            $selectedForEdit = $transaksi->selectedPhotos->pluck('file_url')->toArray();
            $selectedForPrint = $transaksi->selectedPrints->pluck('print_size', 'file_url')->toArray();
            $photoLimit = $transaksi->packet->max_photos_for_edit ?? 10;

            return view('user.transaction.manage-photo', [
                'transaksi'        => $transaksi,
                'photoUrls'        => $photoData['urls'],
                'photoCount'       => $photoData['count'],
                'photoLimit'       => $photoLimit,
                'printAllowances'  => $printAllowances,
                'selectedForEdit'  => $selectedForEdit,
                'selectedForPrint' => $selectedForPrint,
                'formAction'       => route('transaksi.handle-select-for-edit', $transaksi),
            ]);

        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Handle the unified photo selection submission from the user.
     */
    public function handleSelectForEdit(Request $request, Transaksi $transaksi)
    {
        if (!in_array($transaksi->process_status, ['Pilih Foto', 'Siap Edit'])) {
            return redirect()->back()->with('error', 'Photo selection is locked because the editing process has already begun.');
        }

        $validated = $request->validate([
            'selections' => 'nullable|array',
            'selections.*.edit' => 'sometimes|in:1',
            'selections.*.print' => 'sometimes|string|nullable',
        ]);

        $selections = $validated['selections'] ?? [];

        DB::transaction(function () use ($transaksi, $selections) {
            // 1. Clear all previous database selections
            $transaksi->selectedPhotos()->delete();
            $transaksi->selectedPrints()->delete();

            $dataToInsertForEdit = [];
            $dataToInsertForPrint = [];

            // 2. Prepare selection data for database insertion
            foreach ($selections as $url => $selection) {
                if (!empty($selection['edit'])) {
                    $dataToInsertForEdit[] = ['transaction_id' => $transaksi->transaction_id, 'file_url' => $url, 'created_at' => now(), 'updated_at' => now()];
                }
                if (!empty($selection['print'])) {
                    $dataToInsertForPrint[] = ['transaction_id' => $transaksi->transaction_id, 'file_url' => $url, 'print_size' => $selection['print'], 'created_at' => now(), 'updated_at' => now()];
                }
            }

            // 3. Bulk insert new selections into the database
            if (!empty($dataToInsertForEdit)) {
                SelectedPhoto::insert($dataToInsertForEdit);
            }
            if (!empty($dataToInsertForPrint)) {
                SelectedPrint::insert($dataToInsertForPrint);
            }

            // 4. Update the filesystem with the new logic
            // THIS IS THE CORRECTED FOLDER NAME
            $folderName = $this->getTransactionFolderName($transaksi);
            $pilihEditPath = "{$folderName}/Pilih Edit";
            $pilihCetakPath = "{$folderName}/Pilih Cetak";

            // Clear both directories to ensure a clean state
            Storage::disk('public')->delete(Storage::disk('public')->files($pilihEditPath));
            Storage::disk('public')->delete(Storage::disk('public')->files($pilihCetakPath));

            foreach ($selections as $url => $selection) {
                $isForEdit = !empty($selection['edit']);
                $isForPrint = !empty($selection['print']);
                $fileName = basename($url);
                $sourcePath = "{$folderName}/RAW/{$fileName}";

                // Handle files for the editor
                if ($isForEdit) {
                    $newFileNameForEditor = $fileName;

                    // If it's also for print, rename it for the editor
                    if ($isForPrint) {
                        $printSize = $selection['print'];
                        $safeSize = preg_replace('/[^A-Za-z0-9\-]/', '_', $printSize);
                        $newFileNameForEditor = "selected_for_print_{$safeSize}_{$fileName}";
                    }

                    $destinationPath = "{$pilihEditPath}/{$newFileNameForEditor}";
                    if (Storage::disk('public')->exists($sourcePath)) {
                        Storage::disk('public')->copy($sourcePath, $destinationPath);
                    }
                } 
                // Handle files that are ONLY for printing (not editing)
                elseif ($isForPrint && !$isForEdit) {
                    $printSize = $selection['print'];
                    $safeSize = preg_replace('/[^A-Za-z0-9\-]/', '_', $printSize);
                    $newFileNameForPrinter = "selected_for_print_{$safeSize}_{$fileName}";
                    $destinationPath = "{$pilihCetakPath}/{$newFileNameForPrinter}";

                    if (Storage::disk('public')->exists($sourcePath)) {
                        Storage::disk('public')->copy($sourcePath, $destinationPath);
                    }
                }
            }

            // 5. Update the transaction status
            if ($transaksi->process_status === 'Pilih Foto') {
                $transaksi->update(['process_status' => 'Siap Edit']);
            }
        });

        return redirect()->route('transaksi.index')->with('success', 'Your photo selections have been saved successfully!');
    }

    public function preparePrintFiles(Transaksi $transaksi)
    {
        // Security check for admins
        if (!auth()->user()->isAdmin()) {
            abort(403);
        }

        try {
            DB::transaction(function () use ($transaksi) {
                $printSelections = $transaksi->selectedPrints;

                if ($printSelections->isEmpty()) {
                    // If there's nothing to print, just move to the 'Selesai' status
                    $transaksi->update(['process_status' => 'Selesai']);
                    return;
                }

                $folderName = 'photos/' . str_replace('/', '_', $transaksi->receipt_code);
                $pilihCetakPath = "{$folderName}/Pilih Cetak";

                // Clear the directory first
                Storage::disk('public')->delete(Storage::disk('public')->files($pilihCetakPath));

                foreach ($printSelections as $selection) {
                    $fileName = basename($selection->file_url);
                    $sourcePathResult = "{$folderName}/Result/{$fileName}";
                    $sourcePathRaw = "{$folderName}/RAW/{$fileName}";
                    
                    $safeSize = preg_replace('/[^A-Za-z0-9\-]/', '_', $selection->print_size);
                    $destinationPath = "{$pilihCetakPath}/{$safeSize}_{$fileName}";

                    // Prioritize the edited 'Result' file if it exists
                    $sourcePath = Storage::disk('public')->exists($sourcePathResult) ? $sourcePathResult : $sourcePathRaw;

                    if (Storage::disk('public')->exists($sourcePath)) {
                        Storage::disk('public')->copy($sourcePath, $destinationPath);
                    }
                }

                // Update status to the next step in the workflow
                $transaksi->update(['process_status' => 'Siap Cetak']);
            });

            return redirect()->back()->with('success', 'Print files have been prepared successfully.');

        } catch (\Exception $e) {
            Log::error('Failed to prepare print files for transaction ' . $transaksi->receipt_code . ': ' . $e->getMessage());
            return redirect()->back()->with('error', 'An error occurred while preparing print files.');
        }
    }

    // In app/Http/Controllers/TransaksiController.php

    public function viewSelectForPrint(Transaksi $transaksi)
    {
        try {
            // Eager load the packet and its print options relationship
            $transaksi->load('packet.printOptions', 'additionals');

            if (!$transaksi->packet) {
                return redirect()->back()->with('error', 'Transaction is not linked to a valid packet.');
            }

            // Get the print allowances from the packet (e.g., ['8R + Frame' => 2, '4R' => 5])
            $printAllowances = $transaksi->packet->printOptions->pluck('pivot.quantity', 'name')->toArray();

            // 2. Tambahkan kuota dari Additional (Cetak) - LOGIKA SAMA DENGAN VIEW SELECT FOR EDIT
            foreach ($transaksi->additionals as $additional) {
                if (stripos($additional->name, 'Cetak') !== false || stripos($additional->name, 'Print') !== false) {
                    $name = $additional->name;
                    $qty = $additional->pivot->quantity;

                    $merged = false;
                    foreach ($printAllowances as $key => $val) {
                        if (stripos($name, $key) !== false) {
                            $printAllowances[$key] += $qty;
                            $merged = true;
                            break;
                        }
                    }

                    if (!$merged) {
                        if (isset($printAllowances[$name])) {
                            $printAllowances[$name] += $qty;
                        } else {
                            $printAllowances[$name] = $qty;
                        }
                    }
                }
            }

            // Get photos available for printing
            $rawPhotos = $this->getPhotoDirectoryData($transaksi, 'RAW');
            $resultPhotos = $this->getPhotoDirectoryData($transaksi, 'Result');
            $allPhotos = array_unique(array_merge($rawPhotos['urls'], $resultPhotos['urls']));

            // Get previously selected prints
            $selectedForPrint = SelectedPrint::where('transaction_id', $transaksi->transaction_id)
                ->get(['file_url', 'print_size']);

            return view('user.transaction.manage-print-photo', [
                'transaksi'        => $transaksi,
                'photoUrls'        => $allPhotos,
                'printAllowances'  => $printAllowances,
                'selectedForPrint' => $selectedForPrint,
                'pageTitle'        => 'Select Photos for Printing',
                'formAction'       => route('transaksi.handle-select-for-print', $transaksi),
            ]);
        } catch (\Exception $e) {
            // Catch exceptions for cases where a folder (like 'Result') might not exist yet
            return redirect()->back()->with('error', 'Could not load photos. Please ensure photos have been uploaded.');
        }
    }

    // NEW METHOD: Handles submission from the "Select for Print" page
    public function handleSelectForPrint(Request $request, Transaksi $transaksi)
    {
        $request->validate(['selected_photos' => 'nullable|array']);
        $selections = $request->input('selected_photos', []);

        // --- NEW: Validation against allowances ---
        $transaksi->load('packet.printOptions', 'additionals');
        $printAllowances = $transaksi->packet->printOptions->pluck('pivot.quantity', 'name')->toArray();

        // RE-CALCULATE ALLOWANCES (SAME LOGIC AS VIEW)
        foreach ($transaksi->additionals as $additional) {
            if (stripos($additional->name, 'Cetak') !== false || stripos($additional->name, 'Print') !== false) {
                $name = $additional->name;
                $qty = $additional->pivot->quantity;
                $merged = false;
                foreach ($printAllowances as $key => $val) {
                    if (stripos($name, $key) !== false) {
                        $printAllowances[$key] += $qty;
                        $merged = true;
                        break;
                    }
                }
                if (!$merged) {
                    if (isset($printAllowances[$name])) {
                        $printAllowances[$name] += $qty;
                    } else {
                        $printAllowances[$name] = $qty;
                    }
                }
            }
        }

        $selectionCounts = array_count_values(array_filter($selections));

        foreach ($selectionCounts as $size => $count) {
            if (!isset($printAllowances[$size]) || $count > $printAllowances[$size]) {
                return redirect()->back()->withInput()->with('error', "You have selected too many photos for the size: {$size}.");
            }
        }
        // --- End Validation ---

        DB::transaction(function () use ($transaksi, $selections) {
            SelectedPrint::where('transaction_id', $transaksi->transaction_id)->delete();

            $dataToInsert = [];
            $linksToCreate = [];

            foreach ($selections as $url => $size) {
                if (!empty($size)) {
                    $dataToInsert[] = ['transaction_id' => $transaksi->transaction_id, 'file_url' => $url, 'print_size' => $size, 'created_at' => now(), 'updated_at' => now()];
                    $linksToCreate[$url] = $size;
                }
            }

            if (!empty($dataToInsert)) {
                SelectedPrint::insert($dataToInsert);
            }

            $folderName = str_replace('/', '_', $transaksi->receipt_code);
            $pilihCetakPath = storage_path("app/public/photos/{$folderName}/Pilih Cetak");
            File::cleanDirectory($pilihCetakPath);

            foreach ($linksToCreate as $url => $size) {
                $fileName = basename($url);
                $sourcePathRaw = storage_path("app/public/photos/{$folderName}/RAW/{$fileName}");
                $sourcePathResult = storage_path("app/public/photos/{$folderName}/Result/{$fileName}");
                $linkPath = "{$pilihCetakPath}/{$size} - {$fileName}";
                $sourcePath = File::exists($sourcePathResult) ? $sourcePathResult : $sourcePathRaw;

                if (File::exists($sourcePath) && !File::exists($linkPath)) {
                    File::link($sourcePath, $linkPath);
                }
            }
            $transaksi->update(['process_status' => 'Proses Cetak']);
        });

        $redirectData = [
            'success_title'   => 'Print Selection Saved!',
            'success_message' => 'Thank you. We have received your photos for printing.',
            'back_url'        => route('transaksi.index')
        ];

        return redirect()->route('transaksi.view-select-for-print', $transaksi)->with($redirectData);
    }

    protected function getPhotoDirectoryData($transaksi, $status)
    {
        $folderName = $this->getTransactionFolderName($transaksi);
        
        // Path folder ASLI (Source)
        $sourceRelativePath = "{$folderName}/{$status}";
        
        // Path folder THUMBNAIL (Target)
        // Pastikan huruf besar/kecil sesuai dengan folder yang dibuat Command
        $thumbRelativePath = "{$folderName}/Thumbnails/{$status}";

        // Cek keberadaan folder asli
        if (!Storage::disk('public')->exists($sourceRelativePath)) {
            return [
                'folderName' => $folderName,
                'urls' => [],
                'count' => 0
            ];
        }
        
        $files = Storage::disk('public')->files($sourceRelativePath);

        // Filter hanya file gambar
        $photoFiles = array_filter($files, function($file) {
            $extension = pathinfo($file, PATHINFO_EXTENSION);
            return in_array(strtolower($extension), ['jpg', 'jpeg', 'png', 'gif', 'webp']);
        });

        if (empty($photoFiles)) {
            return [
                'folderName' => $folderName,
                'urls' => [],
                'count' => 0
            ];
        }

        // LOGIKA UTAMA: Pilih Thumbnail jika ada, atau GENERATE jika belum ada
        $photoUrls = array_map(function($file) use ($thumbRelativePath, $folderName, $status) {
            $filename = basename($file);
            $thumbnailFile = $thumbRelativePath . '/' . $filename;
            
            // 1. Cek apakah Thumbnail sudah ada?
            if (Storage::disk('public')->exists($thumbnailFile)) {
                return asset('storage/' . $thumbnailFile);
            } 
            
            // 2. Jika belum ada, coba GENERATE ON-THE-FLY
            try {
                // Pastikan folder tujuan ada
                $fullThumbPath = storage_path('app/public/' . $thumbnailFile);
                $thumbDir = dirname($fullThumbPath);
                
                if (!File::exists($thumbDir)) {
                    File::makeDirectory($thumbDir, 0755, true);
                }

                // Ambil file asli (absolute path)
                $realPath = storage_path('app/public/' . $file);

                // Generate Thumbnail (Resize 400px)
                // Menggunakan Intervention Image
                Image::make($realPath)
                    ->resize(400, null, function ($constraint) {
                        $constraint->aspectRatio();
                        $constraint->upsize();
                    })
                    ->save($fullThumbPath, 60); // Quality 60

                return asset('storage/' . $thumbnailFile);

            } catch (\Exception $e) {
                // 3. Fallback: Jika gagal generate (misal error memory), gunakan URL Asli
                return asset('storage/' . $folderName . '/' . $status . '/' . $filename);
            }
        }, $photoFiles);

        return [
            'folderName' => $folderName,
            'urls' => array_values($photoUrls),
            'count' => count($photoUrls)
        ];
    }

    protected function isValidImageFile($file)
    {
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));

        return !in_array($file, ['.', '..']) &&
            in_array($extension, $allowedExtensions);
    }

    public function downloadInvoice(Transaksi $transaksi)
    {
        if ($transaksi->status != "sudah dibayar") {
            return back()->with('error', 'Only paid transactions can download invoices');
        }

        $transaksi->load(
            'packet.product',
            'packet.additionalDefaults.additional',
            'packet.printOptions',
            'additionals'
        );

        $data = [
            'transaksi' => $transaksi,
            'paymentStatusConfig' => [
                'belum dibayar' => 'Unpaid',
                'dp' => 'Down Payment',
                'sudah dibayar' => 'Paid'
            ],
            'processStatusConfig' => [
                'Belum Foto' => 'Not Photographed',
                'Pilih Foto' => 'Selecting Photos',
                'Siap Edit' => 'Ready to Edit',
                'Proses Edit' => 'Editing in Progress',
                'Selesai Editing' => 'Editing Completed',
                'Siap Cetak' => 'Ready to Print',
                'Proses Cetak' => 'Printing',
                'Selesai' => 'Completed'
            ],
            'company' => [
                'name' => 'Your Company Name',
                'address' => 'Jalan Lingkar Selatan, Sukabumi',
                'logo' => public_path('assets/images/yunas_dark.png')
            ]
        ];

        $pdf = Pdf::loadView('invoices.template', $data);
        $filename = "invoice_" . str_replace('/','-',$transaksi->receipt_code) . ".pdf";
        return $pdf->download($filename);
    }

    public function viewSelectionsForAdmin(Transaksi $transaksi, Request $request)
    {
        try {
            // This method also now works correctly
            $filter = $request->get('filter', 'raw');
            $currentFilter = '';
            switch (strtolower($filter)) {
                case "result":
                    $currentFilter = "Result";
                    break;
                case "pilih_cetak":
                    $currentFilter = "Pilih Cetak";
                    break;
                case "pilih_edit":
                    $currentFilter = "Pilih Edit";
                    break;
                default:
                    $currentFilter = "RAW";
                    break;
            }

            $photoData = $this->getPhotoDirectoryData($transaksi, $currentFilter);
            $selectedUrls = $transaksi->selectedPhotos->pluck('file_url')->toArray();
            $selectedForPrint = $transaksi->selectedPrints->pluck('print_size', 'file_url');

            return view('admin.transaction.view-selections', [
                'transaksi' => $transaksi,
                'photoUrls' => $photoData['urls'],
                'photoCount' => $photoData['count'],
                'currentFilter' => $currentFilter,
                'selectedUrls' => $selectedUrls,
                'selectedForPrint' => $selectedForPrint,
            ]);

        } catch (\Exception $e) {
            return redirect()->route('transaksi.index')->with('error', $e->getMessage());
        }
    }

    /**
     * Display a printer-friendly version of the invoice.
     */
    public function printInvoice(Transaksi $transaksi)
    {
        // CORRECTED: Eager load all necessary relationships for printing
        $transaksi->load(
            'packet.product',
            'packet.additionalDefaults.additional',
            'packet.printOptions', // Corrected relationship loading
            'additionals'
        );

        return view('invoices.print-template', ['transaksi' => $transaksi]);
    }

    public function downloadAllPhotosAsZip(Transaksi $transaksi)
    {
        // Security Check: Ensure the logged-in user owns this transaction or is an admin
        if (!auth()->user()->isAdmin() && auth()->user()->username !== $transaksi->phone_number) {
            abort(403, 'Unauthorized action.');
        }

        if (!class_exists('ZipArchive')) {
            return redirect()->back()->with('error', 'The ZipArchive PHP extension is not installed or enabled on the server.');
        }

        try {
            // UPDATED: Use local 'public' disk
            $folderName = 'photos/' . str_replace('/', '_', $transaksi->receipt_code);
            $directory = "{$folderName}/RAW";
            
            $files = Storage::disk('public')->files($directory);

            if (empty($files)) {
                return redirect()->back()->with('error', 'No photos found to download.');
            }

            $zipPath = tempnam(sys_get_temp_dir(), 'zip');
            $zip = new ZipArchive;
            
            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
                throw new \Exception('Cannot create zip archive.');
            }

            foreach ($files as $file) {
                // Use addFile to stream file content directly into zip without loading into memory
                $absolutePath = Storage::disk('public')->path($file);
                $zip->addFile($absolutePath, basename($file));
            }
            
            $zip->close();

            $zipFileName = str_replace('/', '_', $transaksi->receipt_code) . '.zip';

            return response()->download($zipPath, $zipFileName)->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            Log::error('Failed to create zip for transaction ' . $transaksi->receipt_code . ': ' . $e->getMessage());
            return redirect()->back()->with('error', 'Could not create zip file. The photo directory may be empty.');
        }
    }

    public function downloadSelectedPhotosAsZip(Transaksi $transaksi)
    {
        // Security Check: Only admins should access this
        if (!auth()->user()->isAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        if (!class_exists('ZipArchive')) {
            return redirect()->back()->with('error', 'The ZipArchive PHP extension is not installed or enabled on the server.');
        }

        try {
            $selectedPhotos = $transaksi->selectedPhotos;

            if ($selectedPhotos->isEmpty()) {
                return redirect()->back()->with('error', 'No photos have been selected for download.');
            }

            $zipPath = tempnam(sys_get_temp_dir(), 'zip');
            $zip = new ZipArchive;
            
            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
                throw new \Exception('Cannot create zip archive.');
            }

            foreach ($selectedPhotos as $photo) {
                // Convert the public URL back to a storage path
                $filePath = str_replace(asset('storage/'), '', $photo->file_url);
                
                if (Storage::disk('public')->exists($filePath)) {
                    // Use addFile to avoid memory exhaustion
                    $absolutePath = Storage::disk('public')->path($filePath);
                    $zip->addFile($absolutePath, basename($filePath));
                }
            }
            
            $zip->close();

            $zipFileName = str_replace('/', '_', $transaksi->receipt_code) . '_Selected.zip';

            return response()->download($zipPath, $zipFileName)->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            Log::error('Failed to create selected photos zip for transaction ' . $transaksi->receipt_code . ': ' . $e->getMessage());
            return redirect()->back()->with('error', 'Could not create zip file.');
        }
    }

    public function viewResultPhotos(Transaksi $transaksi, Request $request)
    {
        // --- NEW SECURITY CHECK ---
        // Allow access only if the transaction is fully paid OR if the viewer is an admin.
        if ($transaksi->status !== 'sudah dibayar' && !auth()->user()->isAdmin()) {
            return redirect()->route('transaksi.index')->with('error', 'You must complete your payment before you can view the final photos.');
        }
        // --- END SECURITY CHECK ---

        try {
            // This method now works correctly because of the change to getPhotoDirectoryData
            $transaksi->load('packet.printOptions');
            $printAllowances = $transaksi->packet ? $transaksi->packet->printOptions->isNotEmpty() : false;

            $filter = $request->get('filter', 'raw');
            $currentFilter = '';
            switch (strtolower($filter)) {
                case "result":
                    $currentFilter = "Result";
                    break;
                case "pilih_cetak":
                    $currentFilter = "Pilih Cetak";
                    break;
                case "pilih_edit":
                    $currentFilter = "Pilih Edit";
                    break;
                default:
                    $currentFilter = "RAW";
                    break;
            }

            $photos = $this->getPhotoDirectoryData($transaksi, $currentFilter);
            
            return view('user.transaction.result-photo', [
                'transaksi' => $transaksi,
                'photoUrls' => $photos['urls'],
                'photoCount' => $photos['count'],
                'currentFilter' => $currentFilter,
                'printAllowances' => $printAllowances
            ]);

        } catch (\Exception $e) {
            // This catch block will now only trigger for truly unexpected errors.
            return redirect()->route('transaksi.index')->with('error', $e->getMessage());
        }
    }
    
    public function downloadFolderAsZip(Transaksi $transaksi, $status)
    {
        // --- NEW SECURITY CHECK ---
        // Allow download only if the transaction is fully paid OR if the viewer is an admin.
        if ($transaksi->status !== 'sudah dibayar' && !auth()->user()->isAdmin()) {
            abort(403, 'You must complete your payment to download photos.');
        }
        // --- END SECURITY CHECK ---

        if (auth()->user()->username !== $transaksi->phone_number && !auth()->user()->isAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        // Validate the folder name to prevent directory traversal
        $allowedFolders = ['RAW', 'Result', 'Pilih Edit', 'Pilih Cetak'];
        if (!in_array($status, $allowedFolders)) {
            abort(404, 'Folder not found.');
        }

        if (!class_exists('ZipArchive')) {
            return redirect()->back()->with('error', 'The ZipArchive PHP extension is not enabled on the server.');
        }

        try {
            $folderName = $this->getTransactionFolderName($transaksi);
            $directory = "{$folderName}/{$status}";
            
            $files = Storage::disk('public')->files($directory);

            if (empty($files)) {
                return redirect()->back()->with('error', "No photos found in the '{$status}' folder to download.");
            }

            $zipPath = tempnam(sys_get_temp_dir(), 'zip');
            $zip = new ZipArchive;
            
            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
                throw new \Exception('Cannot create zip archive.');
            }

            foreach ($files as $file) {
                // Use addFile instead of addFromString to be memory efficient
                $absolutePath = Storage::disk('public')->path($file);
                $zip->addFile($absolutePath, basename($file));
            }
            
            $zip->close();

            $zipFileName = str_replace('/', '_', $transaksi->receipt_code) . "_{$status}.zip";

            return response()->download($zipPath, $zipFileName)->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            Log::error("Failed to create zip for folder '{$status}' in transaction " . $transaksi->receipt_code . ': ' . $e->getMessage());
            return redirect()->back()->with('error', "Could not create zip file for the '{$status}' folder.");
        }
    }
}