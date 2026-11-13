<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSiteRequest;
use App\Http\Requests\UpdateSiteRequest;
use App\Models\Category;
use App\Models\ITStaff;
use App\Models\Page;
use App\Models\Site;
use App\Services\MonitoringServiceInterface;
use App\Services\PublicStatusService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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

        // ?show=all lists every matching site on one page (still a paginator, so the view stays the same)
        $showAll = request('show') === 'all';
        $perPage = $showAll ? max(1, (clone $query)->count()) : 10;
        $sites = $query->paginate($perPage)->withQueryString();
        $categories = Category::orderBy('name')->get();
        $staffMembers = ITStaff::orderBy('name')->get();

        return view('sites.index', compact('sites', 'categories', 'staffMembers', 'showAll'));
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
            'is_public' => (bool) ($validated['is_public'] ?? false),
            'public_name' => $validated['public_name'] ?? null,
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
    public function update(UpdateSiteRequest $request, Site $site, MonitoringServiceInterface $monitoringService): RedirectResponse
    {
        $validated = $request->validated();

        $site->update([
            'name' => $validated['name'],
            'category_id' => $validated['category_id'],
            'base_url' => $validated['base_url'],
            'description' => $validated['description'] ?? null,
            'responsible_person_id' => $validated['responsible_person_id'],
            'is_public' => (bool) ($validated['is_public'] ?? false),
            'public_name' => $validated['public_name'] ?? null,
        ]);

        if (array_key_exists('is_monitored', $validated)) {
            $monitoringService->setMonitored($site, (bool) $validated['is_monitored']);
        }

        // Sync pages: only add new and remove deleted, keep unchanged ones to preserve check history
        $existingPages = $site->pages()->pluck('path', 'id')->toArray();
        $submittedPaths = $validated['pages'];

        // Delete pages that are no longer in the submitted list
        $pathsToKeep = array_intersect($existingPages, $submittedPaths);
        $idsToDelete = array_diff_key($existingPages, $pathsToKeep);

        if (!empty($idsToDelete)) {
            $site->pages()->whereIn('id', array_keys($idsToDelete))->delete();
        }

        // Add new pages that don't already exist
        $existingPaths = array_values($pathsToKeep);
        $newPaths = array_diff($submittedPaths, $existingPaths);

        foreach ($newPaths as $path) {
            $site->pages()->create(['path' => $path]);
        }

        return redirect()->route('sites.index')
            ->with('success', 'Site updated successfully.');
    }

    /**
     * Remove the specified site from storage.
     * The confirmation prompt is handled on the frontend (JavaScript/Alpine.js).
     */
    /**
     * Apply one action to several selected websites from the website manager list.
     * Each site goes through the same path as its single-site equivalent: delete(), or
     * MonitoringService::setMonitored() so open outages are closed the usual way.
     */
    public function bulk(Request $request, MonitoringServiceInterface $monitoringService): RedirectResponse
    {
        $validated = $request->validate([
            'action' => ['required', 'in:delete,monitor_on,monitor_off,make_public,make_private'],
            'ids' => ['required', 'array', 'min:1', 'max:1000'],
            'ids.*' => ['integer', 'distinct', 'exists:sites,id'],
        ], [
            'ids.required' => 'Select at least one website, then choose an action.',
            'ids.*.exists' => 'One of the selected websites no longer exists. Reload the page and try again.',
            'action.in' => 'Choose one of the listed actions.',
        ]);

        $sites = Site::whereIn('id', $validated['ids'])->get();
        $count = $sites->count();
        $websites = $count . ' ' . Str::plural('website', $count);
        $one = $count === 1;

        switch ($validated['action']) {
            case 'delete':
                // Pages and check history cascade, as with a single delete
                DB::transaction(fn () => $sites->each->delete());
                $message = "Deleted {$websites}. " . ($one ? 'It is' : 'They are') . ' left out of future checking cycles.';
                break;
            case 'monitor_on':
            case 'monitor_off':
                $on = $validated['action'] === 'monitor_on';
                $sites->each(fn (Site $site) => $monitoringService->setMonitored($site, $on));
                $message = $on
                    ? "Monitoring turned on for {$websites}."
                    : "Monitoring turned off for {$websites}. " . ($one ? 'Its open outage is' : 'Their open outages are') . ' closed.';
                break;
            default:
                $public = $validated['action'] === 'make_public';
                Site::whereIn('id', $sites->pluck('id'))->update(['is_public' => $public]);
                $message = $public
                    ? "{$websites} now " . ($one ? 'shows' : 'show') . ' on the public status page.'
                    : "{$websites} " . ($one ? 'is' : 'are') . ' now hidden from the public status page.';
        }

        PublicStatusService::forget();

        return back()->with('success', ucfirst($message));
    }

    public function destroy(Site $site): RedirectResponse
    {
        // Deleting the site will cascade delete pages via foreign key constraint
        $site->delete();

        return redirect()->route('sites.index')
            ->with('success', 'Site deleted successfully. It will be excluded from future checking cycles.');
    }
}
