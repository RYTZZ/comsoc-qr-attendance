<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Attendance Sheet for Students' Activity</title>
<style>
    @page { margin: 54pt 54pt 150pt 54pt; }
    body { font-family: Arial, Helvetica, sans-serif; font-size: 11pt; color: #000; margin: 0; }
    .hdr { position: relative; height: 96pt; text-align: center; }
    .hdr .logo-l { position: absolute; left: 21pt; top: 0; width: 72pt; height: 72pt; }
    .hdr .logo-r { position: absolute; right: 8pt; top: 0; width: 77pt; height: 72pt; }
    .hdr p { margin: 0; line-height: 1.15; }
    .b { font-weight: bold; }
    .rule { border-top: 0.75pt solid #000; margin: 4pt 72pt 0 72pt; }
    h1 { font-size: 11pt; font-weight: bold; text-align: center; margin: 14pt 0 12pt 0; }
    .info p { margin: 0 0 5pt 0; font-size: 11pt; }
    .info .v { display: inline-block; border-bottom: 0.75pt solid #000; min-width: 330pt; padding-left: 2pt; }
    table.sheet { width: 504pt; border-collapse: collapse; table-layout: fixed; margin-top: 8pt; }
    table.sheet th, table.sheet td { border: 0.75pt solid #000; padding: 1pt 3pt; font-size: 9pt; vertical-align: middle; overflow: hidden; word-wrap: break-word; }
    table.sheet th { font-weight: bold; text-align: center; font-size: 10pt; }
    table.sheet tr { page-break-inside: avoid; }
    table.sheet td { height: 21pt; }
    td.c { text-align: center; }
    td.sig { text-align: center; padding: 1pt; }
    td.sig img { max-width: 78pt; max-height: 19pt; }

    .ftr-banner { position: fixed; left: -54pt; bottom: -150pt; width: 612pt; height: 79pt; }
    .ftr-banner img { width: 612pt; height: 79pt; }
    .ctrl { position: fixed; right: 0; bottom: -34pt; width: 348pt; border-collapse: collapse; table-layout: fixed; }
    .ctrl td { border: 0.75pt solid #000; font-size: 8pt; height: 16pt; padding: 0 3pt; line-height: 16pt; }
</style>
</head>
<body>
<div class="ftr-banner"><img src="{{ $banner }}" alt=""></div>
<table class="ctrl">
    <tr><td style="width:72pt">Doc. Code</td><td style="width:93pt">{{ $code }}</td><td style="width:73pt">Effectivity Date</td><td style="width:110pt">{{ $effectivity }}</td></tr>
    <tr><td>Revision No.</td><td>{{ $revision }}</td><td>Page No.:</td><td>&nbsp;</td></tr>
</table>

<div class="hdr">
    <img class="logo-l" src="{{ $logoLeft }}" alt="Sorsogon State University seal">
    <img class="logo-r" src="{{ $logoRight }}" alt="Bagong Pilipinas">
    <p style="font-size:11pt;" class="b">Republic of the Philippines</p>
    <p style="font-size:10pt;" class="b">Sorsogon State University</p>
    <p style="font-size:11pt;" class="b">Office of Student Development and Services</p>
    <p style="font-size:11pt;" class="b">STUDENT COUNCIL AFFAIRS</p>
    <p style="font-size:10pt;" class="b">Bulan Campus</p>
    <p style="font-size:10pt;">Zone 8 Bulan, Sorsogon</p>
    <p style="font-size:10pt;">Tel. No.: 056 211-0103; Email Address: sca_bc@sorsu.edu.ph</p>
    <div class="rule"></div>
</div>

<h1>ATTENDANCE SHEET FOR STUDENTS’ ACTIVITY</h1>

<div class="info">
    <p><span class="b">Name of Organization:</span> <span class="v">{{ $info['organization'] }}</span></p>
    <p><span class="b">Title of Activity:</span> <span class="v">{{ $info['title'] }}</span></p>
    <p><span class="b">Date and Time:</span> <span class="v">{{ $info['datetime'] }}</span></p>
    <p><span class="b">Venue:</span> <span class="v">{{ $info['venue'] }}</span></p>
</div>

<table class="sheet">
    <colgroup>
        <col style="width:25.4pt"><col style="width:144.6pt"><col style="width:89.9pt">
        <col style="width:88pt"><col style="width:72pt"><col style="width:84pt">
    </colgroup>
    <thead>
        <tr>
            <th>No.</th><th>Name</th><th>Position</th><th>Course/Yr. Section</th><th>Contact Number</th><th>Signature</th>
        </tr>
    </thead>
    <tbody>
    @foreach ($rows as $i => $r)
        <tr>
            <td class="c">{{ $i + 1 }}</td>
            <td>{{ $r['name'] }}</td>
            <td>{{ $r['position'] }}</td>
            <td>{{ $r['course'] }}</td>
            <td>{{ $r['contact'] }}</td>
            <td class="sig">@if ($r['signature'])<img src="{{ $r['signature'] }}" alt="">@endif</td>
        </tr>
    @endforeach
    </tbody>
</table>
</body>
</html>
