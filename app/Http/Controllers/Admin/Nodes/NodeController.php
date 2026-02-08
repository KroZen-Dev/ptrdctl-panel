<?php

namespace Pterodactyl\Http\Controllers\Admin\Nodes;

use Illuminate\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\Location;
use Spatie\QueryBuilder\QueryBuilder;
use Pterodactyl\Http\Controllers\Controller;

class NodeController extends Controller
{
    /**
     * Returns a listing of nodes on the system.
     */
    public function index(Request $request): View
    {
        $nodes = QueryBuilder::for(
            Node::query()->with('location')->withCount('servers')
        )
            ->allowedFilters(['uuid', 'name'])
            ->allowedSorts(['id'])
            ->paginate(25);

        $locations = Location::all();

        return view('admin.nodes.index', compact('nodes', 'locations'));
    }

    /**
     * Clone an existing node.
     */
    public function clone(Request $request, Node $node): RedirectResponse
    {
        // Validate form data
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'location_id' => 'required|exists:locations,id',
            'fqdn' => 'required|string',
            'description' => 'nullable|string',
            // Add other fields if needed (memory, disk, etc.)
        ]);

        // Create a new node based on the existing one
        $newNode = new Node();
        $newNode->fill([
            'name' => $validated['name'],
            'location_id' => $validated['location_id'],
            'fqdn' => $validated['fqdn'],
            'description' => $validated['description'],
            // Copy other fields from the original node (except id, uuid, created_at)
            'scheme' => $node->scheme,
            'memory' => $node->memory,
            'disk' => $node->disk,
            // ... add other fields as necessary
        ]);
        $newNode->save();

        // Optionally: generate a new daemon_token or other unique fields
        // $newNode->daemon_token = Str::random(32); // Example

        return redirect()->route('admin.nodes.view', $newNode->id)
            ->with('success', 'Node cloned successfully.');
    }
}
