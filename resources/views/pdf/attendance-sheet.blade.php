<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Attendance Sheet for Students' Activity</title>
<style>
    @page {
        margin-top: 58pt;
        margin-left: 22.5pt;
        margin-right: 22.5pt;
        margin-bottom: 140pt;
    }
    body {
        font-family: Arial, Helvetica, sans-serif;
        font-size: 10pt;
        color: #000;
        margin: 0;
        padding: 0;
    }

    /* Fixed Institutional Footer */
    .ftr-banner {
        position: fixed;
        left: -22.5pt;
        bottom: -140pt;
        width: 612pt;
        height: 80.25pt;
    }
    .ftr-banner img {
        width: 612pt;
        height: 80.25pt;
        display: block;
    }
    .ctrl {
        position: fixed;
        right: 0;
        bottom: -62pt;
        width: 348pt;
        border-collapse: collapse;
        table-layout: fixed;
    }
    .ctrl td {
        border: 0.75pt solid #000;
        font-size: 8pt;
        height: 11pt;
        padding: 0 3pt;
        line-height: 11pt;
    }

    /* Institutional Header */
    .hdr {
        position: relative;
        text-align: center;
        height: 94pt;
    }
    .hdr .logo-l {
        position: absolute;
        left: 53.25pt;
        top: 4pt;
        width: 72pt;
        height: 72pt;
    }
    .hdr .logo-r {
        position: absolute;
        right: 47.25pt;
        top: 4pt;
        width: 77.25pt;
        height: 72pt;
    }
    .hdr p {
        margin: 0;
        line-height: 1.15;
    }
    .hdr .t1 { font-size: 10.5pt; font-weight: bold; }
    .hdr .t2 { font-size: 10pt; font-weight: bold; }
    .hdr .t3 { font-size: 10.5pt; font-weight: bold; }
    .hdr .t4 { font-size: 10.5pt; font-weight: bold; }
    .hdr .t5 { font-size: 10pt; font-weight: bold; }
    .hdr .t6 { font-size: 9.5pt; }
    .hdr .t7 { font-size: 9.5pt; }
    .rule {
        border-top: 1.5pt solid #000;
        width: 360pt;
        margin: 4pt auto 0 auto;
    }

    /* Document Title */
    h1 {
        font-size: 11pt;
        font-weight: bold;
        text-align: center;
        margin: 9pt 0 8pt 0;
        letter-spacing: 0.2pt;
    }

    /* Activity Details */
    .info {
        margin: 0 0 10pt 0;
        width: 567pt;
    }
    .info table {
        width: 100%;
        border-collapse: collapse;
    }
    .info td {
        padding: 1.8pt 0;
        vertical-align: bottom;
        font-size: 9pt;
    }
    .info td.lbl {
        font-weight: bold;
        white-space: nowrap;
        width: 1%;
        padding-right: 5pt;
    }
    .info td.val {
        border-bottom: 0.75pt solid #000;
        padding-left: 3pt;
    }

    /* 6-Column Attendance Table */
    table.sheet {
        width: 567.0pt;
        border-collapse: collapse;
        table-layout: fixed;
        margin: 0;
    }
    table.sheet th, table.sheet td {
        border: 0.75pt solid #000;
        padding: 0 3pt;
        vertical-align: middle;
        overflow: hidden;
        white-space: nowrap;
    }
    table.sheet th {
        font-weight: bold;
        text-align: center;
        font-size: 8.5pt;
        height: 24pt;
        line-height: 10.5pt;
    }
    table.sheet tr {
        page-break-inside: avoid;
    }
    table.sheet td {
        height: 11.5pt;
        line-height: 10.5pt;
        font-size: 7.5pt;
    }
    td.c { text-align: center; }
    td.sig { text-align: center; padding: 0 1pt; }
    td.sig img {
        max-width: 86pt;
        max-height: 11pt;
        vertical-align: middle;
        object-fit: contain;
    }
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
    <p class="t1">Republic of the Philippines</p>
    <p class="t2">Sorsogon State University</p>
    <p class="t3">Office of Student Development and Services</p>
    <p class="t4">STUDENT COUNCIL AFFAIRS</p>
    <p class="t5">Bulan Campus</p>
    <p class="t6">Zone 8 Bulan, Sorsogon</p>
    <p class="t7">Tel. No.: 056 211-0103; Email Address: sca_bc@sorsu.edu.ph</p>
    <div class="rule"></div>
</div>

<h1>ATTENDANCE SHEET FOR STUDENTS’ ACTIVITY</h1>

<div class="info">
    <table>
        <tr><td class="lbl">Name of Organization:</td><td class="val">{{ $info['organization'] }}</td></tr>
        <tr><td class="lbl">Title of Activity:</td><td class="val">{{ $info['title'] }}</td></tr>
        <tr><td class="lbl">Date and Time:</td><td class="val">{{ $info['datetime'] }}</td></tr>
        <tr><td class="lbl">Venue:</td><td class="val">{{ $info['venue'] }}</td></tr>
    </table>
</div>

<table class="sheet">
    <tr style="height:0; line-height:0; font-size:0;">
        <td style="width: 5.115%; height:0; padding:0; border:0; line-height:0; font-size:0;"></td>
        <td style="width: 28.571%; height:0; padding:0; border:0; line-height:0; font-size:0;"></td>
        <td style="width: 17.989%; height:0; padding:0; border:0; line-height:0; font-size:0;"></td>
        <td style="width: 17.460%; height:0; padding:0; border:0; line-height:0; font-size:0;"></td>
        <td style="width: 14.286%; height:0; padding:0; border:0; line-height:0; font-size:0;"></td>
        <td style="width: 16.578%; height:0; padding:0; border:0; line-height:0; font-size:0;"></td>
    </tr>
    <thead>
        <tr>
            <th>No.</th><th>Name</th><th>Position</th><th>Course/Yr. Section</th><th>Contact Number</th><th>Signature</th>
        </tr>
    </thead>
    <tbody>
    @foreach ($rows as $i => $r)
        <tr>
            <td class="c">{{ $i + 1 }}</td>
            <td>{{ $r['name'] ?? '' }}</td>
            <td>{{ $r['position'] ?? '' }}</td>
            <td>{{ $r['course'] ?? '' }}</td>
            <td>{{ $r['contact'] ?? '' }}</td>
            <td class="sig">@if (!empty($r['signature']))<img src="{{ $r['signature'] }}" alt="">@endif</td>
        </tr>
    @endforeach
    @if (!empty($padTo) && count($rows) < $padTo)
        @for ($i = count($rows); $i < $padTo; $i++)
            <tr>
                <td class="c">{{ $i + 1 }}</td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td class="sig"></td>
            </tr>
        @endfor
    @endif
    </tbody>
</table>
</body>
</html>
