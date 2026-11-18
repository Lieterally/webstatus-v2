<?php

namespace App\Http\Controllers;

use App\Models\DowntimeHistory;
use App\Services\PublicStatusService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Saves public notes on outages. One request can set several outages at once (the outages of one
 * day square on the public page). An empty note removes it. Notes are public: they show on the
 * status page, /history and /status.json.
 */
class OutageNoteController extends Controller
{
    public function update(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'notes' => ['required', 'array', 'min:1', 'max:50'],
            'notes.*.id' => ['required', 'integer', 'distinct', 'exists:downtime_histories,id'],
            'notes.*.note' => ['nullable', 'string', 'max:' . DowntimeHistory::NOTE_MAX_LENGTH],
        ], [
            'notes.*.note.max' => 'A note can be at most ' . DowntimeHistory::NOTE_MAX_LENGTH . ' characters.',
        ]);

        $saved = [];
        foreach ($validated['notes'] as $row) {
            $outage = DowntimeHistory::find($row['id']);
            $note = trim((string) ($row['note'] ?? ''));
            $note = $note === '' ? null : $note;

            if ($outage->note !== $note) {
                $outage->update([
                    'note' => $note,
                    'note_updated_by' => $request->user()->id,
                    'note_updated_at' => now(),
                ]);
            }
            $saved[] = ['id' => $outage->id, 'note' => $outage->note];
        }

        PublicStatusService::forget();

        if ($request->expectsJson()) {
            return response()->json(['notes' => $saved]);
        }

        return back()->with('success', count($saved) === 1 ? 'Note saved.' : 'Notes saved.');
    }
}
