<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="COMSOC HUB Privacy Notice and Data Protection Policy pursuant to the Philippine Data Privacy Act of 2012 (RA 10173).">
    <title>Privacy Notice — COMSOC HUB</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Manrope:wght@600;700;800&family=Space+Grotesk:wght@600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen min-h-[100dvh] bg-[#0B1120] text-[#F8FAFC] font-sans antialiased relative overflow-x-hidden selection:bg-[#560E0F] selection:text-white flex flex-col">

    <x-public-auth-bg />

    <header class="sticky top-0 z-30 bg-[#172033]/95 backdrop-blur-md border-b border-[#334155] px-4 sm:px-8 py-3.5 flex items-center justify-between shadow-lg">
        <a href="/" class="flex items-center gap-3 group">
            <div class="w-10 h-10 rounded-xl bg-[#0B1120] border border-[#334155] p-1.5 flex items-center justify-center shrink-0 shadow-md group-hover:border-[#8F3033] transition">
                <img src="{{ asset('images/COMSOC.png') }}" alt="Computing Society Logo" class="w-full h-full object-contain">
            </div>
            <div>
                <span class="font-extrabold text-[#F8FAFC] text-sm font-brand-display tracking-tight block">COMSOC HUB</span>
                <span class="text-[10px] text-[#df747a] font-semibold tracking-wider uppercase block">Privacy & Data Governance</span>
            </div>
        </a>

        <div class="flex items-center gap-2.5">
            @auth
            <a href="{{ route('dashboard') }}" class="btn-primary text-xs py-2 px-3.5 flex items-center gap-1.5 shadow-md">
                <i data-lucide="layout-dashboard" class="w-4 h-4"></i>
                <span>Return to Dashboard</span>
            </a>
            @else
            <a href="{{ route('login') }}" class="btn-secondary text-xs py-2 px-3.5 flex items-center gap-1.5">
                <i data-lucide="log-in" class="w-4 h-4"></i>
                <span>Sign In</span>
            </a>
            <a href="{{ route('signup') }}" class="btn-primary text-xs py-2 px-3.5 flex items-center gap-1.5 shadow-md">
                <i data-lucide="user-plus" class="w-4 h-4"></i>
                <span>Register</span>
            </a>
            @endauth
        </div>
    </header>

    <main class="relative z-10 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12 flex-1 w-full">
        <div class="card bg-[#172033]/95 border border-[#334155] shadow-2xl p-6 sm:p-10 space-y-8 rounded-2xl">

            <!-- Title & Metadata -->
            <div class="border-b border-[#334155] pb-6">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 text-xs font-semibold mb-3">
                    <i data-lucide="shield-check" class="w-3.5 h-3.5"></i>
                    <span>Republic Act No. 10173 (Data Privacy Act of 2012) Compliant</span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-white font-brand-display tracking-tight">
                    COMSOC HUB Privacy Notice
                </h1>
                <p class="text-sm text-[#94A3B8] mt-2 leading-relaxed">
                    Computing Society Organization • Sorsogon State University – Bulan Campus
                </p>
                <div class="flex flex-wrap items-center gap-4 text-xs text-slate-400 mt-4 pt-3 border-t border-[#334155]/60">
                    <span><strong>Effective Date:</strong> Academic Year 2026–2027</span>
                    <span>•</span>
                    <span><strong>Last Updated:</strong> October 2026</span>
                    <span>•</span>
                    <span><strong>Version:</strong> 2.0 (Official Release)</span>
                </div>
            </div>

            <!-- 1. Introduction -->
            <section class="space-y-3">
                <h2 class="text-lg font-bold text-white font-brand-display flex items-center gap-2">
                    <span class="w-6 h-6 rounded-lg bg-[#560E0F] text-[#df747a] text-xs font-mono font-bold flex items-center justify-center">1</span>
                    Organizational Commitment & Scope
                </h2>
                <p class="text-xs sm:text-sm text-slate-300 leading-relaxed">
                    The <strong>Computing Society (COMSOC)</strong> of Sorsogon State University (SorSU) – Bulan Campus is dedicated to upholding the privacy rights of all student members, student leaders, event participants, and officers. This Privacy Notice governs the collection, processing, protection, retention, and disposition of personal and academic data within <strong>COMSOC HUB</strong> (<a href="https://comsochub.tech" class="text-[#df747a] underline">comsochub.tech</a>), our attendance tracking, gamified EXP & rewards ledger, event evaluation, and organizational management platform.
                </p>
            </section>

            <!-- 2. Personal Information Collected -->
            <section class="space-y-4">
                <h2 class="text-lg font-bold text-white font-brand-display flex items-center gap-2">
                    <span class="w-6 h-6 rounded-lg bg-[#560E0F] text-[#df747a] text-xs font-mono font-bold flex items-center justify-center">2</span>
                    Personal Information Collected
                </h2>
                <p class="text-xs sm:text-sm text-slate-300 leading-relaxed">
                    We collect only the minimum necessary personal data directly provided by students during self-registration or generated through system participation:
                </p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-xs sm:text-sm">
                    <div class="p-3.5 rounded-xl bg-[#0B1120] border border-[#334155] space-y-1.5">
                        <div class="font-semibold text-white flex items-center gap-2">
                            <i data-lucide="user" class="w-4 h-4 text-blue-400"></i>
                            <span>Identity & Academic Information</span>
                        </div>
                        <ul class="list-disc list-inside text-slate-400 space-y-1 text-xs">
                            <li>Official Student ID / Student Number</li>
                            <li>Full Name (First Name, Middle Name, Last Name)</li>
                            <li>Academic Program (e.g. BSIT)</li>
                            <li>Year Level and Block Assignment</li>
                            <li>Chosen Username & Adventurer Avatar Style/Seed</li>
                        </ul>
                    </div>

                    <div class="p-3.5 rounded-xl bg-[#0B1120] border border-[#334155] space-y-1.5">
                        <div class="font-semibold text-white flex items-center gap-2">
                            <i data-lucide="mail" class="w-4 h-4 text-purple-400"></i>
                            <span>Contact & Security Credentials</span>
                        </div>
                        <ul class="list-disc list-inside text-slate-400 space-y-1 text-xs">
                            <li>Institutional Email Address (<code class="text-slate-300">@sorsu.edu.ph</code>)</li>
                            <li>Preferred Personal Email (for urgent notices/recovery)</li>
                            <li>Cryptographically Hashed Password (never stored in plaintext)</li>
                            <li>Active Device Session Hashes & Signatures</li>
                            <li>Password Reset Tokens (short-lived & hashed)</li>
                        </ul>
                    </div>

                    <div class="p-3.5 rounded-xl bg-[#0B1120] border border-[#334155] space-y-1.5">
                        <div class="font-semibold text-white flex items-center gap-2">
                            <i data-lucide="qr-code" class="w-4 h-4 text-amber-400"></i>
                            <span>Participation & Attendance Data</span>
                        </div>
                        <ul class="list-disc list-inside text-slate-400 space-y-1 text-xs">
                            <li>Event Registration & Ticket QR Records</li>
                            <li>Kiosk Attendance Scans (Timestamps, Sessions, Status)</li>
                            <li>Snack Distribution Status & Kiosk Verifications</li>
                            <li>Mandatory Event Feedback & Evaluation Submissions</li>
                        </ul>
                    </div>

                    <div class="p-3.5 rounded-xl bg-[#0B1120] border border-[#334155] space-y-1.5">
                        <div class="font-semibold text-white flex items-center gap-2">
                            <i data-lucide="trophy" class="w-4 h-4 text-emerald-400"></i>
                            <span>EXP, Rewards & Community Activity</span>
                        </div>
                        <ul class="list-disc list-inside text-slate-400 space-y-1 text-xs">
                            <li>Spendable EXP and Lifetime EXP Transaction History</li>
                            <li>Physical Merchandise Claim Codes & Redemption Status</li>
                            <li>Mission Submissions, Quests, & Approved Volunteer Hours</li>
                            <li>Help Desk Support Tickets & Communication History</li>
                        </ul>
                    </div>
                </div>
            </section>

            <!-- 3. Purposes of Processing -->
            <section class="space-y-3">
                <h2 class="text-lg font-bold text-white font-brand-display flex items-center gap-2">
                    <span class="w-6 h-6 rounded-lg bg-[#560E0F] text-[#df747a] text-xs font-mono font-bold flex items-center justify-center">3</span>
                    Legal Basis & Purposes of Data Processing
                </h2>
                <p class="text-xs sm:text-sm text-slate-300 leading-relaxed">
                    Processing is conducted in accordance with <strong>Section 12 of the Data Privacy Act of 2012</strong>, grounded upon contractual and organizational membership obligations, compliance with institutional university directives, and explicit data subject consent. Personal data is processed strictly for:
                </p>
                <ul class="list-disc list-inside text-xs sm:text-sm text-slate-300 space-y-1.5 pl-2">
                    <li><strong>Authentication & Security:</strong> Verifying student identity via institutional domain, enforcing single-active-device security, and executing authenticated password resets.</li>
                    <li><strong>Attendance Validation:</strong> Generating dynamic QR attendance tokens and logging physical presence during university-mandated academic seminars and organizational events.</li>
                    <li><strong>Gamification & EXP Ledger:</strong> Calculating legitimate attendance EXP points, crediting volunteer contributions, and managing reward redemptions.</li>
                    <li><strong>Academic Compliance & Clearance:</strong> Generating official organizational attendance reports required for campus student organization accreditation and clearance.</li>
                </ul>
            </section>

            <!-- 4. Security & Storage -->
            <section class="space-y-3">
                <h2 class="text-lg font-bold text-white font-brand-display flex items-center gap-2">
                    <span class="w-6 h-6 rounded-lg bg-[#560E0F] text-[#df747a] text-xs font-mono font-bold flex items-center justify-center">4</span>
                    Data Storage, Security Measures, & Confidentiality
                </h2>
                <p class="text-xs sm:text-sm text-slate-300 leading-relaxed">
                    We implement organizational, physical, and technical controls to protect student data against unauthorized access, destruction, loss, or alteration:
                </p>
                <div class="p-4 rounded-xl bg-[#0B1120] border border-[#334155] space-y-2 text-xs sm:text-sm text-slate-300">
                    <p>• <strong>Encryption:</strong> All web traffic is strictly encrypted in transit using Transport Layer Security (TLS 1.3 / HTTPS). Passwords are cryptographically hashed using standard Bcrypt.</p>
                    <p>• <strong>Strict Role-Based Access Control (RBAC):</strong> Student numbers are masked from non-administrative staff. Only authorized Super Administrators possess clearance to inspect full student identification records.</p>
                    <p>• <strong>Audit Trails:</strong> High-privilege administrative actions, session events, and EXP ledger adjustments are permanently recorded in system audit logs.</p>
                    <p>• <strong>Session Integrity:</strong> Real-time session monitoring invalidates outdated or compromised device tokens immediately upon credential updates.</p>
                </div>
            </section>

            <!-- 5. Data Sharing & Third Parties -->
            <section class="space-y-3">
                <h2 class="text-lg font-bold text-white font-brand-display flex items-center gap-2">
                    <span class="w-6 h-6 rounded-lg bg-[#560E0F] text-[#df747a] text-xs font-mono font-bold flex items-center justify-center">5</span>
                    Third-Party Disclosures & Non-Commercial Policy
                </h2>
                <div class="p-4 rounded-xl bg-burgundy-deep/40 border border-[#8F3033]/50 text-xs sm:text-sm text-slate-300 space-y-2">
                    <p class="font-bold text-white flex items-center gap-2">
                        <i data-lucide="shield-alert" class="w-4 h-4 text-[#df747a]"></i>
                        <span>Zero Commercial Sharing Guarantee</span>
                    </p>
                    <p>
                        COMSOC <strong>never</strong> sells, rents, commercializes, or shares member personal information with marketing brokers, advertisers, or third-party commercial entities.
                    </p>
                    <p>
                        Disclosures are restricted solely to:
                    </p>
                    <ul class="list-disc list-inside text-xs text-slate-400 space-y-1">
                        <li><strong>SorSU Bulan Campus Administration & Faculty Advisers:</strong> For official organizational compliance, student clearance, and academic accreditation audits.</li>
                        <li><strong>Authorized Technical Infrastructure Providers:</strong> Transactional SMTP infrastructure (Google Workspace) for password resets and notification delivery under strict confidentiality terms.</li>
                    </ul>
                </div>
            </section>

            <!-- 6. Retention and Deletion -->
            <section class="space-y-3">
                <h2 class="text-lg font-bold text-white font-brand-display flex items-center gap-2">
                    <span class="w-6 h-6 rounded-lg bg-[#560E0F] text-[#df747a] text-xs font-mono font-bold flex items-center justify-center">6</span>
                    Data Retention & Limitations on Record Deletion
                </h2>
                <p class="text-xs sm:text-sm text-slate-300 leading-relaxed">
                    Student profile information is maintained during the period of active enrollment at Sorsogon State University. Upon graduation or transfer:
                </p>
                <div class="p-4 rounded-xl bg-[#0B1120] border border-[#334155] text-xs sm:text-sm text-slate-300 space-y-2">
                    <p>• <strong>Retention Limitations:</strong> In compliance with academic record-keeping standards and institutional accountability, historical attendance logs, issued certificates, and audited EXP ledger transactions cannot be retroactively purged or fabricated, ensuring fraud prevention and institutional accreditation integrity.</p>
                    <p>• <strong>Account Inactivation:</strong> Inactive student accounts will be deactivated and personal authentication tokens purged after completion of the academic cycle.</p>
                </div>
            </section>

            <!-- 7. Data Subject Rights -->
            <section class="space-y-3">
                <h2 class="text-lg font-bold text-white font-brand-display flex items-center gap-2">
                    <span class="w-6 h-6 rounded-lg bg-[#560E0F] text-[#df747a] text-xs font-mono font-bold flex items-center justify-center">7</span>
                    Your Rights Under the Philippine Data Privacy Act
                </h2>
                <p class="text-xs sm:text-sm text-slate-300 leading-relaxed">
                    As a verified data subject under Republic Act No. 10173, you hold the following statutory rights:
                </p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs sm:text-sm">
                    <div class="p-3 rounded-xl bg-[#0B1120] border border-[#334155]">
                        <p class="font-bold text-white mb-1">Right to be Informed</p>
                        <p class="text-slate-400 text-xs">Right to know whether personal data concerning you are being processed and the specifics of such processing.</p>
                    </div>
                    <div class="p-3 rounded-xl bg-[#0B1120] border border-[#334155]">
                        <p class="font-bold text-white mb-1">Right to Access</p>
                        <p class="text-slate-400 text-xs">Direct access to view your complete personal profile, attendance records, and EXP transaction history via your dashboard.</p>
                    </div>
                    <div class="p-3 rounded-xl bg-[#0B1120] border border-[#334155]">
                        <p class="font-bold text-white mb-1">Right to Rectification</p>
                        <p class="text-slate-400 text-xs">Ability to modify and correct inaccurate profile details, avatar configurations, or nickname via your Profile Settings.</p>
                    </div>
                    <div class="p-3 rounded-xl bg-[#0B1120] border border-[#334155]">
                        <p class="font-bold text-white mb-1">Right to File a Complaint</p>
                        <p class="text-slate-400 text-xs">Right to seek redress or lodge complaints with the organizational Data Protection Officer or the National Privacy Commission (NPC).</p>
                    </div>
                </div>
            </section>

            <!-- 8. Contact Information -->
            <section class="space-y-3 border-t border-[#334155] pt-6">
                <h2 class="text-lg font-bold text-white font-brand-display flex items-center gap-2">
                    <span class="w-6 h-6 rounded-lg bg-[#560E0F] text-[#df747a] text-xs font-mono font-bold flex items-center justify-center">8</span>
                    Contacting the Data Protection Officer (DPO)
                </h2>
                <p class="text-xs sm:text-sm text-slate-300 leading-relaxed">
                    If you have questions, inquiries, or grievances regarding this Privacy Notice or the treatment of your personal information, you may contact the COMSOC Executive Leadership through the following official channels:
                </p>
                <div class="p-4 rounded-xl bg-[#0B1120] border border-[#334155] space-y-2 text-xs sm:text-sm text-slate-300">
                    <p>• <strong>Organization:</strong> Computing Society (COMSOC) — SorSU Bulan Campus</p>
                    <p>• <strong>Campus Address:</strong> Sorsogon State University – Bulan Campus, Zone 8, Bulan, Sorsogon, Philippines 4706</p>
                    <p>• <strong>Official Email:</strong> <a href="mailto:computingsocietysorsubc@gmail.com" class="text-[#df747a] font-mono underline">computingsocietysorsubc@gmail.com</a></p>
                    <p>• <strong>Online Help Desk:</strong> Authenticated members may submit a confidential support inquiry directly via the <a href="{{ route('helpdesk.index') }}" class="text-[#df747a] underline font-semibold">COMSOC Help Desk</a>.</p>
                </div>
            </section>

            <!-- Return Actions -->
            <div class="border-t border-[#334155] pt-6 flex flex-wrap items-center justify-between gap-4">
                <div class="text-xs text-slate-400">
                    Computing Society of SorSU – Bulan Campus • Innovating Today for a Better Community.
                </div>
                <div class="flex items-center gap-3">
                    <a href="{{ url()->previous() ?: route('login') }}" class="btn-secondary text-xs py-2 px-4 flex items-center gap-1.5">
                        <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
                        <span>Go Back</span>
                    </a>
                </div>
            </div>

        </div>
    </main>

    <!-- Shared Public Website Footer -->
    <x-public-website-footer />

    <!-- Floating Back-to-Top CTA Button -->
    <x-back-to-top />

</body>
</html>
