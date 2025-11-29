<div class="row row-cards">
    <!-- العمود الأيسر: توقيع بالـCanvas -->
    {{-- <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <h3 class="card-title">Add Your Signature</h3>
                <p class="card-subtitle">Please Use Touch screen or electronic signature pad</p>
                <div class="mb-3">
                    <label class="form-label">{{ __('Signature') }}</label>
                    <div class="signature position-relative">
                        <div class="position-absolute top-0 end-0 p-2">
                            <button type="button" class="btn btn-icon" id="signature-advanced-clear" title="Clear signature">
                                <i class="ti ti-trash"></i>
                            </button>
                        </div>
                        <canvas id="signature-advanced" width="400" height="400" class="signature-canvas w-100" style="border:1px solid #ddd;"></canvas>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <div class="row align-items-center">
                    <div class="col"><strong>Signed By</strong> {{ auth()->user()->name }}</div>
                    <div class="col-auto">
                        <button type="button" class="btn btn-primary w-100" id="signature-submit-from-canvas">
                            Save Signature (Canvas)
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div> --}}
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <h3 class="card-title">Add Your Signature</h3>
                <p class="card-subtitle">Please Use Touch screen or electronic signature pad</p>
                <div class="mb-3">
                    <label class="form-label">{{ __('Signature') }}</label>
                    <div class="signature position-relative">
                        <div class="position-absolute top-0 end-0 p-2">
                            <div class="btn btn-icon" id="signature-advanced-clear" title="Clear signature">
                                <i class="ti ti-trash"></i>
                            </div>
                        </div>
                        <canvas id="signature-advanced" width="400" height="400" class="signature-canvas"></canvas>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <div class="row align-items-center">
                    <div class="col"><strong>Signed By</strong> {{ auth()->user()->name }}</div>
                    <div class="col-auto">
                        <button type="button" class="btn btn-primary w-100" id="signature-submit-png">Save
                            Signature</button>
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
                        <img src="{{ asset('storage/' . $currentPath) }}" class="img-fluid rounded border" alt="Current Signature" />
                    @else
                        <div class="text-muted">No signature on file.</div>
                    @endif
                </div>

                <div class="mb-3">
                    <input type="file"
                           class="form-control @error('signatureFile') is-invalid @enderror"
                           accept="image/png,image/jpeg,image/webp"
                           wire:model="signatureFile">
                    @error('signatureFile')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div wire:loading wire:target="signatureFile" class="form-text mt-1">Uploading…</div>
                </div>

                @if ($signatureFile)
                    <div class="mb-3">
                        <div class="form-label">Preview</div>
                        <img src="{{ $signatureFile->temporaryUrl() }}" class="img-fluid rounded border" alt="Preview">
                    </div>
                @endif
            </div>
            <div class="card-footer">
                <div class="row align-items-center">
                    <div class="col"><strong>Upload</strong> New Signature</div>
                    <div class="col-auto">
                        <button type="button" class="btn btn-primary w-100" wire:click="saveUpload" wire:loading.attr="disabled">
                            Save Signature (Upload)
                        </button>
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

<script>
document.addEventListener("DOMContentLoaded", function() {
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

{{-- <div class="row row-cards">


    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">{{ __('Current Active Signature') }}</h3>
            </div>
            <div class="card-body">
                <div class="m-0 p-0">
                    <img src="{{ asset('storage/' . auth()->user()->signature->image_path) }}" />
                </div>
                <div class="mb-3">
                    <div class="form-label">Custom File Input</div>
                    <input type="file" class="form-control">
                </div>
            </div>
            <div class="card-footer">
                <div class="row align-items-center">
                    <div class="col"><strong>Upload</strong> New Signature

                    </div>
                    <div class="col-auto">
                        <button type="button" class="btn btn-primary w-100" id="signature-submit-png">Save
                            Signature</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const canvas = document.getElementById("signature-advanced");
        const signaturePad = new SignaturePad(canvas, {
            backgroundColor: "transparent",
            penColor: getComputedStyle(canvas).color,
        });

        function resizeCanvas() {
            const ratio = Math.max(window.devicePixelRatio || 1, 1);
            console.log(ratio);
            console.log(canvas.offsetWidth, canvas.offsetHeight);
            canvas.width = canvas.offsetWidth * ratio;
            canvas.height = canvas.offsetHeight * ratio;
            canvas.getContext("2d").scale(ratio, ratio);
            signaturePad.fromData(signaturePad.toData());
        }
        window.addEventListener("resize", resizeCanvas);
        resizeCanvas();

        document.getElementById("signature-advanced-clear").addEventListener("click", () => {
            signaturePad.clear();
        });


        document.getElementById("signature-submit-png").addEventListener("click", () => {
            alert('Submitting signature...');
            if (signaturePad.isEmpty()) {
                alert("Please sign first.");
                return;
            }
            const dataURL = signaturePad.toDataURL();
            @this.set('signatureData', dataURL);
            @this.call('save');
        });

        window.addEventListener('signature-saved', () => {
            alert('Signature saved successfully!');
            // signaturePad.clear();
        });
    });
</script> --}}
