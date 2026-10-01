<?php

namespace App\Http\Controllers;

use App\Models\JadwalMengajar;
use App\Services\NilaiSpreadsheetService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class NilaiSpreadsheetController extends Controller
{
    public function template(
        JadwalMengajar $jadwalMengajar,
        NilaiSpreadsheetService $spreadsheetService,
    ): BinaryFileResponse {
        return $spreadsheetService->template($jadwalMengajar);
    }
}
