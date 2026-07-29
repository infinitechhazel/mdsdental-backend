<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Confirmed</title>

    <style>
        body {
            margin: 0;
            padding: 0;
            background: #eef1f5;
            font-family: Arial, Helvetica, sans-serif;
        }

        table {
            border-spacing: 0;
            border-collapse: collapse;
        }

        img {
            border: 0;
            display: block;
            max-width: 100%;
        }

        @media only screen and (max-width: 600px) {

            .container {
                width: 100% !important;
                max-width: 100% !important;
            }

            .content,
            .header,
            .footer {
                padding: 24px 20px !important;
            }

            .mobile-stack td {
                display: block !important;
                width: 100% !important;
                text-align: left !important;
            }

            .mobile-badge {
                padding-top: 15px !important;
                text-align: left !important;
            }

            h2 {
                font-size: 24px !important;
                line-height: 32px !important;
            }

            h3 {
                font-size: 17px !important;
            }

            td,
            p {
                font-size: 15px !important;
                line-height: 24px !important;
            }

            .label,
            .value {
                display: block !important;
                width: 100% !important;
                padding: 8px 0 !important;
            }
        }
    </style>
</head>

<body>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
        style="background:#eef1f5;padding:40px 15px;">
        <tr>
            <td align="center">

                <table class="container" role="presentation" width="100%" cellpadding="0" cellspacing="0"
                    style="max-width:600px;background:#ffffff;border-radius:10px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.08);">

                    <!-- Header -->
                    <tr>
                        <td class="header" style="background:#1d4ed8;padding:28px 30px;">
                            <table role="presentation" width="100%">
                                <tr class="mobile-stack">

                                    <td style="color:#ffffff;font-size:20px;font-weight:bold;">
                                        {{ config('app.name') }}
                                    </td>

                                    <td class="mobile-badge" align="right">
                                        <span
                                            style="display:inline-block;background:#ffffff;color:#1d4ed8;font-size:12px;font-weight:bold;padding:7px 14px;border-radius:30px;letter-spacing:.5px;">
                                            CONFIRMED
                                        </span>
                                    </td>

                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Body -->
                    <tr>
                        <td class="content" style="padding:35px 30px;">

                            <h2 style="margin:0 0 18px;color:#111111;font-size:24px;">
                                Appointment Confirmed
                            </h2>

                            <p style="margin:0 0 12px;color:#333333;font-size:15px;line-height:1.7;">
                                Hello <strong>{{ $booking->name }}</strong>,
                            </p>

                            <p style="margin:0 0 25px;color:#333333;font-size:15px;line-height:1.7;">
                                Great news! Your appointment has been successfully confirmed. We look forward to
                                welcoming you and providing you with the best possible service.
                            </p>

                           

                            <h3
                                style="margin:0 0 15px;color:#111111;font-size:16px;border-bottom:2px solid #1d4ed8;padding-bottom:8px;">
                                Booking Details
                            </h3>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">

                                <tr>
                                    <td class="label"
                                        style="width:35%;padding:12px 0;border-bottom:1px solid #eeeeee;color:#666666;font-size:14px;">
                                        Appointment Date
                                    </td>

                                    <td class="value"
                                        style="padding:12px 0;border-bottom:1px solid #eeeeee;color:#111111;font-size:14px;font-weight:bold;">
                                        {{ \Carbon\Carbon::parse($booking->booking_date)->format('F d, Y g:i A') }}
                                    </td>
                                </tr>

                                <tr>
                                    <td class="label"
                                        style="padding:12px 0;color:#666666;font-size:14px;">
                                        Status
                                    </td>

                                    <td class="value"
                                        style="padding:12px 0;color:#1d4ed8;font-size:14px;font-weight:bold;">
                                        Confirmed
                                    </td>
                                </tr>
 @if($booking->notes)
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                                style="margin:0 0 25px;background:#f4f7ff;border-left:4px solid #1d4ed8;border-radius:6px;">

                                <tr>
                                    <td style="padding:16px 18px;">

                                        <p
                                            style="margin:0 0 6px;color:#1d4ed8;font-size:13px;font-weight:bold;text-transform:uppercase;">
                                            Message
                                        </p>

                                        <p style="margin:0;color:#333333;font-size:14px;line-height:1.7;">
                                            {{ $booking->notes }}
                                        </p>

                                    </td>
                                </tr>

                            </table>
                            @endif
                            </table>

                            <p style="margin:25px 0 0;color:#666666;font-size:14px;line-height:1.8;">
                                Please arrive a few minutes before your scheduled appointment. If you need to make any changes, kindly contact our clinic in advance. 
                                This is an automated email, and replies to this message are not monitored.
                            </p>

                            </p>

                        </td>
                        
                    </tr>
                    

                    <!-- Footer -->
                    <tr>
                        <td class="footer"
                            style="background:#f4f6fa;padding:24px 30px;text-align:center;">

                            <p style="margin:0;color:#999999;font-size:12px;line-height:20px;">
                                Thank you for choosing<br>
                                <strong>{{ config('app.name') }}</strong>
                            </p>

                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>

</body>

</html>