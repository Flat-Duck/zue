<?php

namespace App\Http\Controllers\Appraisals;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Appraisals\AppraisalFormVersion;
use App\Models\Appraisals\AppraisalFormVersionItem;
use App\Models\Appraisals\AppraisalItem;

class AppraisalFormVersionItemsController extends Controller
{
    public function edit(AppraisalFormVersion $version)
    {
        $version->load('form');

        $versionItems = AppraisalFormVersionItem::with('item')
            ->where('appraisal_form_version_id', $version->id)
            ->orderBy('sort_order')
            ->get();

        $items = AppraisalItem::query()->orderBy('default_section')->orderBy('key')->get();

        return view('app.appraisals.version_items.edit', compact('version', 'versionItems', 'items'));
    }

    public function addItem(Request $request, AppraisalFormVersion $version)
    {
        $data = $request->validate([
            'item_id' => 'required|exists:appraisal_items,id',
            'max_score_override' => 'required|integer|min:0',
            'sort_order' => 'required|integer|min:1',
            'is_required' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'label_override' => 'nullable|string|max:255',
            'section_override' => 'nullable|in:job_performance,personal_traits,initiative',
        ]);

        AppraisalFormVersionItem::updateOrCreate(
            [
                'appraisal_form_version_id' => $version->id,
                'item_id' => $data['item_id'],
            ],
            [
                'max_score_override' => $data['max_score_override'],
                'sort_order' => $data['sort_order'],
                'is_required' => (bool) ($data['is_required'] ?? false),
                'is_active' => (bool) ($data['is_active'] ?? true),
                'label_override' => $data['label_override'] ?? null,
                'section_override' => $data['section_override'] ?? null,
            ]
        );

        return back()->with('success', 'تمت إضافة البند للـ Version.');
    }

    public function bulkUpdate(Request $request, AppraisalFormVersion $version)
    {
        $data = $request->validate([
            'rows' => 'required|array',
            'rows.*.id' => 'required|exists:appraisal_form_version_items,id',
            'rows.*.max_score_override' => 'required|integer|min:0',
            'rows.*.sort_order' => 'required|integer|min:1',
            'rows.*.is_required' => 'nullable|boolean',
            'rows.*.is_active' => 'nullable|boolean',
            'rows.*.label_override' => 'nullable|string|max:255',
            'rows.*.section_override' => 'nullable|in:job_performance,personal_traits,initiative',
        ]);

        foreach ($data['rows'] as $row) {
            $vi = AppraisalFormVersionItem::where('appraisal_form_version_id', $version->id)
                ->where('id', $row['id'])
                ->first();

            if (!$vi)
                continue;

            $vi->update([
                'max_score_override' => $row['max_score_override'],
                'sort_order' => $row['sort_order'],
                'is_required' => (bool) ($row['is_required'] ?? false),
                'is_active' => (bool) ($row['is_active'] ?? false),
                'label_override' => $row['label_override'] ?? null,
                'section_override' => $row['section_override'] ?? null,
            ]);
        }

        return back()->with('success', 'تم تحديث البنود.');
    }

    public function destroy(AppraisalFormVersion $version, AppraisalFormVersionItem $versionItem)
    {
        if ($versionItem->appraisal_form_version_id !== $version->id)
            abort(404);

        $versionItem->delete();

        return back()->with('success', 'تم حذف البند من الـ Version.');
    }
}
