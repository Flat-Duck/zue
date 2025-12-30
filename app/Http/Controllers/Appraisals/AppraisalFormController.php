<?php

namespace App\Http\Controllers\Appraisals;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Appraisals\AppraisalForm;

class AppraisalFormController extends Controller
{
    public function index()
    {
        $forms = AppraisalForm::query()->orderBy('code')->get();
        return view('app.appraisals.forms.index', compact('forms'));
    }

    public function create()
    {
        return view('app.appraisals.forms.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code' => 'required|string|max:255|unique:appraisal_forms,code',
            'name_ar' => 'required|string|max:255',
            'is_active' => 'nullable|boolean',
        ]);

        $data['is_active'] = (bool) ($data['is_active'] ?? false);

        $form = AppraisalForm::create($data);

        return redirect()->route('appraisals.versions.index', $form->id)
            ->with('success', 'تم إنشاء النموذج. توا أنشئ Version.');
    }

    public function edit(AppraisalForm $form)
    {
        return view('app.appraisals.forms.edit', compact('form'));
    }

    public function update(Request $request, AppraisalForm $form)
    {
        $data = $request->validate([
            'code' => 'required|string|max:255|unique:appraisal_forms,code,' . $form->id,
            'name_ar' => 'required|string|max:255',
            'is_active' => 'nullable|boolean',
        ]);

        $data['is_active'] = (bool) ($data['is_active'] ?? false);
        $form->update($data);

        return back()->with('success', 'تم تحديث النموذج.');
    }
}
