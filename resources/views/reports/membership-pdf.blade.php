<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>ComSoc Membership Report</title>
    <style>
        body { font-family: sans-serif; font-size: 11px; color: #1e293b; margin: 20px; }
        h1 { font-size: 16px; margin-bottom: 4px; }
        p.subtitle { color: #64748b; font-size: 10px; margin-top: 0; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #cbd5e1; padding: 6px 8px; text-align: left; }
        th { background-color: #f1f5f9; font-size: 10px; text-transform: uppercase; letter-spacing: 0.5px; }
        .status-active { color: #16a34a; font-weight: bold; }
        .status-inactive { color: #94a3b8; }
    </style>
</head>
<body>
    <div style="margin-bottom: 16px; border-bottom: 2px solid #7A1618; padding-bottom: 12px;">
        <table style="width: 100%; border: none; margin-top: 0;">
            <tr style="border: none;">
                <td style="width: 60px; border: none; padding: 0; vertical-align: middle;">
                    @if(file_exists(public_path('images/COMSOC.png')))
                        <img src="{{ public_path('images/COMSOC.png') }}" style="width: 50px; height: 50px; object-fit: contain;">
                    @endif
                </td>
                <td style="border: none; padding-left: 12px; vertical-align: middle;">
                    <h1 style="margin: 0; font-size: 16px; color: #303644;">Computing Society (ComSoc)</h1>
                    <p class="subtitle" style="margin: 2px 0 0 0;">Official Membership Report — Generated {{ now()->format('F j, Y h:i A') }}</p>
                </td>
            </tr>
        </table>
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Student Number</th>
                <th>Full Name</th>
                <th>Program & Year</th>
                <th>Academic Year</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data as $i => $row)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $row->student?->student_number ?? '—' }}</td>
                    <td>{{ $row->student?->display_name ?? '—' }}</td>
                    <td>{{ ($row->student?->program ?: 'BSIT') . ' • ' . ($row->student?->year_level ?: '—') }}</td>
                    <td>{{ $row->academicYear?->label ?? '—' }}</td>
                    <td class="{{ $row->status === 'active' ? 'status-active' : 'status-inactive' }}">{{ strtoupper($row->status) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
