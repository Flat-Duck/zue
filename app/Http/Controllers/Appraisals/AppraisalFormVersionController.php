<?php

namespace App\Http\Controllers\Appraisals;

use App\Http\Controllers\Controller;
use App\Http\Requests\Appraisals\AppraisalFormVersionStoreRequest;
use App\Models\Appraisals\AppraisalForm;
use App\Models\Appraisals\AppraisalFormVersion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AppraisalFormVersionController extends Controller
{
    public function index(AppraisalForm $form): View
    {
        $versions = AppraisalFormVersion::query()
            ->where('appraisal_form_id', $form->id)
            ->orderByDesc('version')
            ->get();

        $nextVersion = ($versions->max('version') ?? 0) + 1;

        return view('app.appraisals.versions.index', compact('form', 'versions', 'nextVersion'));
    }

    public function store(AppraisalFormVersionStoreRequest $request, AppraisalForm $form)
    {
        $data = $request->validated();

        $data['appraisal_form_id'] = $form->id;
        $data['is_active'] = (bool) ($data['is_active'] ?? false);

        $version = DB::transaction(function () use ($data, $form): AppraisalFormVersion {
            if ($data['is_active']) {
                AppraisalFormVersion::query()
                    ->where('appraisal_form_id', $form->id)
                    ->lockForUpdate()
                    ->get();
            }

            $version = AppraisalFormVersion::create($data);

            if ($version->is_active) {
                AppraisalFormVersion::query()
                    ->where('appraisal_form_id', $form->id)
                    ->whereKeyNot($version->id)
                    ->update(['is_active' => false]);
            }

            return $version;
        });

        return redirect()->route('appraisals.version-items.edit', $version->id)
            ->with('success', 'تم إنشاء Version. توا ربط البنود.');
    }

    public function activate(AppraisalFormVersion $version): RedirectResponse
    {
        DB::transaction(function () use ($version): void {
            AppraisalFormVersion::query()
                ->where('appraisal_form_id', $version->appraisal_form_id)
                ->lockForUpdate()
                ->get();

            AppraisalFormVersion::where('appraisal_form_id', $version->appraisal_form_id)->update(['is_active' => false]);
            $version->update(['is_active' => true]);
        });

        return back()->with('success', 'تم تفعيل الـ Version.');
    }
}
