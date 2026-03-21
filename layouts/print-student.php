<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= htmlspecialchars($title ?? 'Student record', ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet" />
    <style>
        :root {
            --ps-ink: #0f172a;
            --ps-muted: #64748b;
            --ps-accent: #1d4ed8;
            --ps-accent-soft: #eff6ff;
            --ps-card: #ffffff;
            --ps-border: #e2e8f0;
            --ps-bg: #f1f5f9;
            --ps-radius: 12px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: "Plus Jakarta Sans", system-ui, sans-serif;
            font-size: 15px;
            line-height: 1.5;
            color: var(--ps-ink);
            background: var(--ps-bg);
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .print-toolbar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 14px 20px;
            background: var(--ps-card);
            border-bottom: 1px solid var(--ps-border);
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .print-toolbar a {
            color: var(--ps-accent);
            text-decoration: none;
            font-weight: 600;
        }

        .print-toolbar a:hover {
            text-decoration: underline;
        }

        .print-toolbar button {
            font-family: inherit;
            font-weight: 600;
            padding: 10px 18px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            background: var(--ps-accent);
            color: #fff;
        }

        .print-toolbar button:hover {
            filter: brightness(1.05);
        }

        .print-shell {
            max-width: 900px;
            margin: 0 auto;
            padding: 24px 20px 40px;
        }

        .ps-school {
            text-align: center;
            padding: 28px 20px 24px;
            margin-bottom: 24px;
            border-radius: var(--ps-radius);
            background: linear-gradient(135deg, #1e3a8a 0%, #1d4ed8 55%, #3b82f6 100%);
            color: #fff;
            box-shadow: 0 10px 40px rgba(29, 78, 216, 0.25);
        }

        .ps-school h1 {
            margin: 0;
            font-size: 1.65rem;
            font-weight: 700;
            letter-spacing: -0.02em;
        }

        .ps-school p {
            margin: 8px 0 0;
            font-size: 0.9rem;
            opacity: 0.92;
            max-width: 36rem;
            margin-left: auto;
            margin-right: auto;
        }

        .ps-school .ps-tag {
            display: inline-block;
            margin-top: 14px;
            padding: 6px 14px;
            border-radius: 999px;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            background: rgba(255, 255, 255, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.25);
        }

        .ps-hero {
            display: grid;
            grid-template-columns: 200px 1fr;
            gap: 24px;
            align-items: start;
            padding: 24px;
            margin-bottom: 20px;
            background: var(--ps-card);
            border-radius: var(--ps-radius);
            border: 1px solid var(--ps-border);
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.06);
        }

        @media (max-width: 640px) {
            .ps-hero {
                grid-template-columns: 1fr;
                justify-items: center;
                text-align: center;
            }
        }

        .ps-photo-wrap {
            width: 200px;
            height: 250px;
            border-radius: 10px;
            overflow: hidden;
            border: 1px solid var(--ps-border);
            background: var(--ps-accent-soft);
            flex-shrink: 0;
        }

        .ps-photo-wrap img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .ps-photo-placeholder {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--ps-muted);
            font-size: 0.85rem;
            font-weight: 500;
            text-align: center;
            padding: 12px;
        }

        .ps-biodata h2 {
            margin: 0 0 4px;
            font-size: 1.35rem;
            font-weight: 700;
            letter-spacing: -0.02em;
        }

        .ps-biodata .ps-index {
            font-size: 0.9rem;
            color: var(--ps-muted);
            font-weight: 600;
            margin-bottom: 16px;
        }

        .ps-dl {
            display: grid;
            grid-template-columns: 140px 1fr;
            gap: 8px 16px;
            font-size: 0.9rem;
        }

        .ps-dl dt {
            margin: 0;
            color: var(--ps-muted);
            font-weight: 500;
        }

        .ps-dl dd {
            margin: 0;
            font-weight: 600;
        }

        @media (max-width: 480px) {
            .ps-dl {
                grid-template-columns: 1fr;
            }
        }

        .ps-grid {
            display: grid;
            gap: 16px;
        }

        @media (min-width: 640px) {
            .ps-grid.cols-2 {
                grid-template-columns: 1fr 1fr;
            }
        }

        .ps-card {
            padding: 20px 22px;
            background: var(--ps-card);
            border-radius: var(--ps-radius);
            border: 1px solid var(--ps-border);
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.05);
            break-inside: avoid;
        }

        .ps-card h3 {
            margin: 0 0 14px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--ps-accent);
        }

        .ps-card .ps-dl {
            font-size: 0.875rem;
        }

        .ps-foot {
            margin-top: 28px;
            padding-top: 16px;
            border-top: 1px dashed var(--ps-border);
            font-size: 0.75rem;
            color: var(--ps-muted);
            text-align: center;
        }

        .ps-error {
            padding: 32px 24px;
            text-align: center;
            background: var(--ps-card);
            border-radius: var(--ps-radius);
            border: 1px solid var(--ps-border);
        }

        .ps-error p {
            margin: 0 0 20px;
            color: var(--ps-muted);
            font-weight: 500;
        }

        @media print {
            @page {
                size: A4;
                margin: 14mm;
            }

            body {
                background: #fff;
            }

            .no-print {
                display: none !important;
            }

            .print-shell {
                max-width: none;
                padding: 0;
                margin: 0;
            }

            .ps-card {
                box-shadow: none !important;
                border: 1px solid #cbd5e1 !important;
                break-inside: avoid;
                page-break-inside: avoid;
            }

            .ps-hero {
                break-inside: avoid;
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>
    <div class="no-print print-toolbar">
        <a href="<?= htmlspecialchars(url('/admin/students'), ENT_QUOTES, 'UTF-8') ?>">← Back to students</a>
        <button type="button" onclick="window.print()">Print</button>
    </div>

    <div class="print-shell">
        <?= $content ?? '' ?>
    </div>
</body>
</html>
<?php flush_session(); ?>
