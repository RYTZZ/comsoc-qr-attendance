# COMSOC Hub — Encrypted Student Signature & Official Attendance Sheet (FM-BUC-SCA-017)
## Operational Readiness, Deployment & Maintenance Guide

### 1. Architectural Overview & Security Model
- **Client-Side Signature Capture**: Implemented using the official `signature_pad` library (`^5.1.4`) via Vite / npm. Wrapped inside an Alpine.js component (`resources/js/signature.js`) supporting mouse, touchscreen, and stylus inputs.
- **Onboarding Signup Wizard (Step 4)**: The student self-registration flow in `resources/views/auth/signup.blade.php` incorporates a mandatory 4th onboarding step ("STEP 4 OF 4 — Register Your Signature"). Students cannot finalize account creation without drawing their handwritten signature and acknowledging the statutory authorization and reuse consent terms.
- **Transactional Atomicity on Registration**: Student record creation, user record initialization, profile setup, and signature persistence are strictly bound in a single `DB::transaction`. If signature validation, dimension enforcement, or encryption fails, all user and student database operations cleanly roll back.
- **Server-Side Validation & Normalization**: Signature data sent as data URLs are decoded, dimension-checked, rasterized, checked for minimum ink threshold, and re-encoded using PHP GD into clean transparent PNGs.
- **Server-Side Encryption at Rest**: Plaintext signature images are **never** stored in the database or public web directories. Signature payloads are encrypted via Laravel's `Encrypter` service using authenticated encryption (AES-256-CBC with HMAC or AES-256-GCM) prior to database insertion.
- **Dedicated Encryption Key**: The application supports a dedicated `SIGNATURE_ENCRYPTION_KEY`. If unset, it safely defaults to `APP_KEY`. `APP_KEY` is never altered.
- **Authorization & Principle of Least Privilege**:
  - Students can only view, register, or replace their **own** signature. Request-injected student IDs or foreign UUIDs are discarded.
  - Plaintext signatures and ciphertext payloads are never returned in general member APIs, profiles, or audit logs.
  - The preview endpoint (`/signature/preview`) streams binary PNG bytes with `Cache-Control: no-store, private` solely to the authenticated owner.
- **Official Attendance Sheet Export**:
  - Generated using DomPDF reproducing official document `FM-BUC-SCA-017 Attendance Sheet for Student's Activity 2026.docx`.
  - Attendees are queried exclusively from verified check-in attendance records (`attendance_records` where `action = 'in'`). Registering a signature never creates an attendance record.
  - Signatures are decrypted strictly in-memory during authorized PDF generation.
  - Issued PDFs are hashed (`SHA-256`), stored encrypted in private storage (`storage/app/attendance-sheets/`), and cataloged in `attendance_sheet_issuances` with full audit logging.
- **Document Integrity & Hash Notice**:
  > **IMPORTANT NOTICE ON DOCUMENT INTEGRITY**: The SHA-256 hash stored in `attendance_sheet_issuances` and the audit logs certifies document immutability and exact binary reproduction within the application. **This cryptographic hash does NOT constitute an X.509 cryptographic digital PDF signature (such as PAdES / Adobe digital signature certificate).** It serves as an internal tamper-detection control.

---

### 2. Deployment Steps (Hostinger VPS / Production)

1. **Pull Code and Update Dependencies**:
   ```bash
   git pull origin main
   composer install --no-dev --optimize-autoloader
   npm ci
   npm run build
   ```

2. **Environment Configuration**:
   Add the following variables to `.env` (generate a unique 32-byte key):
   ```bash
   php -r "echo 'base64:'.base64_encode(random_bytes(32)).PHP_EOL;"
   ```
   Add to `.env`:
   ```dotenv
   SIGNATURE_ENCRYPTION_KEY=base64:YOUR_GENERATED_KEY_HERE
   SIGNATURE_CIPHER=AES-256-CBC
   SIGNATURE_ARCHIVE_DISK=local
   ```
   *(Note: Back up this key in a secure credential manager separate from database backups).*

3. **Run Additive Database Migrations**:
   ```bash
   php artisan migrate --force
   ```
   *Creates tables `student_signatures` and `attendance_sheet_issuances`. No existing tables or columns are modified.*

4. **Verify Storage Permissions & PHP Extensions**:
   - Verify `storage/app` is writable by the web server user (`www-data`).
   - Ensure the PHP `gd` extension is enabled (`php -m | grep gd`).

5. **Cache Application Configuration**:
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

---

### 3. Key Rotation & Disaster Recovery

#### How Key Rotation Operates:
1. Generate a new key:
   ```bash
   php -r "echo 'base64:'.base64_encode(random_bytes(32)).PHP_EOL;"
   ```
2. Update `.env`:
   - Set `SIGNATURE_ENCRYPTION_KEY` to the new key.
   - Set `SIGNATURE_PREVIOUS_KEYS` to the old key (comma-separated list for multiple past keys):
   ```dotenv
   SIGNATURE_ENCRYPTION_KEY=base64:NEW_KEY_HERE
   SIGNATURE_PREVIOUS_KEYS=base64:OLD_KEY_HERE
   ```
3. Run `php artisan config:cache`.
4. **Verification**: Existing signatures and archived attendance PDFs remain immediately decryptable and verifiable because `SignatureVaultService` registers previous keys into Laravel's `Encrypter`.
5. *(Optional Maintenance)*: When ready to decommission the retired key permanently, re-encrypt existing rows with the primary key before removing it from `SIGNATURE_PREVIOUS_KEYS`.

#### Loss of Keys Risk:
If all encryption keys are lost, persisted signature payloads and encrypted archived PDF copies cannot be decrypted. In this failure mode:
- Students must draw and save a new signature.
- Officers can generate and issue a new attendance sheet PDF from existing attendance records.

---

### 4. Data Fallbacks & Field Mapping Rules

| Document Field | Database Source | Fallback Rule |
| :--- | :--- | :--- |
| **Name of Organization** | `events.organizer` | Falls back to `config('app.name')` (e.g., "COMSOC Hub") if null. |
| **Title of Activity** | `events.name` | Required in `events` table. |
| **Date and Time** | `events.event_date`, `events.starts_at`, `events.ends_at` | Formatted as `"F j, Y, g:i A - g:i A"`. Missing start/end times omit time component. |
| **Venue** | `events.venue_name`, `events.venue_address`, `events.location` | Combines `venue_name` and `venue_address`; falls back to `location` if empty. |
| **Name (Attendee)** | `students.last_name`, `first_name`, `middle_name` | Formatted as `"Last, First M."`; falls back to `users.name` if external guest / user record. |
| **Position** | `users.officer_position` | Falls back to `"Member"` if null. |
| **Course/Yr. Section** | `students.program`, `year_level`, `block` | Combines program, year level, and block (e.g., `"BSIT 2nd Year - A"`). Blank if unassigned. |
| **Contact Number** | `event_registrations.phone` | Matched by `user_id` and `event_id`. Leaves cell empty if not provided during registration. No dummy data is fabricated. |
| **Signature** | `student_signatures` | Active version for `user_id`. Left completely blank if unsubmitted. |

---

### 5. Rollback Procedures

If rolling back the release:
1. **Database Rollback**:
   ```bash
   php artisan migrate:rollback --step=1
   ```
   *Drops `attendance_sheet_issuances` and `student_signatures`. Does not affect `users`, `students`, `events`, or `attendance_records`.*
2. **Code Rollback**:
   Revert git commits.
3. **Rebuild Assets**:
   ```bash
   npm ci && npm run build
   php artisan optimize:clear
   ```
