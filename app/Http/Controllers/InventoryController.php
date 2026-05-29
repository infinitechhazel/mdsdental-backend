<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Inventory;


class InventoryController extends Controller
{
    public function index()
    {
        return response()->json(Inventory::all(), 200);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'item_name' => 'required|string|max:255',
            'category' => 'nullable|string|max:255',
            'quantity' => 'required|integer|min:0',
            'unit' => 'nullable|string|max:50',
            'expiry_date' => 'nullable|date',
            'cost_price' => 'nullable|numeric',
            'selling_price' => 'nullable|numeric',
        ]);

        $inventory = Inventory::create($validated);
        return response()->json($inventory, 201);
    }

    public function show($id)
    {
        $inventory = Inventory::findOrFail($id);
        return response()->json($inventory, 200);
    }

    public function update(Request $request, $id)
    {
        $inventory = Inventory::findOrFail($id);
        $inventory->update($request->all());
        return response()->json($inventory, 200);
    }

    public function destroy($id)
    {
        Inventory::destroy($id);
        return response()->json(null, 204);
    }
}
