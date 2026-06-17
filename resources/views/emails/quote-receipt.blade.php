<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Your FlowEdu Proforma Invoice</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #f3f4f6;
            margin: 0;
            padding: 20px;
            color: #1f2937;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            overflow: hidden;
        }
        .header {
            background-color: #059669; /* Ghanaian/College green */
            color: #ffffff;
            padding: 28px 24px;
            text-align: center;
        }
        .header h2 {
            margin: 0;
            font-size: 22px;
            font-weight: 700;
        }
        .content {
            padding: 24px;
            font-size: 15px;
            line-height: 1.6;
            color: #4b5563;
        }
        .content p {
            margin-top: 0;
            margin-bottom: 16px;
        }
        .summary-box {
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 16px;
            margin-bottom: 24px;
        }
        .summary-box h3 {
            margin-top: 0;
            margin-bottom: 12px;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #111827;
        }
        .summary-table {
            width: 100%;
            border-collapse: collapse;
        }
        .summary-table td {
            padding: 6px 0;
            font-size: 14px;
        }
        .summary-table td.label {
            color: #6b7280;
        }
        .summary-table td.val {
            text-align: right;
            font-weight: 600;
            color: #111827;
        }
        .summary-table tr.total-row td {
            padding-top: 12px;
            border-top: 1px solid #e5e7eb;
            font-weight: 700;
            font-size: 15px;
        }
        .summary-table tr.total-row td.val {
            color: #059669;
        }
        .footer {
            background-color: #f9fafb;
            padding: 16px;
            text-align: center;
            font-size: 12px;
            color: #9ca3af;
            border-top: 1px solid #e5e7eb;
        }
        .button {
            display: inline-block;
            background-color: #059669;
            color: #ffffff;
            text-decoration: none;
            padding: 12px 24px;
            border-radius: 6px;
            font-weight: 700;
            font-size: 14px;
            margin: 16px 0;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Your FlowEdu Invoice & Receipt</h2>
        </div>
        <div class="content">
            <p>Dear {{ $data['name'] }},</p>
            <p>Thank you for expressing interest in **FlowEdu** by Matme Inc. We have processed your quote request for **{{ $data['college_name'] }}**.</p>
            
            <p>We have attached a copy of your detailed <strong>Proforma Invoice (PDF)</strong> containing your fully customized configuration breakdown and license pricing.</p>

            <div class="summary-box">
                <h3>Quotation Summary</h3>
                <table class="summary-table">
                    <tr>
                        <td class="label">Institution Name</td>
                        <td class="val">{{ $data['college_name'] }}</td>
                    </tr>
                    <tr>
                        <td class="label">Student Capacity</td>
                        <td class="val">{{ $pricing['band_label'] }}</td>
                    </tr>
                    <tr>
                        <td class="label">Modules Configured</td>
                        <td class="val">{{ count($pricing['modules']) }} module(s)</td>
                    </tr>
                    <tr>
                        <td class="label">Hosting Setup</td>
                        <td class="val">{{ $pricing['hosting_label'] }}</td>
                    </tr>
                    @if($pricing['is_custom'])
                        <tr class="total-row">
                            <td class="label">Estimate</td>
                            <td class="val">Custom Quote Required</td>
                        </tr>
                    @else
                        <tr class="total-row">
                            <td class="label">One-time Setup (Year 1)</td>
                            <td class="val">{{ $pricing['currency'] }} {{ number_format($pricing['upfront_total'], 2) }}</td>
                        </tr>
                        <tr>
                            <td class="label">Annual Renewal (Year 2+)</td>
                            <td class="val">{{ $pricing['currency'] }} {{ number_format($pricing['renew_total'], 2) }} / yr</td>
                        </tr>
                    @endif
                </table>
            </div>

            <p>Our implementation and systems integration team will follow up with you within one business day to discuss deployment logistics, timelines, and answer any questions you might have.</p>
            
            <p>If you need to contact us immediately, please reply directly to this email or call us at <strong>0249100268</strong>.</p>
            
            <p>Best regards,<br><strong>FlowEdu Integration Team</strong><br>Matme Inc.</p>
        </div>
        <div class="footer">
            FlowEdu Landing Page Notification &bull; Matme Inc &bull; Accra, Ghana
        </div>
    </div>
</body>
</html>
