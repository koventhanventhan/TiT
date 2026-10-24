<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $gradeDisplay }} - {{ $mediumDisplay }} - Student Index Numbers</title>
    <style>
        body {
            font-family: 'notosanstamil', sans-serif;
            font-size: 10pt;
            color: #111827;
            line-height: 1.4;
            margin: 0;
            padding: 0;
            background: #ffffff;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }
        .wordmark {
            font-family: 'montserrat', sans-serif;
            font-size: 32pt;
            font-weight: bold;
            color: #0b1f4b;
            line-height: 1;
            margin: 0;
            letter-spacing: -0.5pt;
        }
        .institute-sub {
            font-size: 8.5pt;
            font-weight: 600;
            color: #4b5563;
            letter-spacing: 2pt;
            text-transform: uppercase;
            margin-top: 3px;
        }
        .title-grade {
            font-family: 'montserrat', sans-serif;
            font-size: 15pt;
            font-weight: bold;
            color: #0b1f4b;
            text-align: right;
            line-height: 1.2;
        }
        .title-medium {
            font-size: 12pt;
            font-weight: bold;
            color: #2563eb;
            text-align: right;
            margin-top: 2px;
            letter-spacing: 0.5pt;
        }
        .title-sub {
            font-size: 8.5pt;
            font-weight: 600;
            color: #6b7280;
            text-align: right;
            letter-spacing: 1.5pt;
            text-transform: uppercase;
            margin-top: 2px;
        }
        .divider {
            border-bottom: 2px solid #0b1f4b;
            margin-bottom: 15px;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
        }
        .data-table th {
            background-color: #f1f5f9;
            color: #0f172a;
            font-size: 9.5pt;
            font-weight: bold;
            padding: 8px 10px;
            border: 1px solid #cbd5e1;
            text-transform: uppercase;
            letter-spacing: 0.5pt;
        }
        .data-table td {
            padding: 7px 10px;
            border: 1px solid #e2e8f0;
            font-size: 9.5pt;
            color: #1e293b;
        }
        .row-even {
            background-color: #ffffff;
        }
        .row-odd {
            background-color: #f8fafc;
        }
        .col-num {
            width: 40px;
            text-align: center;
            color: #64748b;
            font-weight: 600;
        }
        .col-name {
            text-align: left;
            font-weight: 500;
        }
        .col-index {
            width: 140px;
            text-align: center;
            font-family: monospace;
            font-weight: bold;
            color: #0b1f4b;
            font-size: 10pt;
            letter-spacing: 0.5px;
        }
        .footer-line {
            border-top: 1px solid #cbd5e1;
            padding-top: 6px;
            font-size: 8pt;
            color: #64748b;
            width: 100%;
        }
    </style>
</head>
<body>

    <htmlpagefooter name="pageFooter">
        <table class="footer-line" style="border-collapse: collapse; width: 100%;">
            <tr>
                <td style="text-align: left; font-size: 8pt; color: #64748b;">
                    {{ ucwords(strtolower($mediumDisplay)) }}
                </td>
                <td style="text-align: right; font-size: 8pt; color: #64748b;">
                    Page {PAGENO} of {nbpg}
                </td>
            </tr>
        </table>
    </htmlpagefooter>
    <sethtmlpagefooter name="pageFooter" value="on" />

    <table class="header-table">
        <tr>
            <td style="width: 50%; vertical-align: middle;">
                <div class="wordmark">{{ $institute->index_prefix ?? 'TiT' }}</div>
                <div class="institute-sub">{{ $institute->name ?? 'INSTITUTE OF EDUCATION' }}</div>
            </td>
            <td style="width: 50%; vertical-align: middle; text-align: right;">
                <div class="title-grade">{{ $gradeDisplay }}</div>
                <div class="title-medium">{{ $mediumDisplay }}</div>
                <div class="title-sub">STUDENT INDEX NUMBERS</div>
            </td>
        </tr>
    </table>

    <div class="divider"></div>

    <table class="data-table">
        <thead>
            <tr>
                <th class="col-num">#</th>
                <th class="col-name">Student Name</th>
                <th class="col-index">Index Number</th>
            </tr>
        </thead>
        <tbody>
            @forelse($students as $idx => $s)
                <tr class="{{ $idx % 2 === 0 ? 'row-even' : 'row-odd' }}">
                    <td class="col-num">{{ $idx + 1 }}</td>
                    <td class="col-name">{{ $s->full_name ?? $s->name }}</td>
                    <td class="col-index">{{ $s->index_number }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" style="text-align: center; padding: 25px; color: #64748b;">
                        No students with assigned index numbers found for {{ $gradeDisplay }} in {{ $mediumDisplay }}.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

</body>
</html>
