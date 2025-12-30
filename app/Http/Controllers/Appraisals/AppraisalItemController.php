<?php

namespace App\Http\Controllers\Appraisals;

use App\Http\Controllers\Controller;
use App\Models\Appraisals\AppraisalItem;
use Illuminate\Http\Request;

class AppraisalItemController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->get('q', ''));

        $items = AppraisalItem::query()
            ->when($q !== '', fn($qq) => $qq->where('key', 'like', "%{$q}%")
                ->orWhere('default_label', 'like', "%{$q}%"))
            ->orderBy('default_section')
            ->orderBy('key')
            ->paginate(50)
            ->withQueryString();

        return view('app.appraisals.items.index', compact('items', 'q'));
    }

    public function create()
    {
        return view('app.appraisals.items.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'key' => 'required|string|max:255|unique:appraisal_items,key',
            'default_section' => 'required|in:job_performance,personal_traits,initiative',
            'default_label' => 'required|string|max:255',
        ]);

        AppraisalItem::create($data);

        return redirect()->route('appraisals.items.index')->with('success', 'تم إضافة البند.');
    }

    public function edit(AppraisalItem $item)
    {
        return view('app.appraisals.items.edit', compact('item'));
    }

    public function update(Request $request, AppraisalItem $item)
    {
        $data = $request->validate([
            'key' => 'required|string|max:255|unique:appraisal_items,key,' . $item->id,
            'default_section' => 'required|in:job_performance,personal_traits,initiative',
            'default_label' => 'required|string|max:255',
        ]);

        $item->update($data);

        return back()->with('success', 'تم تحديث البند.');
    }
}
