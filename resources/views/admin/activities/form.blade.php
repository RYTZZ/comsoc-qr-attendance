<x-layouts.admin :title="$activity ? 'Edit Activity — ' . $activity->title : 'Create Parent Activity'">
<div class="page-header mb-6">
    <div>
        <a href="{{ route('admin.activities.index') }}" class="text-xs text-slate-400 hover:text-white inline-flex items-center gap-1 mb-1">
            <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
            Back to Activity Management
        </a>
        <h1 class="page-title font-heading text-2xl font-bold text-white">
            {{ $activity ? "Edit Parent Activity: {$activity->title}" : 'Create Parent Activity / Multi-Session Event' }}
        </h1>
    </div>
</div>

<form method="POST"
      action="{{ $activity ? route('admin.activities.update', $activity) : route('admin.activities.store') }}"
      enctype="multipart/form-data"
      class="space-y-6"
      x-data="{
          regMode: '{{ old('registration_mode', $activity?->registration_mode ?? 'individual') }}',
          isMultiSession: {{ old('is_multi_session', $activity?->is_multi_session ?? true) ? 'true' : 'false' }},
          awardParentRewards: {{ old('award_parent_rewards', $activity?->award_parent_rewards ?? false) ? 'true' : 'false' }}
      }">
    @csrf
    @if($activity) @method('PUT') @endif

    <!-- Level 1: General Parent Event Information -->
    <div class="card bg-[#172033] border border-slate-800 p-6 space-y-4">
        <h2 class="text-sm font-bold text-white uppercase tracking-wider border-b border-slate-800 pb-2">
            1. Parent Event Information
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="md:col-span-2">
                <label class="block text-xs font-semibold text-slate-300 mb-1">Event / Activity Title *</label>
                <input type="text" name="title" value="{{ old('title', $activity?->title) }}" required
                       placeholder="e.g., COMSOC Skills Development Day / COMSOC Hackathon 2026"
                       class="input w-full text-xs bg-[#0B1120] border-slate-700 text-white">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Activity Category *</label>
                <select name="category" required class="select w-full text-xs bg-[#0B1120] border-slate-700 text-white">
                    @foreach($categories as $cat)
                        <option value="{{ $cat }}" @selected(old('category', $activity?->category) === $cat)>{{ $cat }}</option>
                    @endforeach
                </select>
            </div>

            <div class="md:col-span-3">
                <label class="block text-xs font-semibold text-slate-300 mb-1">Description</label>
                <textarea name="description" rows="3"
                          class="input w-full text-xs bg-[#0B1120] border-slate-700 text-white">{{ old('description', $activity?->description) }}</textarea>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Venue / Location</label>
                <input type="text" name="venue" value="{{ old('venue', $activity?->venue) }}"
                       placeholder="e.g., CCS Building Laboratories 1-4"
                       class="input w-full text-xs bg-[#0B1120] border-slate-700 text-white">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Event Organizer</label>
                <input type="text" name="organizer_name" value="{{ old('organizer_name', $activity?->organizer_name ?? 'Computing Society') }}"
                       class="input w-full text-xs bg-[#0B1120] border-slate-700 text-white">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Status *</label>
                <select name="status" class="select w-full text-xs bg-[#0B1120] border-slate-700 text-white">
                    @foreach(\App\Models\Activity::STATUSES as $st)
                        <option value="{{ $st }}" @selected(old('status', $activity?->status ?? 'registration_open') === $st)>
                            {{ ucfirst(str_replace('_', ' ', $st)) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Event Starts At</label>
                <input type="datetime-local" name="starts_at"
                       value="{{ old('starts_at', $activity?->starts_at?->format('Y-m-d\TH:i')) }}"
                       class="input w-full text-xs bg-[#0B1120] border-slate-700 text-white">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Event Ends At</label>
                <input type="datetime-local" name="ends_at"
                       value="{{ old('ends_at', $activity?->ends_at?->format('Y-m-d\TH:i')) }}"
                       class="input w-full text-xs bg-[#0B1120] border-slate-700 text-white">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Banner Image</label>
                <input type="file" name="banner" accept="image/*"
                       class="block w-full text-xs text-slate-300 file:mr-2 file:py-1 file:px-2.5 file:rounded file:border-0 file:bg-[#560E0F] file:text-white">
            </div>

            <div class="md:col-span-3">
                <label class="block text-xs font-semibold text-slate-300 mb-1">General Participation Requirements</label>
                <textarea name="general_requirements" rows="2"
                          placeholder="Bring valid student ID, laptop, etc."
                          class="input w-full text-xs bg-[#0B1120] border-slate-700 text-white">{{ old('general_requirements', $activity?->general_requirements) }}</textarea>
            </div>
        </div>
    </div>

    <!-- Registration Window, Capacities, Modes & Eligibility -->
    <div class="card bg-[#172033] border border-slate-800 p-6 space-y-4">
        <h2 class="text-sm font-bold text-white uppercase tracking-wider border-b border-slate-800 pb-2">
            2. Registration Configuration, Session Rules & Eligibility
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Registration Opens At</label>
                <input type="datetime-local" name="registration_opens_at"
                       value="{{ old('registration_opens_at', $activity?->registration_opens_at?->format('Y-m-d\TH:i')) }}"
                       class="input w-full text-xs bg-[#0B1120] border-slate-700 text-white">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Registration Closes At</label>
                <input type="datetime-local" name="registration_closes_at"
                       value="{{ old('registration_closes_at', $activity?->registration_closes_at?->format('Y-m-d\TH:i')) }}"
                       class="input w-full text-xs bg-[#0B1120] border-slate-700 text-white">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Overall Event Capacity (Blank = Unlimited)</label>
                <input type="number" name="overall_capacity" min="1"
                       value="{{ old('overall_capacity', $activity?->overall_capacity) }}"
                       class="input w-full text-xs bg-[#0B1120] border-slate-700 text-white">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Registration Mode *</label>
                <select name="registration_mode" x-model="regMode" class="select w-full text-xs bg-[#0B1120] border-slate-700 text-white">
                    <option value="individual">Individual Registration Only</option>
                    <option value="team">Team Registration Only (IDEATHON / Hackathon)</option>
                    <option value="both">Both Individual & Team</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Schedule Conflict Policy *</label>
                <select name="conflict_policy" class="select w-full text-xs bg-[#0B1120] border-slate-700 text-white">
                    <option value="block" @selected(old('conflict_policy', $activity?->conflict_policy ?? 'block') === 'block')>Block Overlapping Session Registrations</option>
                    <option value="warn" @selected(old('conflict_policy', $activity?->conflict_policy) === 'warn')>Allow with Schedule Conflict Warning</option>
                    <option value="ignore" @selected(old('conflict_policy', $activity?->conflict_policy) === 'ignore')>Do Not Check Schedule Conflicts</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Max Sessions Per Student (Blank = All)</label>
                <input type="number" name="max_sessions_per_student" min="1"
                       value="{{ old('max_sessions_per_student', $activity?->max_sessions_per_student) }}"
                       class="input w-full text-xs bg-[#0B1120] border-slate-700 text-white">
            </div>
        </div>

        <!-- Team Settings -->
        <div x-show="regMode === 'team' || regMode === 'both'" class="p-4 rounded-xl bg-[#0B1120] border border-slate-800 grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Minimum Team Size</label>
                <input type="number" name="min_team_size" min="1" max="20" value="{{ old('min_team_size', $activity?->min_team_size ?? 2) }}"
                       class="input w-full text-xs bg-[#172033] border-slate-700 text-white">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Maximum Team Size</label>
                <input type="number" name="max_team_size" min="1" max="20" value="{{ old('max_team_size', $activity?->max_team_size ?? 5) }}"
                       class="input w-full text-xs bg-[#172033] border-slate-700 text-white">
            </div>
            <div class="flex items-center pt-5">
                <label class="inline-flex items-center gap-2 text-xs text-slate-300 cursor-pointer">
                    <input type="checkbox" name="require_all_team_invites_accepted" value="1"
                           @checked(old('require_all_team_invites_accepted', $activity?->require_all_team_invites_accepted ?? true))
                           class="rounded bg-slate-900 border-slate-700 text-[#560E0F]">
                    <span>Require all invited members to accept before submission</span>
                </label>
            </div>
        </div>

        <!-- Boolean Toggles -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 pt-2">
            <label class="inline-flex items-center gap-2 text-xs text-slate-300 cursor-pointer">
                <input type="checkbox" name="is_multi_session" value="1" x-model="isMultiSession"
                       class="rounded bg-slate-900 border-slate-700 text-[#560E0F]">
                <span>Multi-Session Activity</span>
            </label>

            <label class="inline-flex items-center gap-2 text-xs text-slate-300 cursor-pointer">
                <input type="checkbox" name="allow_multiple_sessions" value="1"
                       @checked(old('allow_multiple_sessions', $activity?->allow_multiple_sessions ?? true))
                       class="rounded bg-slate-900 border-slate-700 text-[#560E0F]">
                <span>Students Can Select Multiple Sessions</span>
            </label>

            <label class="inline-flex items-center gap-2 text-xs text-slate-300 cursor-pointer">
                <input type="checkbox" name="require_session_selection" value="1"
                       @checked(old('require_session_selection', $activity?->require_session_selection ?? true))
                       class="rounded bg-slate-900 border-slate-700 text-[#560E0F]">
                <span>Session Selection Required</span>
            </label>

            <label class="inline-flex items-center gap-2 text-xs text-slate-300 cursor-pointer">
                <input type="checkbox" name="allow_independent_session_registration" value="1"
                       @checked(old('allow_independent_session_registration', $activity?->allow_independent_session_registration ?? false))
                       class="rounded bg-slate-900 border-slate-700 text-[#560E0F]">
                <span>Allow Independent Session Registration</span>
            </label>

            <label class="inline-flex items-center gap-2 text-xs text-slate-300 cursor-pointer">
                <input type="checkbox" name="requires_approval" value="1"
                       @checked(old('requires_approval', $activity?->requires_approval ?? false))
                       class="rounded bg-slate-900 border-slate-700 text-[#560E0F]">
                <span>Requires Organizer Approval</span>
            </label>

            <label class="inline-flex items-center gap-2 text-xs text-slate-300 cursor-pointer">
                <input type="checkbox" name="require_separate_session_approval" value="1"
                       @checked(old('require_separate_session_approval', $activity?->require_separate_session_approval ?? false))
                       class="rounded bg-slate-900 border-slate-700 text-[#560E0F]">
                <span>Each Session Requires Separate Approval</span>
            </label>

            <label class="inline-flex items-center gap-2 text-xs text-slate-300 cursor-pointer">
                <input type="checkbox" name="waitlist_enabled" value="1"
                       @checked(old('waitlist_enabled', $activity?->waitlist_enabled ?? true))
                       class="rounded bg-slate-900 border-slate-700 text-[#560E0F]">
                <span>Enable Waiting List When Full</span>
            </label>

            <label class="inline-flex items-center gap-2 text-xs text-slate-300 cursor-pointer">
                <input type="checkbox" name="allow_walk_in" value="1"
                       @checked(old('allow_walk_in', $activity?->allow_walk_in ?? false))
                       class="rounded bg-slate-900 border-slate-700 text-[#560E0F]">
                <span>Allow Authorized Walk-In Attendance</span>
            </label>

            <label class="inline-flex items-center gap-2 text-xs text-slate-300 cursor-pointer">
                <input type="checkbox" name="allow_cancellation" value="1"
                       @checked(old('allow_cancellation', $activity?->allow_cancellation ?? true))
                       class="rounded bg-slate-900 border-slate-700 text-[#560E0F]">
                <span>Allow Student Cancellation</span>
            </label>

            <label class="inline-flex items-center gap-2 text-xs text-slate-300 cursor-pointer">
                <input type="checkbox" name="allow_submission_editing" value="1"
                       @checked(old('allow_submission_editing', $activity?->allow_submission_editing ?? false))
                       class="rounded bg-slate-900 border-slate-700 text-[#560E0F]">
                <span>Allow Submission Editing</span>
            </label>

            <label class="inline-flex items-center gap-2 text-xs text-slate-300 cursor-pointer">
                <input type="hidden" name="require_parent_registration" value="0">
                <input type="checkbox" name="require_parent_registration" value="1"
                       @checked(old('require_parent_registration', $activity?->require_parent_registration ?? true))
                       class="rounded bg-slate-900 border-slate-700 text-[#560E0F]">
                <span>Require Parent Activity Registration</span>
            </label>

            <label class="inline-flex items-center gap-2 text-xs text-slate-300 cursor-pointer">
                <input type="hidden" name="allow_session_change" value="0">
                <input type="checkbox" name="allow_session_change" value="1"
                       @checked(old('allow_session_change', $activity?->allow_session_change ?? true))
                       class="rounded bg-slate-900 border-slate-700 text-[#560E0F]">
                <span>Allow Students to Change Sessions</span>
            </label>
        </div>

        <!-- Program & Year Level Eligibility -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-3 border-t border-slate-800">
            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5">Eligible Programs (Leave all unchecked for All Programs)</label>
                @php $selProgs = old('eligible_programs', $activity?->eligible_programs ?? []); @endphp
                <div class="flex flex-wrap gap-2">
                    @foreach($programs as $prog)
                        <label class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded bg-[#0B1120] border border-slate-700 text-xs text-slate-300">
                            <input type="checkbox" name="eligible_programs[]" value="{{ $prog['value'] }}"
                                   @checked(in_array($prog['value'], $selProgs, true))
                                   class="rounded bg-slate-900 border-slate-700 text-[#560E0F]">
                            <span>{{ $prog['value'] }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5">Eligible Year Levels (Leave all unchecked for All Year Levels)</label>
                @php $selYears = old('eligible_year_levels', $activity?->eligible_year_levels ?? []); @endphp
                <div class="flex flex-wrap gap-2">
                    @foreach($yearLevels as $yr)
                        <label class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded bg-[#0B1120] border border-slate-700 text-xs text-slate-300">
                            <input type="checkbox" name="eligible_year_levels[]" value="{{ $yr }}"
                                   @checked(in_array($yr, $selYears, true))
                                   class="rounded bg-slate-900 border-slate-700 text-[#560E0F]">
                            <span>{{ $yr }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- Form Assignment & Parent Rewards (Super Admin) -->
    <div class="card bg-[#172033] border border-slate-800 p-6 space-y-4">
        <h2 class="text-sm font-bold text-white uppercase tracking-wider border-b border-slate-800 pb-2">
            3. Dynamic Registration Form, Organizers & Parent Rewards
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @if(auth()->user()->isSuperAdmin())
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Assigned Parent Registration Form (Super Admin Exclusive)</label>
                    <select name="registration_form_id" class="select w-full text-xs bg-[#0B1120] border-slate-700 text-white">
                        <option value="">— No Parent Form (Session-Only or Profile-Only) —</option>
                        @foreach($publishedForms as $pf)
                            <option value="{{ $pf->id }}" @selected(old('registration_form_id', $activity?->registration_form_id) === $pf->id)>
                                {{ $pf->title }} (v{{ $pf->version }})
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Parent Completion Derivation Rule</label>
                <select name="completion_mode" class="select w-full text-xs bg-[#0B1120] border-slate-700 text-white">
                    <option value="any_session" @selected(old('completion_mode', $activity?->completion_mode) === 'any_session')>Complete at least 1 selected session</option>
                    <option value="all_selected_sessions" @selected(old('completion_mode', $activity?->completion_mode) === 'all_selected_sessions')>Complete ALL selected sessions</option>
                    <option value="minimum_sessions" @selected(old('completion_mode', $activity?->completion_mode) === 'minimum_sessions')>Complete a minimum number of sessions</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Minimum Sessions for Completion (If Rule = Minimum)</label>
                <input type="number" name="min_sessions_for_completion" min="1"
                       value="{{ old('min_sessions_for_completion', $activity?->min_sessions_for_completion ?? 1) }}"
                       class="input w-full text-xs bg-[#0B1120] border-slate-700 text-white">
            </div>
        </div>

        <div class="p-4 rounded-xl bg-[#0B1120] border border-slate-800 space-y-3">
            <label class="inline-flex items-center gap-2 text-xs font-semibold text-slate-200 cursor-pointer">
                <input type="checkbox" name="award_parent_rewards" value="1" x-model="awardParentRewards"
                       class="rounded bg-slate-900 border-slate-700 text-[#560E0F]">
                <span>Parent Event Provides Additional Bonus EXP/Points Upon Overall Completion (Separate from Session Rewards)</span>
            </label>

            <div x-show="awardParentRewards" class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-1">Parent Bonus EXP</label>
                    <input type="number" name="parent_exp_reward" min="0" value="{{ old('parent_exp_reward', $activity?->parent_exp_reward ?? 0) }}"
                           class="input w-full text-xs bg-[#172033] border-slate-700 text-white">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-1">Parent Bonus Points</label>
                    <input type="number" name="parent_points_reward" min="0" value="{{ old('parent_points_reward', $activity?->parent_points_reward ?? 0) }}"
                           class="input w-full text-xs bg-[#172033] border-slate-700 text-white">
                </div>
            </div>
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-300 mb-1.5">Assign Authorized Organizers</label>
            @php $assignedOrgIds = old('organizer_ids', $activity ? $activity->organizers->pluck('id')->all() : []); @endphp
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2 max-h-40 overflow-y-auto p-3 rounded-xl bg-[#0B1120] border border-slate-800">
                @foreach($organizers as $org)
                    <label class="inline-flex items-center gap-2 text-xs text-slate-300">
                        <input type="checkbox" name="organizer_ids[]" value="{{ $org->id }}"
                               @checked(in_array($org->id, $assignedOrgIds, true))
                               class="rounded bg-slate-900 border-slate-700 text-[#560E0F]">
                        <span>{{ $org->name }} <span class="text-[10px] text-slate-500">({{ $org->role }})</span></span>
                    </label>
                @endforeach
            </div>
        </div>
    </div>

    <div class="flex justify-end gap-3">
        <a href="{{ route('admin.activities.index') }}" class="btn-secondary text-xs py-2.5 px-5">Cancel</a>
        <button type="submit" class="btn-primary text-xs py-2.5 px-6">
            {{ $activity ? 'Save Activity Changes' : 'Create Parent Activity' }}
        </button>
    </div>
</form>
</x-layouts.admin>

