<?php

namespace App\Http\Controllers\RfidManagement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RegisterNewCard extends Controller
{
    public function index()
    {
        return Inertia::render('rfid-management/register-new-card/Index');
    }
}
