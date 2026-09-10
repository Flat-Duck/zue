<?php

namespace App\Http\Controllers\Appraisals;

use App\Http\Controllers\Controller;
use App\Http\Requests\Appraisals\AppraisalFormStoreRequest;
use App\Http\Requests\Appraisals\AppraisalFormUpdateRequest;
use App\Models\Appraisals\AppraisalForm;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AppraisalFormController extends Controller
{
    public function index(): View
    {
        $forms = AppraisalForm::query()->orderBy('code')->get();

        return view('app.appraisals.forms.index', compact('forms'));
    }

    public function create(): View
    {
        return view('app.appraisals.forms.create');
    }

    public function store(AppraisalFormStoreRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $data['is_active'] = (bool) ($data['is_active'] ?? false);

        $form = AppraisalForm::create($data);

        return redirect()->route('appraisals.versions.index', $form->id)
            ->with('success', 'تم إنشاء النموذج. توا أنشئ Version.');
    }

    public function edit(AppraisalForm $form): View
    {
        return view('app.appraisals.forms.edit', compact('form'));
    }

    public function update(AppraisalFormUpdateRequest $request, AppraisalForm $form): RedirectResponse
    {
        $data = $request->validated();

        $data['is_active'] = (bool) ($data['is_active'] ?? false);
        $form->update($data);

        return back()->with('success', 'تم تحديث النموذج.');
    }
}
