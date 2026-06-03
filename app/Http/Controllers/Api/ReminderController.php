<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Reminder;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReminderController extends Controller
{
    /** Resolve user dari Auth atau fallback user_id query param */
    private function resolveUser(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            $userId = $request->query('user_id') ?? $request->input('user_id');
            if ($userId) {
                $user = User::find($userId);
            }
        }
        return $user;
    }

    /** GET /api/v1/reminders */
    public function index(Request $request)
    {
        $user = $this->resolveUser($request);
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $reminders = Reminder::where('user_id', $user->id)
            ->orderBy('reminder_date')
            ->orderBy('reminder_time')
            ->get();

        return response()->json(['success' => true, 'data' => $reminders]);
    }

    /** POST /api/v1/reminders */
    public function store(Request $request)
    {
        $user = $this->resolveUser($request);
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $validated = $request->validate([
            'title'         => 'required|string|max:255',
            'description'   => 'nullable|string',
            'reminder_date' => 'required|date',
            'reminder_time' => 'nullable|date_format:H:i',
            'category'      => 'required|in:kontrol,obat,vaksin,premi,lainnya',
            'repeat'        => 'nullable|in:none,daily,weekly,monthly',
        ]);

        $reminder = Reminder::create([
            'user_id'       => $user->id,
            'title'         => $validated['title'],
            'description'   => $validated['description'] ?? null,
            'reminder_date' => $validated['reminder_date'],
            'reminder_time' => $validated['reminder_time'] ?? null,
            'category'      => $validated['category'],
            'repeat'        => $validated['repeat'] ?? 'none',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pengingat berhasil dibuat.',
            'data'    => $reminder,
        ], 201);
    }

    /** PUT /api/v1/reminders/{id} */
    public function update(Request $request, $id)
    {
        $user = $this->resolveUser($request);
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $reminder = Reminder::where('user_id', $user->id)->findOrFail($id);

        $validated = $request->validate([
            'title'         => 'sometimes|string|max:255',
            'description'   => 'nullable|string',
            'reminder_date' => 'sometimes|date',
            'reminder_time' => 'nullable|date_format:H:i',
            'category'      => 'sometimes|in:kontrol,obat,vaksin,lainnya',
            'repeat'        => 'nullable|in:none,daily,weekly,monthly',
            'is_done'       => 'sometimes|boolean',
        ]);

        $reminder->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Pengingat berhasil diperbarui.',
            'data'    => $reminder,
        ]);
    }

    /** DELETE /api/v1/reminders/{id} */
    public function destroy(Request $request, $id)
    {
        $user = $this->resolveUser($request);
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $reminder = Reminder::where('user_id', $user->id)->findOrFail($id);
        $reminder->delete();

        return response()->json(['success' => true, 'message' => 'Pengingat berhasil dihapus.']);
    }

    /** GET /api/v1/reminders/today */
    public function today(Request $request)
    {
        $user = $this->resolveUser($request);
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $reminders = Reminder::where('user_id', $user->id)
            ->whereDate('reminder_date', Carbon::today())
            ->where('is_done', false)
            ->orderBy('reminder_time')
            ->get();

        Reminder::where('user_id', $user->id)
            ->whereDate('reminder_date', Carbon::today())
            ->where('is_notified', false)
            ->update(['is_notified' => true]);

        return response()->json(['success' => true, 'data' => $reminders]);
    }

    /** GET /api/v1/reminders/upcoming */
    public function upcoming(Request $request)
    {
        $user = $this->resolveUser($request);
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $reminders = Reminder::where('user_id', $user->id)
            ->whereDate('reminder_date', '>=', Carbon::today())
            ->whereDate('reminder_date', '<=', Carbon::today()->addDays(7))
            ->where('is_done', false)
            ->orderBy('reminder_date')
            ->orderBy('reminder_time')
            ->get();

        return response()->json(['success' => true, 'data' => $reminders]);
    }
}
