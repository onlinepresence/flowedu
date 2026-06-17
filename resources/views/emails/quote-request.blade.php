<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>New Quote Request Details</title>
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
            padding: 24px;
            text-align: center;
        }
        .header h2 {
            margin: 0;
            font-size: 20px;
            font-weight: 700;
        }
        .content {
            padding: 24px;
        }
        .section-title {
            font-size: 14px;
            font-weight: 700;
            color: #374151;
            text-transform: uppercase;
            border-bottom: 2px solid #f3f4f6;
            padding-bottom: 6px;
            margin-top: 24px;
            margin-bottom: 12px;
        }
        .details-table {
            width: 100%;
            border-collapse: collapse;
        }
        .details-table th, .details-table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
            font-size: 14px;
        }
        .details-table th {
            width: 40%;
            color: #4b5563;
            font-weight: 600;
        }
        .details-table td {
            color: #111827;
        }
        .pricing-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }
        .pricing-table th, .pricing-table td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #f3f4f6;
        }
        .pricing-table th {
            background-color: #f9fafb;
            color: #4b5563;
            font-weight: 600;
        }
        .pricing-table td.num {
            text-align: right;
            font-family: monospace;
        }
        .pricing-table tr.total-row td {
            font-weight: 700;
            font-size: 14px;
            border-top: 2px solid #e5e7eb;
            border-bottom: 2px solid #e5e7eb;
        }
        .module-list {
            margin: 0;
            padding-left: 20px;
        }
        .message-box {
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            padding: 12px;
            font-size: 14px;
            color: #374151;
            white-space: pre-line;
            margin-top: 8px;
        }
        .footer {
            background-color: #f9fafb;
            padding: 16px;
            text-align: center;
            font-size: 12px;
            color: #9ca3af;
            border-top: 1px solid #e5e7eb;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>New Quote Request Received</h2>
        </div>
        <div class="content">
            <p style="font-size: 15px; line-height: 1.5; color: #4b5563;">
                A new quote request has been submitted from the FlowEdu Landing Page. Below are the contact and pricing details:
            </p>

            <div class="section-title">Contact Information</div>
            <table class="details-table">
                <tr>
                    <th>College Name</th>
                    <td>{{ $data['college_name'] ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>Contact Name</th>
                    <td>{{ $data['name'] ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>Role / Position</th>
                    <td>{{ $data['role'] ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>Phone Number</th>
                    <td>{{ $data['phone'] ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>Email Address</th>
                    <td><a href="mailto:{{ $data['email'] ?? '' }}">{{ $data['email'] ?? 'N/A' }}</a></td>
                </tr>
            </table>

            <div class="section-title">Quotation Configuration</div>
            <table class="details-table">
                <tr>
                    <th>Student Population Band</th>
                    <td>{{ $pricing['band_label'] }} (Multiplier: {{ $pricing['multiplier'] ?? '1.0' }}x)</td>
                </tr>
                <tr>
                    <th>Hosting Setup</th>
                    <td>{{ $pricing['hosting_label'] }}</td>
                </tr>
                <tr>
                    <th>Founding Discount Status</th>
                    <td>{{ $pricing['apply_founding'] ? 'Applied (15% off core)' : 'Not Applied' }}</td>
                </tr>
            </table>

            @if(!$pricing['is_custom'])
                <div class="section-title">Quotation Pricing Details ({{ $pricing['currency'] }})</div>
                <table class="pricing-table">
                    <thead>
                        <tr>
                            <th>Item Description</th>
                            <th style="text-align: right;">Upfront (Y1)</th>
                            <th style="text-align: right;">Renewal (Y2+)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Core -->
                        <tr>
                            <td>Core Academic Licence</td>
                            <td class="num">{{ number_format($pricing['core_upfront'], 2) }}</td>
                            <td class="num">{{ number_format($pricing['core_renewal'], 2) }}</td>
                        </tr>
                        @if($pricing['apply_founding'])
                            <tr style="color: #059669; font-weight: 500;">
                                <td>&nbsp;&nbsp;Founding Client Discount (-15%)</td>
                                <td class="num">-{{ number_format($pricing['founding_discount_upfront'], 2) }}</td>
                                <td class="num">-{{ number_format($pricing['founding_discount_renew'], 2) }}</td>
                            </tr>
                        @endif

                        <!-- Modules -->
                        @if(!empty($pricing['modules']))
                            <tr>
                                <td colspan="3" style="font-weight: 600; background-color: #f9fafb;">Modules</td>
                            </tr>
                            @foreach($pricing['modules'] as $mod)
                                <tr>
                                    <td>&nbsp;&nbsp;{{ $mod['label'] }}</td>
                                    <td class="num">{{ number_format($mod['onetime'], 2) }}</td>
                                    <td class="num">{{ number_format($mod['renew'], 2) }}</td>
                                </tr>
                            @endforeach
                            @if($pricing['apply_bundle'])
                                <tr style="color: #059669; font-weight: 500;">
                                    <td>&nbsp;&nbsp;Bundle Discount (-12%)</td>
                                    <td class="num">-{{ number_format($pricing['bundle_discount_onetime'], 2) }}</td>
                                    <td class="num">-{{ number_format($pricing['bundle_discount_renew'], 2) }}</td>
                                </tr>
                            @endif
                        @endif

                        <!-- Hosting -->
                        <tr>
                            <td>Hosting Setup ({{ $pricing['hosting_label'] }})</td>
                            <td class="num">{{ number_format($pricing['hosting_setup_fee'], 2) }}</td>
                            <td class="num">0.00</td>
                        </tr>

                        <!-- Addons -->
                        @if(!empty($pricing['addons']))
                            <tr>
                                <td colspan="3" style="font-weight: 600; background-color: #f9fafb;">Implementation Addons</td>
                            </tr>
                            @foreach($pricing['addons'] as $addon)
                                <tr>
                                    <td>&nbsp;&nbsp;{{ $addon['label'] }}</td>
                                    <td class="num">{{ number_format($addon['price'], 2) }}</td>
                                    <td class="num">0.00</td>
                                </tr>
                            @endforeach
                        @endif

                        <!-- Training -->
                        @if(!empty($pricing['trainings']))
                            <tr>
                                <td colspan="3" style="font-weight: 600; background-color: #f9fafb;">Staff Training</td>
                            </tr>
                            @foreach($pricing['trainings'] as $t)
                                <tr>
                                    <td>&nbsp;&nbsp;{{ $t['label'] }}</td>
                                    <td class="num">{{ number_format($t['price'], 2) }}</td>
                                    <td class="num">0.00</td>
                                </tr>
                            @endforeach
                        @endif

                        <!-- Grand Totals -->
                        <tr class="total-row">
                            <td>GRAND TOTALS</td>
                            <td class="num" style="color: #059669;">{{ number_format($pricing['upfront_total'], 2) }}</td>
                            <td class="num" style="color: #059669;">{{ number_format($pricing['renew_total'], 2) }}</td>
                        </tr>
                    </tbody>
                </table>
            @else
                <div class="section-title">Quotation Pricing Details</div>
                <p style="font-size: 14px; font-weight: bold; color: #b91c1c;">
                    This client requested a CUSTOM quote for 3,500+ students. Please reach out to construct a custom pricing profile.
                </p>
            @endif

            @if(!empty($data['message']))
                <div class="section-title">Additional Message</div>
                <div class="message-box">{{ $data['message'] }}</div>
            @endif
        </div>
        <div class="footer">
            FlowEdu Landing Page Notification &bull; Matme Inc
        </div>
    </div>
</body>
</html>
