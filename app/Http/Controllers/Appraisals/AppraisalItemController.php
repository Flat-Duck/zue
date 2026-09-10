<?php

namespace App\Http\Controllers\Appraisals;

use App\Http\Controllers\Controller;
use App\Http\Requests\Appraisals\AppraisalItemStoreRequest;
use App\Http\Requests\Appraisals\AppraisalItemUpdateRequest;
use App\Models\Appraisals\AppraisalItem;
use Illuminate\Http\Request;

class AppraisalItemController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->get('q', ''));

        $items = AppraisalItem::query()
            ->when($q !== '', fn ($qq) => $qq->where('key', 'like', "%{$q}%")
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

    public function store(AppraisalItemStoreRequest $request)
    {
        $data = $request->validated();

        AppraisalItem::create($data);

        return redirect()->route('appraisals.items.index')->with('success', 'تم إضافة البند.');
    }

    public function edit(AppraisalItem $item)
    {
        return view('app.appraisals.items.edit', compact('item'));
    }

    public function update(AppraisalItemUpdateRequest $request, AppraisalItem $item)
    {
        $data = $request->validated();

        $item->update($data);

        return back()->with('success', 'تم تحديث البند.');
    }
}
