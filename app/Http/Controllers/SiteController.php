<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSiteRequest;
use App\Http\Requests\UpdateSiteRequest;
use App\Models\Category;
use App\Models\ITStaff;
use App\Models\Page;
use App\Models\Site;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SiteController extends Controller
{
    /**
     * Display a listing of all monitored sites.
     */
    public function index(): View
    {
        $query = Site::with(['category', 'responsiblePerson', 'pages'])
            ->withCount('pages')
            ->orderBy('name');

        // Search filter
        if ($search = request('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        // Category filter
        if ($categoryId = request('category')) {
            $query->where('category_id', $categoryId);
        }

        // Responsible person filter
        if ($personId = request('person')) {
            $query->where('responsible_person_id', $personId);
        }

        $sites = $query->paginate(10)->withQueryString();
        $categories = Category::orderBy('name')->get();
        $staffMembers = ITStaff::orderBy('name')->get();

        return view('sites.index', compact('sites', 'categories', 'staffMembers'));
    }

    /**
     * Show the form for creating a new site.
     */
    public function create(): View
    {
        $categories = Category::orderBy('name')->get();
        $staffMembers = ITStaff::orderBy('name')->get();

        return view('sites.create', compact('categories', 'staffMembers'));
    }

    /**
     * Store a newly created site in storage.
     */
    public function store(StoreSiteRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $site = Site::create([
            'name' => $validated['name'],
            'category_id' => $validated['category_id'],
            'base_url' => $validated['base_url'],
            'description' => $validated['description'] ?? null,
            'responsible_person_id' => $validated['responsible_person_id'],
        ]);

        // Create associated pages
        foreach ($validated['pages'] as $path) {
            $site->pages()->create(['path' => $path]);
        }

        return redirect()->route('sites.index')
            ->with('success', 'Site created successfully. It will be included in the next checking cycle.');
    }

    /**
     * Show the form for editing the specified site.
     */
    public function edit(Site $site): View
    {
        $site->load('pages');
        $categories = Category::orderBy('name')->get();
        $staffMembers = ITStaff::orderBy('name')->get();

        return view('sites.edit', compact('site', 'categories', 'staffMembers'));
    }

    /**
     * Update the specified site in storage.
     */
    public function update(UpdateSiteRequest $request, Site $site): RedirectResponse
    {
        $validated = $request->validated();

        $site->update([
            'name' => $validated['name'],
            'category_id' => $validated['category_id'],
            'base_url' => $validated['base_url'],
            'description' => $validated['description'] ?? null,
            'responsible_person_id' => $validated['responsible_person_id'],
        ]);

        // Sync pages: delete existing and recreate
        $site->pages()->delete();
        foreach ($validated['pages'] as $path) {
            $site->pages()->create(['path' => $path]);
        }

        return redirect()->route('sites.index')
            ->with('success', 'Site updated successfully.');
    }

    /**
     * Remove the specified site from storage.
     * The confirmation prompt is handled on the frontend (JavaScript/Alpine.js).
     */
    public function destroy(Site $site): RedirectResponse
    {
        // Deleting the site will cascade delete pages via foreign key constraint
        $site->delete();

        return redirect()->route('sites.index')
            ->with('success', 'Site deleted successfully. It will be excluded from future checking cycles.');
    }
}
