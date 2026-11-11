<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\dataGatewayController;
use App\Http\Controllers\User\HomeController as UserHomeController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PacketController;
use App\Http\Controllers\User\PacketController as UserPacketController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\User\ProductController as UserProductController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\ExpenseCategoryController;
use App\Http\Controllers\TransaksiController;
use App\Http\Controllers\AdditionalController;
use App\Http\Controllers\AdditionalDefaultController;
use App\Http\Controllers\User\PaymentController;
use App\Http\Controllers\User\BookingController;
use App\Http\Controllers\User\RescheduleRequestController;
use App\Http\Controllers\User\KontakController;
use App\Http\Controllers\User\GaleriController;
use App\Http\Controllers\RescheduleController;
use App\Http\Controllers\User\PhotoController;
 



   
// ── Halaman Jadwal ────────────────────────────────────────────────────
Route::get('/jadwal', [RescheduleRequestController::class, 'index'])
    ->name('schedule');
 
// ── API: slot tersedia per tanggal ────────────────────────────────────
Route::get('/jadwal/slots', [RescheduleRequestController::class, 'slots'])
    ->name('schedule.slots');
 
// ── API: cari booking milik user (untuk reschedule) ───────────────────
Route::post('/jadwal/cari-booking', [RescheduleRequestController::class, 'cariBooking'])
    ->name('schedule.cari-booking');
 
// ── API: simpan reschedule ────────────────────────────────────────────
Route::post('/jadwal/reschedule', [RescheduleRequestController::class, 'rescheduleRequest'])
    ->name('schedule.reschedule');

// User Page
    Route::get('/booking', [UserHomeController::class, 'index'])->name('userPage.home');
    Route::get('/product/{id}', [HomeController::class, 'product'])->name('product.filter');

    // Booking
    Route::get('/booking/{order_id}/download-pdf',[BookingController::class, 'downloadPdf'])
        ->where('order_id', '.*')
        ->name('booking.download.pdf');
    Route::get('/booking/available-slots', [BookingController::class, 'availableSlots'])
    ->name('booking.available-slots');

    //galeri
    Route::get('/galeri', [GaleriController::class, 'index'])->name('galeri');
    Route::get('/kontak', [KontakController::class, 'index'])->name('kontak');

    // ── Payment (Midtrans) ───────────────────────────────────────────────────
    Route::post('/booking/snap-token', [BookingController::class, 'createSnapToken'])->name('booking.snap-token');

    // ── Payment redirect pages ────────────────────────────────────────────────
    Route::get('/payment/success', [PaymentController::class, 'paymentSuccess'])->name('payment.success');
    Route::get('/payment/failed', [PaymentController::class, 'paymentFailed'])->name('payment.failed');

    // ── Webhook Midtrans — 
    Route::post('/payment/webhook', [PaymentController::class, 'handleWebhook'])->name('payment.webhook');




// Admin Routes
Auth::routes();

Route::middleware(['auth'])->group(function () {
    Route::get('/', [HomeController::class, 'root']);
    Route::get('/dashboard', [HomeController::class, 'index'])->name('dashboard');

    // User Management
    Route::resource('users', UserController::class);
    Route::put('/users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');
    Route::post('/change-password', [UserController::class, 'changePassword'])->name('change.password.post');
    Route::put('/user/{user}/reset-password', [UserController::class, 'resetPassword'])->name('reset.password.post');

    // Additional and Packet
    Route::resource('additionals', AdditionalController::class);
    Route::resource('additional-defaults', AdditionalDefaultController::class);
    Route::resource('products', ProductController::class);
    Route::resource('packets', PacketController::class);
    Route::put('/packets/{packet}/toggle-status', [PacketController::class, 'toggleStatus'])->name('packets.toggle-status');
    Route::get('packets/product/{id}', [PacketController::class, 'product'])->name('packets.product');
    Route::get('/packets/{packet}/default-additionals', [TransaksiController::class, 'getDefaultAdditionals'])->name('packets.default-additionals');
    Route::post('/packets/{packet}/print-options', [PacketController::class, 'addPrintOption'])->name('packets.addPrintOption');
    Route::delete('/packets/{packet}/print-options/{print_size}', [PacketController::class, 'removePrintOption'])->name('packets.removePrintOption');

    // Expense
    Route::resource('expenses', ExpenseController::class);
    Route::put('/expenses/{expense}/toggle-payment', [ExpenseController::class, 'togglePayment'])->name('expenses.toggle-payment');
    Route::post('/expenses/{expense}/partial-payment', [ExpenseController::class, 'makePartialPayment'])->name('expenses.partial-payment');
    Route::get('/expenses/{expense}/debt-payments', [ExpenseController::class, 'showDebtPayments'])->name('expenses.debt-payments');
    Route::resource('expense-categories', ExpenseCategoryController::class);
    Route::put('/expense-categories/{expenseCategory}/toggle-monthly-default', [ExpenseCategoryController::class, 'toggleMonthlyDefault'])->name('expense-categories.toggle-monthly-default');
    Route::post('/expenses/generate-monthly', [ExpenseController::class, 'generateMonthlyExpenses'])->name('expenses.generate-monthly');

    // Transaksi
    Route::resource('transaksi', TransaksiController::class);
    Route::put('/transaksi/{id}/update-status', [TransaksiController::class, 'updateStatus'])->name('transaksi.update-status');
    Route::resource('gateway', dataGatewayController::class);

    // User Photo Gallery (Google Drive API)
    Route::get('/foto', [PhotoController::class, 'index'])->name('user.foto.index');


    Route::prefix('transaksi/{transaksi}')->name('transaksi.')->group(function () {
        Route::get('/select-for-edit', [TransaksiController::class, 'viewSelectForEdit'])->name('view-select-for-edit');
        Route::post('/handle-select-for-edit', [TransaksiController::class, 'handleSelectForEditUser'])->name('handle-select-for-edit');
        Route::get('/select-for-print', [TransaksiController::class, 'viewSelectForPrint'])->name('view-select-for-print');
        Route::post('/handle-select-for-print', [TransaksiController::class, 'handleSelectForPrint'])->name('handle-select-for-print');
        Route::get('/result-photos', [TransaksiController::class, 'viewResultPhotos'])->name('view-result-photos');
        Route::get('/download-invoice', [TransaksiController::class, 'downloadInvoice'])->name('download-invoice');

        // Route View Selections lama (tetap ada jika dibutuhkan via URL, tapi button dihapus di view)
        Route::get('/view-selections', [TransaksiController::class, 'viewSelectionsForAdmin'])->name('view-selections');

        // Route BARU untuk update inputan manual dari WA
        Route::put('/update-selections', [TransaksiController::class, 'updateSelections'])->name('update-selections');

        Route::get('/print-invoice', [TransaksiController::class, 'printInvoice'])->name('print-invoice');
        Route::get('/download-all', [TransaksiController::class, 'downloadAllPhotosAsZip'])->name('downloadAll');
        Route::get('/download-selected', [TransaksiController::class, 'downloadSelectedPhotosAsZip'])->name('downloadSelected');
        Route::get('/download-folder/{status}', [TransaksiController::class, 'downloadFolderAsZip'])->name('downloadFolder');

        
    });
    // Route untuk reschedule
        Route::get('/reschedule', [RescheduleController::class, 'index'])->name('view-reschedule');
        Route::post('/reschedule/{id}/approve', [RescheduleController::class, 'approve'])->name('reschedule.approve');
        Route::post('/reschedule/{id}/reject', [RescheduleController::class, 'reject'])->name('reschedule.reject');
});


Route::get('index/{locale}', [HomeController::class, 'lang']);
Route::post('/formsubmit', [HomeController::class, 'FormSubmit'])->name('FormSubmit');
Route::get('{any}', [HomeController::class, 'index'])->where('any', '.*');