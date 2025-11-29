<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;
use App\Models\Signature;

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
    public function saveUpload(): void
    {
        $this->validate([
            'signatureFile' => 'required|image|mimes:png,jpg,jpeg,webp|max:2048',
        ]);

        // Read raw file
        $binary = @file_get_contents($this->signatureFile->getRealPath());
        if ($binary === false) {
            session()->flash('error', 'Failed to read uploaded file.');
            return;
        }

        // Make white transparent (PNG)
        $processed = $this->makeWhiteTransparent($binary);

        $fileName = 'signature_' . auth()->id() . '_' . time() . '.png';
        $filePath = 'signatures/' . $fileName;

        Storage::disk('public')->put($filePath, $processed);

        $this->storeOrUpdateSignature($filePath);

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

    /**
     * Convert white / near-white pixels to transparent
     * Prefer Imagick if available; fallback to GD
     */
protected function makeWhiteTransparent(string $binary): string
{
    // ====== 1) Imagick (مُفضّل) ======
    if (extension_loaded('imagick')) {
        try {
            $img = new \Imagick();
            $img->readImageBlob($binary);
            $img->setImageColorspace(\Imagick::COLORSPACE_SRGB);
            $img->setImageAlphaChannel(\Imagick::ALPHACHANNEL_SET);
            $img->setImageFormat('png');

            $w = $img->getImageWidth();
            $h = $img->getImageHeight();

            // نعيّن لون الخلفية من 4 زوايا (متوسطهم)
            $points = [
                [5, 5],
                [max(0, $w - 6), 5],
                [5, max(0, $h - 6)],
                [max(0, $w - 6), max(0, $h - 6)],
            ];

            $sum = ['r'=>0,'g'=>0,'b'=>0];
            foreach ($points as [$x, $y]) {
                $px = $img->getImagePixelColor($x, $y)->getColor(true); // 0..1
                $sum['r'] += $px['r'];
                $sum['g'] += $px['g'];
                $sum['b'] += $px['b'];
            }
            $avg = [
                'r' => (int)round(($sum['r'] / 4) * 255),
                'g' => (int)round(($sum['g'] / 4) * 255),
                'b' => (int)round(($sum['b'] / 4) * 255),
            ];

            $bg = new \ImagickPixel(sprintf('rgb(%d,%d,%d)', $avg['r'], $avg['g'], $avg['b']));

            // fuzz لازم بقيمة QuantumRange (مش نسبة مئوية)
            $range  = \Imagick::getQuantumRange();
            $quant  = $range['quantumRangeLong'] ?? 65535;
            // للصور اللي فيها ظل/بيج خفيف، 20–30% ممتاز. إبدأ بـ 0.25
            $fuzzRatio = 0.25;
            $fuzz      = (int)round($quant * $fuzzRatio);

            // امسح الخلفية (وكل ما يقرب منها) إلى شفاف
            $img->setImageColorFuzz($fuzz);
            $img->transparentPaintImage($bg, 0.0, $fuzz, false);

            // (اختياري) تمريرة إضافية على الأبيض الخالص لو بقي منه شيء
            $img->transparentPaintImage(new \ImagickPixel('white'), 0.0, (int)round($quant * 0.12), false);

            // (اختياري) تشذيب حواف البيج بعد الشفافية
            // $img->trimImage(1);

            $out = $img->getImageBlob();
            $img->clear(); $img->destroy();
            return $out;
        } catch (\Throwable $e) {
            // ينزل على GD
        }
    }

    // ====== 2) GD fallback ======
    $gd = @imagecreatefromstring($binary);
    if (!$gd) return $binary;

    if (function_exists('imagepalettetotruecolor')) {
        @imagepalettetotruecolor($gd);
    }
    imagesavealpha($gd, true);
    imagealphablending($gd, false);

    $w = imagesx($gd); $h = imagesy($gd);

    // عيّن لون الخلفية من الزوايا
    $sample = function($x,$y) use($gd){
        $c = imagecolorat($gd,$x,$y);
        return [ ($c>>16)&0xFF, ($c>>8)&0xFF, $c&0xFF ];
    };
    $p1 = $sample(5,5);
    $p2 = $sample(max(0,$w-6),5);
    $p3 = $sample(5,max(0,$h-6));
    $p4 = $sample(max(0,$w-6),max(0,$h-6));

    $bgR = (int)round(($p1[0]+$p2[0]+$p3[0]+$p4[0])/4);
    $bgG = (int)round(($p1[1]+$p2[1]+$p3[1]+$p4[1])/4);
    $bgB = (int)round(($p1[2]+$p2[2]+$p3[2]+$p4[2])/4);

    // اعتبر قريب من الخلفية إذا كانت المسافة اللونية صغيرة
    $deltaThreshold = 45; // زيدها 55–70 لو فيه ظل أقوى

    for ($y=0; $y<$h; $y++) {
        for ($x=0; $x<$w; $x++) {
            $rgb = imagecolorat($gd, $x, $y);
            $r = ($rgb>>16)&0xFF; $g = ($rgb>>8)&0xFF; $b = $rgb&0xFF;

            $dr = $r - $bgR; $dg = $g - $bgG; $db = $b - $bgB;
            $delta = sqrt($dr*$dr + $dg*$dg + $db*$db);

            if ($delta <= $deltaThreshold) {
                $transparent = imagecolorallocatealpha($gd, 255, 255, 255, 127);
                imagesetpixel($gd, $x, $y, $transparent);
            }
        }
    }

    ob_start(); imagepng($gd); $out = ob_get_clean(); imagedestroy($gd);
    return $out ?: $binary;
}


    public function render()
    {
        return view('livewire.user-signature');
    }
}


// namespace App\Livewire;

// use Livewire\WithFileUploads;
// use Livewire\Component;
// use Illuminate\Support\Facades\Storage;
// use App\Models\Signature;
// use Intervention\Image\ImageManagerStatic as Image;
// class UserSignature extends Component
// {
//     use WithFileUploads;

//     public string $signatureData = '';   // للـcanvas
//     public $signatureFile;               // للرفع من الجهاز (TemporaryUploadedFile)

//     protected function rules(): array
//     {
//         return [
//             'signatureFile' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:2048',
//         ];
//     }

//     public function save()
//     {
//         // الحفظ من الـcanvas (نفس اللي عندك)
//         $base64Image = preg_replace('#^data:image/\w+;base64,#i', '', $this->signatureData);
//         $image = base64_decode($base64Image);

//         $fileName = 'signature_' . auth()->id() . '_' . time() . '.png';
//         $filePath = 'signatures/' . $fileName;

//         Storage::disk('public')->put($filePath, $image);

//         $this->storeOrUpdateSignature($filePath);

//         session()->flash('success', 'Signature saved!');
//         $this->dispatch('signature-saved');
//     }

// //     public function saveUpload()
//     {
//         // الحفظ من رفع ملف
//         $this->validate();

//         if (!$this->signatureFile) {
//             session()->flash('error', 'Please choose an image file.');
//             return;
//         }

//         // اسم وامتداد الملف
//         $ext = $this->signatureFile->getClientOriginalExtension();
//         $fileName = 'signature_' . auth()->id() . '_' . time() . '.' . $ext;
//         $filePath = $this->signatureFile->storeAs('signatures', $fileName, 'public');

//         $this->storeOrUpdateSignature($filePath);

//         // نفرّغ الاختيار
//         $this->reset('signatureFile');

//         session()->flash('success', 'Signature uploaded!');
//         $this->dispatch('signature-saved');
//     }

//     protected function storeOrUpdateSignature(string $filePath): void
//     {
//         // امسح القديمة لو عنده توقيع سابق
//         $existing = Signature::where('user_id', auth()->id())->first();
//         if ($existing && $existing->image_path && Storage::disk('public')->exists($existing->image_path)) {
//             Storage::disk('public')->delete($existing->image_path);
//         }

//         Signature::updateOrCreate(
//             ['user_id' => auth()->id()],
//             ['image_path' => $filePath]
//         );
//     }


// public function saveUpload()
// {
//     $this->validate([
//         'signatureFile' => 'required|image|mimes:png,jpg,jpeg,webp|max:2048',
//     ]);

//     // نقرأ محتوى الملف المرفوع
//     $contents = file_get_contents($this->signatureFile->getRealPath());

//     // 🧼 إزالة الخلفية البيضاء وجعلها شفافة (PNG)
//     $processedPng = $this->makeWhiteTransparent($contents);

//     // اسم/مسار الحفظ (دائمًا PNG)
//     $fileName = 'signature_' . auth()->id() . '_' . time() . '.png';
//     $filePath = 'signatures/' . $fileName;

//     Storage::disk('public')->put($filePath, $processedPng);

//     $this->storeOrUpdateSignature($filePath);
//     $this->reset('signatureFile');

//     session()->flash('success', 'Signature uploaded with transparent background!');
//     $this->dispatch('signature-saved');
// }

// /**
//  * يحوّل كل بكسل أبيض/شبه أبيض لشفاف.
//  * يفضّل Imagick لو متاح، وإلا نستعمل GD كبديل.
//  */
// protected function makeWhiteTransparent(string $binary): string
// {
//     // حاول بـ Imagick أولًا
//     if (extension_loaded('imagick')) {
//         $img = new Imagick();
//         $img->readImageBlob($binary);
//         $img->setImageAlphaChannel(\Imagick::ALPHACHANNEL_SET);
//         $img->setImageFormat('png');

//         // نسبة التسامح (fuzz) للأبيض ≈ 10% (عدّلها لو لزم)
//         // ملاحظة: بعض إصدارات Imagick تستخدم setImageColorFuzz بالقيم الكمية
//         // الحل الأبسط: جرب قيمة "fuzz" مباشرة في transparentPaintImage:
//         $white = new \ImagickPixel('white');
//         $img->transparentPaintImage($white, 0.0, 0.10, false); // 0.10 ~= 10% fuzz

//         // تأكيد قناة ألفا
//         $img->setImageAlphaChannel(\Imagick::ALPHACHANNEL_SET);

//         $blob = $img->getImageBlob();
//         $img->clear();
//         $img->destroy();
//         return $blob;
//     }

//     // بديل GD: تفريغ الأبيض يدويًا
//     $gd = imagecreatefromstring($binary);
//     if (!$gd) {
//         // لو فشل نرجّع الملف كما هو
//         return $binary;
//     }

//     imagesavealpha($gd, true);
//     imagealphablending($gd, false);

//     $width  = imagesx($gd);
//     $height = imagesy($gd);

//     // العتبة: اعتبر أي بكسل RGB > 240 "أبيض"
//     $threshold = 240;

//     for ($y = 0; $y < $height; $y++) {
//         for ($x = 0; $x < $width; $x++) {
//             $index = imagecolorat($gd, $x, $y);
//             $r = ($index >> 16) & 0xFF;
//             $g = ($index >> 8) & 0xFF;
//             $b = $index & 0xFF;

//             if ($r >= $threshold && $g >= $threshold && $b >= $threshold) {
//                 // شفافية كاملة
//                 $transparent = imagecolorallocatealpha($gd, 255, 255, 255, 127);
//                 imagesetpixel($gd, $x, $y, $transparent);
//             }
//         }
//     }

//     // ارجع PNG
//     ob_start();
//     imagepng($gd);
//     $out = ob_get_clean();
//     imagedestroy($gd);

//     return $out;
// }

//     public function render()
//     {
//         return view('livewire.user-signature');
//     }
// }

// namespace App\Livewire;

// use Livewire\Component;

// class UserSignture extends Component
// {
//     public function render()
//     {
//         return view('livewire.user-signture');
//     }
// }
// namespace App\Livewire;

// use Livewire\Component;
// use Illuminate\Support\Facades\Storage;
// use App\Models\Signature;

// class UserSignature extends Component
// {
//     public $signatureData;

//     public function save()
//     {
//         $base64Image = preg_replace('#^data:image/\w+;base64,#i', '', $this->signatureData);
//         $image = base64_decode($base64Image);

//         $fileName = 'signature_' . auth()->id() . '_' . time() . '.png';
//         $filePath = 'signatures/' . $fileName;

//         Storage::disk('public')->put($filePath, $image);

//         Signature::updateOrCreate(
//             ['user_id' => auth()->id()],
//             ['image_path' => $filePath]
//         );

//         session()->flash('success', 'Signature saved!');
//         $this->dispatch('signature-saved');
//     }

//     public function render()
//     {
//         return view('livewire.user-signature');
//     }
// } -->
