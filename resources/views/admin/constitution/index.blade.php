<x-admin-layout title="Constitutional & By-Laws Management">
    <div class="space-y-6">

        <!-- Top Header & Breadcrumb -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 text-xs text-slate-400 mb-1">
                    <a href="{{ route('admin.dashboard') }}" class="hover:text-white transition">Dashboard</a>
                    <span>/</span>
                    <span class="text-white font-medium">Governance & Constitution</span>
                </div>
                <h1 class="text-2xl font-bold text-white tracking-tight flex items-center gap-2.5">
                    <i data-lucide="scroll-text" class="w-6 h-6 text-amber-400"></i>
                    <span>Constitutional and By-Laws Management</span>
                </h1>
                <p class="text-xs text-slate-400 mt-1">
                    Upload, replace, and govern the Computing Society's official governing document for all members.
                </p>
            </div>

            <div class="flex items-center gap-2.5">
                <a href="{{ route('constitution.index') }}"
                   target="_blank"
                   class="btn-secondary text-xs flex items-center gap-2">
                    <i data-lucide="external-link" class="w-4 h-4"></i>
                    <span>View Member Page</span>
                </a>
                @if($document && $document->fileExists())
                    <a href="{{ route('constitution.download') }}"
                       class="btn-primary text-xs flex items-center gap-2">
                        <i data-lucide="download" class="w-4 h-4"></i>
                        <span>Download Current File</span>
                    </a>
                @endif
            </div>
        </div>

        @if(session('status'))
            <div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 text-xs flex items-center gap-3">
                <i data-lucide="check-circle" class="w-5 h-5 shrink-0 text-emerald-400"></i>
                <span class="font-medium">{{ session('status') }}</span>
            </div>
        @endif

        @if($errors->any())
            <div class="p-4 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-300 text-xs space-y-1">
                <div class="font-bold flex items-center gap-2">
                    <i data-lucide="alert-octagon" class="w-4 h-4 text-rose-400"></i>
                    <span>Please correct the errors below:</span>
                </div>
                <ul class="list-disc list-inside ps-2 text-rose-200">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Left: Document Status & Current Details -->
            <div class="lg:col-span-1 space-y-6">

                <!-- Current Document Snapshot -->
                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-lg space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                        <h2 class="text-sm font-bold text-white flex items-center gap-2">
                            <i data-lucide="file-check-2" class="w-4 h-4 text-emerald-400"></i>
                            <span>Current Published Document</span>
                        </h2>
                        @if($document)
                            <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30">
                                v{{ $document->version }}.0
                            </span>
                        @endif
                    </div>

                    @if($document && $document->fileExists())
                        <div class="space-y-3 text-xs">
                            <div>
                                <span class="text-slate-500 block uppercase text-[10px] font-bold tracking-wider">Document Title</span>
                                <span class="text-white font-semibold text-sm">{{ $document->title }}</span>
                            </div>

                            <div>
                                <span class="text-slate-500 block uppercase text-[10px] font-bold tracking-wider">Publication Status</span>
                                <div class="mt-1 flex items-center gap-2">
                                    @if($document->is_published)
                                        <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/30 flex items-center gap-1.5">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                                            Published & Visible to Members
                                        </span>
                                    @else
                                        <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-rose-500/10 text-rose-400 border border-rose-500/30 flex items-center gap-1.5">
                                            <span class="w-1.5 h-1.5 rounded-full bg-rose-400"></span>
                                            Unpublished (Hidden from Members)
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-2 pt-2 border-t border-slate-800">
                                <div>
                                    <span class="text-slate-500 block text-[10px] font-medium">File Size</span>
                                    <span class="text-slate-200 font-mono">{{ $document->formattedFileSize() }}</span>
                                </div>
                                <div>
                                    <span class="text-slate-500 block text-[10px] font-medium">Format</span>
                                    <span class="text-slate-200 uppercase font-mono">PDF</span>
                                </div>
                                <div>
                                    <span class="text-slate-500 block text-[10px] font-medium">First Uploaded</span>
                                    <span class="text-slate-300">{{ $document->created_at?->format('M d, Y') ?? 'N/A' }}</span>
                                </div>
                                <div>
                                    <span class="text-slate-500 block text-[10px] font-medium">Last Replaced</span>
                                    <span class="text-slate-300">{{ $document->updated_at?->format('M d, Y') ?? 'N/A' }}</span>
                                </div>
                            </div>

                            <div class="pt-2 border-t border-slate-800">
                                <span class="text-slate-500 block text-[10px] font-medium">Original Filename</span>
                                <span class="text-slate-300 font-mono text-[11px] truncate block" title="{{ $document->original_filename }}">
                                    {{ $document->original_filename }}
                                </span>
                            </div>

                            @if($document->file_hash)
                                <div class="pt-2 border-t border-slate-800">
                                    <span class="text-slate-500 block text-[10px] font-medium">SHA-256 Integrity Hash</span>
                                    <span class="text-slate-400 font-mono text-[9px] break-all block bg-slate-950 p-1.5 rounded border border-slate-800">
                                        {{ $document->file_hash }}
                                    </span>
                                </div>
                            @endif

                            @if($document->uploader)
                                <div class="pt-2 border-t border-slate-800">
                                    <span class="text-slate-500 block text-[10px] font-medium">Last Modified By</span>
                                    <span class="text-slate-300 font-medium">{{ $document->uploader->name }} ({{ $document->uploader->role }})</span>
                                </div>
                            @endif

                            <!-- Quick Publication Action -->
                            <form action="{{ route('admin.constitution.toggle-publish') }}" method="POST" class="pt-3">
                                @csrf
                                <button type="submit"
                                        class="w-full text-xs font-semibold py-2 px-3 rounded-xl transition flex items-center justify-center gap-2 {{ $document->is_published ? 'bg-amber-500/10 text-amber-300 hover:bg-amber-500/20 border border-amber-500/30' : 'bg-emerald-500/10 text-emerald-300 hover:bg-emerald-500/20 border border-emerald-500/30' }}">
                                    <i data-lucide="{{ $document->is_published ? 'eye-off' : 'eye' }}" class="w-4 h-4"></i>
                                    <span>{{ $document->is_published ? 'Unpublish Document' : 'Publish Document' }}</span>
                                </button>
                            </form>
                        </div>
                    @else
                        <!-- No file found state -->
                        <div class="text-center py-6 space-y-2">
                            <div class="w-12 h-12 rounded-xl bg-slate-800 flex items-center justify-center mx-auto text-slate-400">
                                <i data-lucide="file-x" class="w-6 h-6"></i>
                            </div>
                            <h3 class="text-sm font-bold text-white">No Document File Present</h3>
                            <p class="text-xs text-slate-400 max-w-xs mx-auto">
                                The system does not currently have an approved PDF uploaded. Use the upload form to add the official document.
                            </p>
                        </div>
                    @endif
                </div>

                <!-- Update Document Metadata Form (Title & Description) -->
                @if($document)
                    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-lg space-y-4">
                        <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                            <h2 class="text-sm font-bold text-white flex items-center gap-2">
                                <i data-lucide="edit-3" class="w-4 h-4 text-blue-400"></i>
                                <span>Edit Title & Description</span>
                            </h2>
                        </div>

                        <form action="{{ route('admin.constitution.update-metadata') }}" method="POST" class="space-y-4">
                            @csrf
                            @method('PATCH')

                            <div>
                                <label for="edit_title" class="block text-xs font-semibold text-slate-300 mb-1">
                                    Document Title <span class="text-rose-400">*</span>
                                </label>
                                <input type="text"
                                       id="edit_title"
                                       name="title"
                                       value="{{ old('title', $document->title) }}"
                                       required
                                       class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-amber-500">
                            </div>

                            <div>
                                <label for="edit_description" class="block text-xs font-semibold text-slate-300 mb-1">
                                    Description / Purpose
                                </label>
                                <textarea id="edit_description"
                                          name="description"
                                          rows="3"
                                          class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-amber-500">{{ old('description', $document->description) }}</textarea>
                            </div>

                            <button type="submit" class="btn-secondary text-xs w-full py-2 flex items-center justify-center gap-1.5">
                                <i data-lucide="save" class="w-3.5 h-3.5"></i>
                                <span>Save Changes</span>
                            </button>
                        </form>
                    </div>
                @endif

            </div>

            <!-- Right: Upload / Replace Document Form & In-Console Preview -->
            <div class="lg:col-span-2 space-y-6">

                <!-- Upload / Replace Form Card -->
                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-lg space-y-5">
                    <div class="flex items-center justify-between pb-4 border-b border-slate-800">
                        <div>
                            <h2 class="text-base font-bold text-white flex items-center gap-2">
                                <i data-lucide="upload-cloud" class="w-5 h-5 text-amber-400"></i>
                                <span>{{ $document ? 'Replace Document With Newer Approved Version' : 'Upload Official Constitutional Document' }}</span>
                            </h2>
                            <p class="text-xs text-slate-400 mt-0.5">
                                {{ $document ? 'Uploading a new PDF increments the version count and invalidates cached previews immediately.' : 'Upload the original signed or ratified PDF for members.' }}
                            </p>
                        </div>
                        @if($document)
                            <span class="px-2.5 py-1 rounded-full text-[11px] font-semibold bg-blue-500/10 text-blue-400 border border-blue-500/30">
                                Target: v{{ $document->version + 1 }}.0
                            </span>
                        @endif
                    </div>

                    <form action="{{ route('admin.constitution.upload') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                        @csrf

                        <div class="grid grid-cols-1 gap-4">
                            <div>
                                <label for="upload_title" class="block text-xs font-semibold text-slate-300 mb-1">
                                    Official Document Title <span class="text-rose-400">*</span>
                                </label>
                                <input type="text"
                                       id="upload_title"
                                       name="title"
                                       value="{{ old('title', $document?->title ?? 'Computing Society Constitution and By-Laws') }}"
                                       required
                                       class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-amber-500">
                            </div>

                            <div>
                                <label for="upload_description" class="block text-xs font-semibold text-slate-300 mb-1">
                                    Description & Scope
                                </label>
                                <textarea id="upload_description"
                                          name="description"
                                          rows="2"
                                          class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-amber-500">{{ old('description', $document?->description ?? 'The official governing document establishing the organization\'s governing principles, policies, structure, and procedures.') }}</textarea>
                            </div>

                            <!-- PDF File Drag/Select Area -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-300 mb-1">
                                    Official PDF File <span class="text-rose-400">*</span>
                                </label>
                                <div class="relative border-2 border-dashed border-slate-700 hover:border-amber-500/60 rounded-2xl p-6 text-center bg-slate-950/60 transition group cursor-pointer"
                                     onclick="document.getElementById('cbl_file_input').click()">
                                    <input type="file"
                                           id="cbl_file_input"
                                           name="document"
                                           accept="application/pdf"
                                           required
                                           class="sr-only"
                                           onchange="const f = this.files[0]; if(f) { document.getElementById('selected-file-label').innerText = f.name + ' (' + (f.size/1024).toFixed(1) + ' KB)'; }">
                                    
                                    <div class="w-12 h-12 rounded-xl bg-amber-500/10 text-amber-400 flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition">
                                        <i data-lucide="file-up" class="w-6 h-6"></i>
                                    </div>
                                    <p id="selected-file-label" class="text-xs font-semibold text-slate-200">
                                        Click to browse or drop official PDF here
                                    </p>
                                    <p class="text-[11px] text-slate-500 mt-1">
                                        Strict PDF validation • Maximum size: 20 MB • File hash automatically computed
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-center gap-2 pt-1">
                                <input type="checkbox"
                                       id="is_published_checkbox"
                                       name="is_published"
                                       value="1"
                                       {{ old('is_published', $document ? $document->is_published : true) ? 'checked' : '' }}
                                       class="rounded bg-slate-950 border-slate-700 text-amber-500 focus:ring-amber-500/30">
                                <label for="is_published_checkbox" class="text-xs text-slate-300 select-none cursor-pointer">
                                    Publish immediately upon upload (makes it available to all authenticated members)
                                </label>
                            </div>
                        </div>

                        <div class="pt-3 border-t border-slate-800 flex justify-end">
                            <button type="submit" class="btn-primary text-xs flex items-center gap-2 py-2.5 px-4 shadow-lg">
                                <i data-lucide="upload" class="w-4 h-4"></i>
                                <span>{{ $document ? 'Upload & Replace Document' : 'Upload Official Constitution' }}</span>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Live Administrator Preview Box -->
                @if($document && $document->fileExists())
                    <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-lg">
                        <div class="px-5 py-3.5 bg-slate-950 border-b border-slate-800 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <i data-lucide="monitor" class="w-4 h-4 text-slate-400"></i>
                                <span class="text-xs font-bold text-white">Live Administrator Document Preview</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <a href="{{ route('constitution.preview') }}"
                                   target="_blank"
                                   class="text-[11px] text-amber-400 hover:underline flex items-center gap-1 font-semibold">
                                    <span>Open Stream</span>
                                    <i data-lucide="external-link" class="w-3 h-3"></i>
                                </a>
                            </div>
                        </div>

                        <div class="w-full bg-slate-950" style="height: 500px;">
                            <iframe src="{{ route('constitution.preview') }}?v={{ $document->version }}-{{ $document->updated_at?->timestamp }}#toolbar=1"
                                    class="w-full h-full border-0"
                                    title="Constitution Preview">
                            </iframe>
                        </div>
                    </div>
                @endif

            </div>

        </div>

    </div>
</x-admin-layout>
