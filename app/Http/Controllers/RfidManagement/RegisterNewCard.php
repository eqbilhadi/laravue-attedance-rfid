<?php

namespace App\Http\Controllers\RfidManagement;

use App\Http\Controllers\Controller;
use App\Http\Requests\RfidManagement\RegisterNewCard\CreateRfidCardRequest;
use App\Models\UserRfid;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Throwable;

class RegisterNewCard extends Controller
{
    public function index()
    {
        return Inertia::render('rfid-management/register-new-card/Index');
    }

     public function store(CreateRfidCardRequest $request)
    {
        try {
            UserRfid::create($request->validated());

            return redirect()->route('rfid-management.register-new-card.index')->with('success', 'RFID created successfully!');
        } catch (Throwable $e) {
            Log::error('RFID create failed', [
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->withInput()->with('error', 'Failed to create rfid. Please try again.');
        }
    }
}
