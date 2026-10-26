<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MenuItem;

class MenuController extends Controller
{
    public function index()
    {
        return response()->json(MenuItem::orderBy('category')->orderBy('name')->get());
    }

    public function store(Request $request)
    {
        // 1. Kunin lang yung mga tamang fields at i-validate
        $validated = $request->validate([
            'name' => 'required|string',
            'description' => 'nullable|string',
            'category' => 'required|string',
            'price' => 'required|numeric',
            'daily_stock' => 'required|integer',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = 'menu_' . time() . '_' . rand(1000,9999) . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('assets/uploads/menu'), $filename);
            $imagePath = 'assets/uploads/menu/' . $filename;
        }

        // 2. I-save lang ang mga tamang fields
        $item = MenuItem::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? '',
            'category' => $validated['category'],
            'price' => $validated['price'],
            'daily_stock' => $validated['daily_stock'],
            'remaining_stock' => $validated['daily_stock'],
            'is_available' => true,
            'image_path' => $imagePath
        ]);

        return response()->json(['success' => true, 'id' => $item->id]);
    }

    public function update(Request $request, $id)
    {
        $item = MenuItem::findOrFail($id);
        
        // 1. Kunin lang ang tamang fields
        $validated = $request->validate([
            'name' => 'required|string',
            'description' => 'nullable|string',
            'category' => 'required|string',
            'price' => 'required|numeric',
            'daily_stock' => 'required|integer',
        ]);

        // 2. I-update yung fields
        $item->name = $validated['name'];
        $item->description = $validated['description'] ?? '';
        $item->category = $validated['category'];
        $item->price = $validated['price'];
        $item->daily_stock = $validated['daily_stock'];

        // 3. Kung may bagong image, i-upload at i-override ang image_path
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = 'menu_' . time() . '_' . rand(1000,9999) . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('assets/uploads/menu'), $filename);
            $item->image_path = 'assets/uploads/menu/' . $filename;
        }

        $item->save();

        return response()->json(['success' => true]);
    }

    public function destroy($id)
    {
        MenuItem::findOrFail($id)->delete();
        return response()->json(['success' => true]);
    }

    public function resetStock()
    {
        $items = MenuItem::all();
        foreach ($items as $item) {
            $item->update([
                'remaining_stock' => $item->daily_stock,
                'is_available' => $item->daily_stock > 0 ? true : false
            ]);
        }
        return response()->json(['success' => true]);
    }
}