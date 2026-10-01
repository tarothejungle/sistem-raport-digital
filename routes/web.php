<?php

use App\Http\Controllers\LegerPtsExportController;
use App\Http\Controllers\MaintenanceAdministratorLoginController;
use App\Http\Controllers\NilaiSpreadsheetController;
use App\Http\Controllers\RaportPdfController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');
Route::redirect('/login', '/admin/login')->name('login');
Route::get('/maintenance/administrator', [MaintenanceAdministratorLoginController::class, 'create'])
    ->name('maintenance.admin.login');
Route::post('/maintenance/administrator', [MaintenanceAdministratorLoginController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('maintenance.admin.authenticate');

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
        )->middleware('throttle:report-bulk')->name('rapor.bulk-download');

        Route::get(
            '/leger-pts/{kelas}/{tahunAjaran}/unduh.xlsx',
            [LegerPtsExportController::class, 'xlsx'],
        )->name('leger-pts.xlsx');

        Route::get(
            '/leger-pts/{kelas}/{tahunAjaran}/unduh.pdf',
            [LegerPtsExportController::class, 'pdf'],
        )->name('leger-pts.pdf');

        Route::get(
            '/input-nilai/{jadwalMengajar}/template.xlsx',
            [NilaiSpreadsheetController::class, 'template'],
        )->name('nilai.template');
    });
