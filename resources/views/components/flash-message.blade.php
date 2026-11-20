@if (session('success'))
    <div class="flash ok" role="status">
        <i data-lucide="circle-check" class="w-5 h-5 shrink-0 mt-0.5"></i>
        <div>{{ session('success') }}</div>
    </div>
@endif

@if (session('warning'))
    <div class="flash warn" role="alert">
        <i data-lucide="triangle-alert" class="w-5 h-5 shrink-0 mt-0.5"></i>
        <div>{{ session('warning') }}</div>
    </div>
@endif

@if (session('error'))
    <div class="flash err" role="alert">
        <i data-lucide="circle-alert" class="w-5 h-5 shrink-0 mt-0.5"></i>
        <div>{{ session('error') }}</div>
    </div>
@endif

@if (session('status'))
    <div class="flash info" role="status">
        <i data-lucide="info" class="w-5 h-5 shrink-0 mt-0.5"></i>
        <div>{{ session('status') }}</div>
    </div>
@endif

@if ($errors->any())
    <div class="flash err" role="alert">
        <i data-lucide="circle-alert" class="w-5 h-5 shrink-0 mt-0.5"></i>
        <div class="space-y-1">
            <div class="font-bold">Periksa kembali data yang dikirim:</div>
            <ul class="list-disc pl-4 text-sm space-y-0.5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif
