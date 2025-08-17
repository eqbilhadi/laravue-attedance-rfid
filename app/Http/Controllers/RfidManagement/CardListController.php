<?php

namespace App\Http\Controllers\RfidManagement;

use App\Http\Controllers\Controller;
use App\Models\UserRfid;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Illuminate\Validation\Rule;
use Throwable;

class CardListController extends Controller
{
    /**
     * Menampilkan daftar kartu RFID user dengan filter dan paginasi.
     */
    public function index(Request $request)
    {
        $query = UserRfid::query()
            ->with('user')
            ->withCount('scans')
            ->whereHas('user', function ($query) use ($request) {
                $query->when($request->filled('search'), function ($q) use ($request) {
                    $q->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($request->input('search')) . '%']);
                });
                $query->when($request->filled('is_active'), function ($q) use ($request) {
                    $q->where('is_active', $request->input('is_active'));
                });
            });

        $data = $query->latest()->paginate(12)->withQueryString();

        return Inertia::render('rfid-management/card-list/Index', [
            'data' => $data,
            'filters' => $request->only(['search', 'is_active']),
        ]);
    }

    public function update(Request $request, UserRfid $card)
    {
        $validated = $request->validate([
            'uid' => [
                'required',
                'string',
                Rule::unique('sys_user_rfids')->ignore($card->id),
            ],
        ]);

        try {
            $card->update($validated);

            return redirect()->back()->with('success', [
                'title' => 'Card Updated!',
                'description' => 'UID for ' . $card->user->name . ' has been changed.'
            ]);
        } catch (Throwable $e) {
            Log::error('Card update failed', [
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->withInput()->with('error', [
                'title' => 'Failed to update card.',
                'description' => 'Please try again.'
            ]);
        }
    }

    public function destroy(UserRfid $card)
    {
        try {
            $name = $card->user->name;
            $card->delete();
            return redirect()->back()->with('success', [
                'title' => 'Card Deleted!',
                'description' => 'Card for ' . $name . ' has been deleted.'
            ]);
        } catch (Throwable $e) {
            Log::error('Card delete failed', [
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->withInput()->with('error', [
                'title' => 'Failed to delete card.',
                'description' => 'Please try again.'
            ]);
        }
    }
}
