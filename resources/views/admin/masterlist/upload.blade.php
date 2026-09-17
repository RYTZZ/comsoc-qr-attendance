<x-layouts.admin :title="'Upload Masterlist'">
<div class="page-header">
    <div>
        <h1 class="page-title">Upload Masterlist</h1>
        <p class="text-xs text-slate-400 mt-1">Enroll students and register active memberships from university records.</p>
    </div>
</div>

<div class="max-w-2xl mb-6">
    <div class="flex items-center justify-between p-3 rounded-xl bg-slate-900 border border-slate-800 text-xs">
        <div class="flex items-center gap-2 text-indigo-400 font-semibold">
            <span class="w-6 h-6 rounded-full bg-indigo-600 text-white flex items-center justify-center text-[11px] shadow">1</span>
            <span>Upload</span>
        </div>
        <div class="h-0.5 flex-1 mx-3 bg-slate-800"></div>
        <div class="flex items-center gap-2 text-slate-500 font-medium">
            <span class="w-6 h-6 rounded-full bg-slate-800 text-slate-400 flex items-center justify-center text-[11px]">2</span>
            <span>Review</span>
        </div>
        <div class="h-0.5 flex-1 mx-3 bg-slate-800"></div>
        <div class="flex items-center gap-2 text-slate-500 font-medium">
            <span class="w-6 h-6 rounded-full bg-slate-800 text-slate-400 flex items-center justify-center text-[11px]">3</span>
            <span>Confirm</span>
        </div>
        <div class="h-0.5 flex-1 mx-3 bg-slate-800"></div>
        <div class="flex items-center gap-2 text-slate-500 font-medium">
            <span class="w-6 h-6 rounded-full bg-slate-800 text-slate-400 flex items-center justify-center text-[11px]">4</span>
            <span>Completed</span>
        </div>
    </div>
</div>

<div class="max-w-2xl">
    <div class="card mb-6">
        <div class="alert-info mb-5">
            <svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
            <div>
                <p class="font-medium mb-1">Masterlist Format</p>
                <p class="text-xs opacity-80">Required columns: <strong>Student Number</strong>, <strong>Name</strong> (Last Name, First Name Middle Name). Excel (.xlsx, .xls) and CSV are accepted. Maximum file size: 10MB.</p>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.masterlist.preview') }}" enctype="multipart/form-data" class="space-y-5">
            @csrf
            <div>
                <label for="academic_year_id" class="label">Academic Year *</label>
                @php
                    $yearOpts = [];
                    $activeYearId = '';
                    foreach($academicYears as $year) {
                        $yearOpts[$year->id] = $year->label . ($year->is_active ? ' (Active)' : '');
                        if ($year->is_active && !$activeYearId) {
                            $activeYearId = $year->id;
                        }
                    }
                @endphp
                <x-custom-dropdown
                    name="academic_year_id"
                    id="academic_year_id"
                    :options="$yearOpts"
                    :value="old('academic_year_id', $activeYearId)"
                    :required="true"
                    placeholder="Select academic year…" />
            </div>

            <div>
                <label for="file" class="label">Masterlist File *</label>
                <div x-data="{ name: '' }" class="mt-1">
                    <label for="file" class="flex flex-col items-center justify-center w-full h-32 border-2 border-dashed border-slate-700 rounded-xl cursor-pointer hover:border-indigo-500 hover:bg-slate-800/50 transition-all group">
                        <div class="text-center">
                            <svg class="w-8 h-8 text-slate-500 group-hover:text-indigo-400 mx-auto mb-2 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                            <p class="text-sm text-slate-400" x-text="name || 'Click to upload or drag and drop'"></p>
                            <p class="text-xs text-slate-600">.xlsx, .xls, .csv — max 10MB</p>
                        </div>
                        <input type="file" name="file" id="file" class="sr-only" accept=".xlsx,.xls,.csv" required
                               @change="name = $event.target.files[0]?.name">
                    </label>
                </div>
            </div>

            <div class="flex justify-end pt-2">
                <button type="submit" class="btn-primary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    Preview Import
                </button>
            </div>
        </form>
    </div>
</div>
</x-layouts.admin>
