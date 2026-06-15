<?php

namespace App\Http\Controllers;

use App\Models\ITStaff;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ITStaffController extends Controller
{
    /**
     * Display a listing of all IT staff members.
     */
    public function index(): View
    {
        $staff = ITStaff::orderBy('name')->get();

        return view('it-staff.index', compact('staff'));
    }

    /**
     * Show the form for creating a new IT staff member.
     */
    public function create(): View
    {
        return view('it-staff.create');
    }

    /**
     * Store a newly created IT staff member in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|min:1|max:100',
            'position' => 'required|string|min:1|max:100',
        ]);

        ITStaff::create($validated);

        return redirect()->route('it-staff.index')
            ->with('success', 'IT staff member created successfully.');
    }

    /**
     * Show the form for editing the specified IT staff member.
     */
    public function edit(ITStaff $itStaff): View
    {
        return view('it-staff.edit', compact('itStaff'));
    }

    /**
     * Update the specified IT staff member in storage.
     */
    public function update(Request $request, ITStaff $itStaff): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|min:1|max:100',
            'position' => 'required|string|min:1|max:100',
        ]);

        $itStaff->update($validated);

        return redirect()->route('it-staff.index')
            ->with('success', 'IT staff member updated successfully.');
    }

    /**
     * Remove the specified IT staff member from storage.
     */
    public function destroy(ITStaff $itStaff): RedirectResponse
    {
        // Prevent deletion of staff assigned to sites
        if ($itStaff->sites()->exists()) {
            return redirect()->route('it-staff.index')
                ->with('error', 'Cannot delete IT staff member that is currently assigned to one or more monitored sites.');
        }

        $itStaff->delete();

        return redirect()->route('it-staff.index')
            ->with('success', 'IT staff member deleted successfully.');
    }
}
