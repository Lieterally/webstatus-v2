<?php

namespace App\Http\Controllers;

use App\Models\TelegramTarget;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TelegramTargetController extends Controller
{
    /**
     * Display a listing of all Telegram targets.
     */
    public function index(): View
    {
        $targets = TelegramTarget::orderBy('created_at', 'desc')->get();

        return view('telegram-targets.index', compact('targets'));
    }

    /**
     * Show the form for creating a new Telegram target.
     */
    public function create(): View
    {
        return view('telegram-targets.create');
    }

    /**
     * Store a newly created Telegram target in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'chat_id' => ['required', 'string', 'max:32', 'regex:/^\d+$/', 'unique:telegram_targets,chat_id'],
            'username' => ['nullable', 'string', 'max:100'],
            'is_active' => ['nullable', 'in:0,1'],
        ], [
            'chat_id.required' => 'The chat ID is required.',
            'chat_id.max' => 'The chat ID must not exceed 32 characters.',
            'chat_id.regex' => 'The chat ID must be a numeric string.',
            'chat_id.unique' => 'This chat ID is already registered.',
            'username.max' => 'The username must not exceed 100 characters.',
            'is_active.in' => 'The is_active field must be 0 or 1.',
        ]);

        TelegramTarget::create([
            'chat_id' => $validated['chat_id'],
            'username' => $validated['username'] ?? null,
            'is_active' => $validated['is_active'] ?? 1,
        ]);

        return redirect()->route('telegram-targets.index')
            ->with('success', 'Telegram target created successfully.');
    }

    /**
     * Show the form for editing the specified Telegram target.
     */
    public function edit(TelegramTarget $telegramTarget): View
    {
        return view('telegram-targets.edit', compact('telegramTarget'));
    }

    /**
     * Update the specified Telegram target in storage.
     */
    public function update(Request $request, TelegramTarget $telegramTarget): RedirectResponse
    {
        $validated = $request->validate([
            'chat_id' => [
                'required',
                'string',
                'max:32',
                'regex:/^\d+$/',
                Rule::unique('telegram_targets', 'chat_id')->ignore($telegramTarget->id),
            ],
            'username' => ['nullable', 'string', 'max:100'],
            'is_active' => ['nullable', 'in:0,1'],
        ], [
            'chat_id.required' => 'The chat ID is required.',
            'chat_id.max' => 'The chat ID must not exceed 32 characters.',
            'chat_id.regex' => 'The chat ID must be a numeric string.',
            'chat_id.unique' => 'This chat ID is already registered.',
            'username.max' => 'The username must not exceed 100 characters.',
            'is_active.in' => 'The is_active field must be 0 or 1.',
        ]);

        $telegramTarget->update([
            'chat_id' => $validated['chat_id'],
            'username' => $validated['username'] ?? null,
            'is_active' => $validated['is_active'] ?? 1,
        ]);

        return redirect()->route('telegram-targets.index')
            ->with('success', 'Telegram target updated successfully.');
    }

    /**
     * Remove the specified Telegram target from storage.
     * The confirmation prompt is handled on the frontend (JavaScript/Alpine.js).
     */
    public function destroy(TelegramTarget $telegramTarget): RedirectResponse
    {
        $telegramTarget->delete();

        return redirect()->route('telegram-targets.index')
            ->with('success', 'Telegram target deleted successfully.');
    }
}
