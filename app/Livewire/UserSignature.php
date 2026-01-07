<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;
use App\Models\Signature;
use App\Services\SignatureService;


class UserSignature extends Component
{
    use WithFileUploads;

    /** Canvas base64 data */
    public string $signatureData = '';

    /** Uploaded file (TemporaryUploadedFile) */
    public $signatureFile;

    /**
     * Save signature from Canvas (keep background as-is)
     */
    public function save(): void
    {
        if (empty($this->signatureData)) {
            session()->flash('error', 'No signature data.');
            return;
        }

        $base64Image = preg_replace('#^data:image/\w+;base64,#i', '', $this->signatureData);
        $image = base64_decode($base64Image);

        if ($image === false) {
            session()->flash('error', 'Invalid signature data.');
            return;
        }

        $fileName = 'signature_' . auth()->id() . '_' . time() . '.png';
        $filePath = 'signatures/' . $fileName;

        Storage::disk('public')->put($filePath, $image);

        $this->storeOrUpdateSignature($filePath);

        session()->flash('success', 'Signature saved!');
        $this->dispatch('signature-saved');
    }

    /**
     * Save signature from uploaded file
     * (remove white background → transparent PNG)
     */
    /**
     * Save signature from uploaded file
     * (remove white background → transparent PNG)
     */
    public function saveUpload(SignatureService $signatureService): void
    {
        $this->validate([
            'signatureFile' => 'required|image|mimes:png,jpg,jpeg,webp|max:2048',
        ]);

        $signatureService->saveSignature(auth()->user(), $this->signatureFile);

        // reset the temp upload
        $this->reset('signatureFile');

        session()->flash('success', 'Signature uploaded with transparent background!');
        $this->dispatch('signature-saved');
    }

    /**
     * Replace existing signature (delete old file), then upsert DB row
     */
    protected function storeOrUpdateSignature(string $filePath): void
    {
        $existing = Signature::where('user_id', auth()->id())->first();

        if ($existing && $existing->image_path && Storage::disk('public')->exists($existing->image_path)) {
            Storage::disk('public')->delete($existing->image_path);
        }

        Signature::updateOrCreate(
            ['user_id' => auth()->id()],
            ['image_path' => $filePath]
        );
    }

    public function render()
    {
        return view('livewire.user-signature');
    }
}
