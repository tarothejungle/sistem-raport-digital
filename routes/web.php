<?php

use App\Http\Controllers\RaportPdfController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');
Route::redirect('/login', '/admin/login')->name('login');

Route::middleware('auth')
    ->prefix('admin')
    ->name('admin.')
    ->group(function (): void {
        Route::get(
            '/cetak-rapor/{siswa}/{tahunAjaran}/pratinjau',
            [RaportPdfController::class, 'preview'],
        )->name('rapor.preview');

        Route::get(
            '/cetak-rapor/{siswa}/{tahunAjaran}/unduh',
            [RaportPdfController::class, 'download'],
        )->name('rapor.download');

        Route::get(
            '/cetak-rapor/{tahunAjaran}/unduh-terpilih',
            [RaportPdfController::class, 'bulkDownload'],
        )->name('rapor.bulk-download');
    });
