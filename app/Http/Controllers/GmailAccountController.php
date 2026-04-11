<?php

namespace App\Http\Controllers;

use App\Models\GmailAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GmailAccountController extends Controller
{
    // Get all accounts
    public function index()
    {
        $accounts = GmailAccount::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($account) {
                return [
                    'id'           => $account->id,
                    'email'        => $account->email,
                    'name'         => $account->name,
                    'avatar'       => $account->avatar,
                    'daily_limit'  => $account->daily_limit,
                    'daily_sent'   => $account->daily_sent,
                    'total_sent'   => $account->total_sent,
                    'is_active'    => $account->is_active,
                    'remaining'    => $account->remainingToday(),
                    'token_status' => $account->tokenStatus(),
                    'token_expires_in' => $account->tokenExpiresInMinutes(),
                    'last_used_at' => $account->last_used_at,
                ];
            });

        return response()->json([
            'success'  => true,
            'accounts' => $accounts
        ]);
    }

    // Toggle account active status
    public function toggle($id)
    {
        $account = GmailAccount::where('user_id', Auth::id())
                               ->findOrFail($id);

        $account->update([
            'is_active' => !$account->is_active
        ]);

        return response()->json([
            'success'   => true,
            'is_active' => $account->is_active,
            'message'   => $account->is_active
                ? 'Account activated'
                : 'Account deactivated'
        ]);
    }

    // Update daily limit
    public function updateLimit(Request $request, $id)
    {
        $request->validate([
            'daily_limit' => 'required|integer|min:1|max:500'
        ]);

        $account = GmailAccount::where('user_id', Auth::id())
                               ->findOrFail($id);

        $account->update([
            'daily_limit' => $request->daily_limit
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Daily limit updated'
        ]);
    }

    // Reset daily count
    public function resetDaily($id)
    {
        $account = GmailAccount::where('user_id', Auth::id())
                               ->findOrFail($id);

        $account->resetDailyCount();

        return response()->json([
            'success' => true,
            'message' => 'Daily count reset'
        ]);
    }

    // Delete account
    public function destroy($id)
    {
        $account = GmailAccount::where('user_id', Auth::id())
                               ->findOrFail($id);

        $account->delete();

        return response()->json([
            'success' => true,
            'message' => 'Account removed'
        ]);
    }
}