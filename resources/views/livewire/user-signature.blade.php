<div class="row row-cards">
    <!-- العمود الأيسر: توقيع بالـCanvas -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <h3 class="card-title">@lang('ui.add_your_signature')</h3>
                <p class="card-subtitle">@lang('ui.please_use_touch_screen_or_electronic')</p>
                <div class="mb-3">
                    <label class="form-label">{{ __('Signature') }}</label>
                    <div class="signature position-relative">
                        <div class="position-absolute top-0 end-0 p-2">
                            <div class="btn btn-icon" id="signature-advanced-clear" title="@lang('ui.clear_signature')">
                                <i class="ti ti-trash"></i>
                            </div>
                        </div>
                        <canvas id="signature-advanced" width="400" height="400" class="signature-canvas"></canvas>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <div class="row align-items-center">
                    <div class="col"><strong>@lang('ui.signed_by')</strong> {{ auth()->user()->name }}</div>
                    <div class="col-auto">
                        <button type="button" class="btn btn-primary w-100" id="signature-submit-png">@lang('ui.save_signature')</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- العمود الأيمن: رفع صورة توقيع -->
    <div class="col-md-6">
        <div class="card" wire:ignore.self>
            <div class="card-header">
                <h3 class="card-title">{{ __('Current Active Signature') }}</h3>
            </div>
            <div class="card-body">
                <div class="m-0 p-0 mb-3">
                    @php
                        $currentPath = optional(auth()->user()->signature)->image_path;
                    @endphp
                    @if($currentPath)
                        <img src="{{ asset('storage/' . $currentPath) }}" class="img-fluid rounded border"
                            alt="@lang('ui.current_signature')" />
                    @else
                        <div class="text-muted">@lang('ui.no_signature_on_file')</div>
                    @endif
                </div>

                <div class="mb-3">
                    <input type="file" class="form-control @error('signatureFile') is-invalid @enderror"
                        accept="image/png,image/jpeg,image/webp" wire:model="signatureFile">
                    @error('signatureFile')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div wire:loading wire:target="signatureFile" class="form-text mt-1">@lang('ui.uploading')</div>
                </div>

                @if ($signatureFile)
                    <div class="mb-3">
                        <div class="form-label">@lang('ui.preview')</div>
                        <img src="{{ $signatureFile->temporaryUrl() }}" class="img-fluid rounded border" alt="@lang('ui.preview')">
                    </div>
                @endif
            </div>
            <div class="card-footer">
                <div class="row align-items-center">
                    <div class="col"><strong>@lang('ui.upload')</strong> @lang('ui.new_signature')</div>
                    <div class="col-auto">
                        <button type="button" class="btn btn-primary w-100" wire:click="saveUpload"
                            wire:loading.attr="disabled"> @lang('ui.save_signature_upload') </button>
                    </div>
                </div>
            </div>
        </div>

        @if (session()->has('success'))
            <div class="alert alert-success mt-3">{{ session('success') }}</div>
        @endif
        @if (session()->has('error'))
            <div class="alert alert-danger mt-3">{{ session('error') }}</div>
        @endif
    </div>
</div>

<script @cspNonce>
    document.addEventListener("DOMContentLoaded", function () {
        const canvas = document.getElementById("signature-advanced");
        const signaturePad = new SignaturePad(canvas, {
            backgroundColor: "transparent",
            penColor: getComputedStyle(canvas).color,
        });

        function resizeCanvas() {
            const ratio = Math.max(window.devicePixelRatio || 1, 1);
            const width = canvas.offsetWidth || 400;
            const height = canvas.offsetHeight || 400;
            canvas.width = width * ratio;
            canvas.height = height * ratio;
            canvas.getContext("2d").scale(ratio, ratio);
            signaturePad.fromData(signaturePad.toData());
        }
        window.addEventListener("resize", resizeCanvas);
        resizeCanvas();

        document.getElementById("signature-advanced-clear").addEventListener("click", () => {
            signaturePad.clear();
        });

        document.getElementById("signature-submit-from-canvas").addEventListener("click", () => {
            if (signaturePad.isEmpty()) {
                alert("Please sign first.");
                return;
            }
            const dataURL = signaturePad.toDataURL();
            @this.set('signatureData', dataURL);
            @this.call('save');
        });

        window.addEventListener('signature-saved', () => {
            // تقدر تحدث العرض أو تجيب بيانات جديدة لو تبي
            // مثلاً: location.reload();  (لو ضروري)
        });
    });
</script>