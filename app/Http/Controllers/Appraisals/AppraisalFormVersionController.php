<?php

namespace App\Http\Controllers\Appraisals;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Appraisals\AppraisalForm;
use App\Models\Appraisals\AppraisalFormVersion;

class AppraisalFormVersionController extends Controller
{
    public function index(AppraisalForm $form)
    {
        $versions = AppraisalFormVersion::query()
            ->where('appraisal_form_id', $form->id)
            ->orderByDesc('version')
            ->get();

        $nextVersion = ($versions->max('version') ?? 0) + 1;

        return view('app.appraisals.versions.index', compact('form', 'versions', 'nextVersion'));
    }

    public function store(Request $request, AppraisalForm $form)
    {
        $data = $request->validate([
            'version' => 'required|integer|min:1',
            'effective_from' => 'nullable|date',
            'effective_to' => 'nullable|date|after_or_equal:effective_from',
            'is_active' => 'nullable|boolean',
        ]);

        $data['appraisal_form_id'] = $form->id;
        $data['is_active'] = (bool) ($data['is_active'] ?? false);

        $version = AppraisalFormVersion::create($data);

        // لو خلّيته active، سكّر باقي الـ versions
        if ($version->is_active) {
            AppraisalFormVersion::where('appraisal_form_id', $form->id)
                ->where('id', '!=', $version->id)
                ->update(['is_active' => false]);
        }

        return redirect()->route('appraisals.version-items.edit', $version->id)
            ->with('success', 'تم إنشاء Version. توا ربط البنود.');
    }

    public function activate(AppraisalFormVersion $version)
    {
        AppraisalFormVersion::where('appraisal_form_id', $version->appraisal_form_id)->update(['is_active' => false]);
        $version->update(['is_active' => true]);

        return back()->with('success', 'تم تفعيل الـ Version.');
    }
}
