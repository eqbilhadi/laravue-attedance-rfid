<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\RfidResource;
use App\Services\TapCard\RfidProcessingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class TapCardController extends Controller
{
    protected $rfidService;

    public function __construct(RfidProcessingService $rfidService)
    {
        $this->rfidService = $rfidService;
    }

    public function check(Request $request)
    {
        try {
            $result = $this->rfidService->processTap($request);

            return new RfidResource(
                $result['success'],
                $result['message'],
                $result['title'],
                $result['data']
            );

        } catch (ValidationException $e) {
            // Tangkap error validasi dari service dan format sebagai response
            $errors = $e->validator->errors();
            $title = $errors->first('title') ?? 'VALIDATION FAILED';
            $message = $errors->first('message') ?? 'An error occurred.';
            
            return new RfidResource(false, $message, $title, null);

        } catch (\Throwable $e) {
            // Tangkap semua error lainnya
            Log::error('Tap card failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString() // Optional: for more detailed logging
            ]);

            return new RfidResource(false, "Terjadi kesalahan pada server.", "SERVER ERROR", null);
        }
    }
}
