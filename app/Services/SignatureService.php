<?php

namespace App\Services;

use App\Models\Signature;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class SignatureService
{
    protected $imageProcessingService;

    public function __construct(ImageProcessingService $imageProcessingService)
    {
        $this->imageProcessingService = $imageProcessingService;
    }

    /**
     * Process, store, and save/update signature for a user.
     */
    public function saveSignature(User $user, UploadedFile $file): Signature
    {
        // 1. Process Image (remove white background)
        $binary = file_get_contents($file->getRealPath());
        $processed = $this->imageProcessingService->makeWhiteTransparent($binary);

        // 2. Clear old signature if exists
        if ($user->signature && Storage::disk('public')->exists($user->signature->image_path)) {
            Storage::disk('public')->delete($user->signature->image_path);
        }

        // 3. Store new file
        $fileName = 'signature_'.$user->id.'_'.time().'.png';
        $filePath = 'signatures/'.$fileName;
        Storage::disk('public')->put($filePath, $processed);

        // 4. Update Database
        return Signature::updateOrCreate(
            ['user_id' => $user->id],
            ['image_path' => $filePath]
        );
    }
}
