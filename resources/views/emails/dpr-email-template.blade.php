<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{ $subject ?? 'Daily Payment Request Notification' }}</title>
</head>

<body
    style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; background-color: #f4f7fa; color: #333333;">

    <!-- Email Container -->
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%"
        style="background-color: #f4f7fa;">
        <tr>
            <td style="padding: 20px 0;">

                <!-- Main Content -->
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="600"
                    style="margin: 0 auto; background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">

                    <!-- Header -->
                    <tr>
                        <td
                            style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; text-align: center; border-radius: 8px 8px 0 0;">
                            <h1 style="margin: 0; color: #ffffff; font-size: 24px; font-weight: 600;">
                                {{ $notificationType ?? 'Notifikasi Permintaan Pembayaran' }}
                            </h1>
                            @if (isset($requestNumber))
                                <p style="margin: 10px 0 0 0; color: #e0e7ff; font-size: 14px;">
                                    {{ $requestNumber }}
                                </p>
                            @endif
                        </td>
                    </tr>

                    <!-- Status Badge -->
                    @if (isset($status))
                        <tr>
                            <td style="padding: 20px 30px 0 30px; text-align: center;">
                                <span
                                    style="display: inline-block; padding: 8px 20px; border-radius: 20px; font-size: 13px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;
                                @if ($status === 'pending') background-color: #fef3c7; color: #92400e;
                                @elseif($status === 'approved') background-color: #d1fae5; color: #065f46;
                                @elseif($status === 'rejected') background-color: #fee2e2; color: #991b1b;
                                @elseif($status === 'draft') background-color: #e5e7eb; color: #374151;
                                @else background-color: #dbeafe; color: #1e40af; @endif">
                                    {{ $status }}
                                </span>
                            </td>
                        </tr>
                    @endif

                    <!-- Greeting -->
                    <tr>
                        <td style="padding: 30px 30px 20px 30px;">
                            <p style="margin: 0; font-size: 16px; line-height: 1.6; color: #4b5563;">
                                Halo <strong>{{ $recipientName ?? 'User' }}</strong>,
                            </p>
                            <p style="margin: 15px 0 0 0; font-size: 15px; line-height: 1.6; color: #6b7280;">
                                {{ $msg ?? 'Anda memiliki notifikasi terkait permintaan pembayaran.' }}
                            </p>
                        </td>
                    </tr>

                    <!-- Request Summary Box -->
                    <tr>
                        <td style="padding: 0 30px 20px 30px;">
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%"
                                style="background-color: #f9fafb; border-radius: 8px; border: 1px solid #e5e7eb;">

                                <!-- Request Details -->
                                <tr>
                                    <td style="padding: 20px;">
                                        <h2
                                            style="margin: 0 0 15px 0; font-size: 16px; font-weight: 600; color: #111827;">
                                            Detail Permintaan
                                        </h2>

                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0"
                                            width="100%">
                                            @if (isset($requesterName))
                                                <tr>
                                                    <td
                                                        style="padding: 8px 0; font-size: 14px; color: #6b7280; width: 140px;">
                                                        Pemohon
                                                    </td>
                                                    <td
                                                        style="padding: 8px 0; font-size: 14px; color: #111827; font-weight: 500;">
                                                        {{ $requesterName }}
                                                    </td>
                                                </tr>
                                            @endif

                                            @if (isset($requesterDepartment))
                                                <tr>
                                                    <td style="padding: 8px 0; font-size: 14px; color: #6b7280;">
                                                        Departemen
                                                    </td>
                                                    <td
                                                        style="padding: 8px 0; font-size: 14px; color: #111827; font-weight: 500;">
                                                        {{ $requesterDepartment }}
                                                    </td>
                                                </tr>
                                            @endif

                                            @if (isset($requestDate))
                                                <tr>
                                                    <td style="padding: 8px 0; font-size: 14px; color: #6b7280;">
                                                        Tanggal
                                                    </td>
                                                    <td
                                                        style="padding: 8px 0; font-size: 14px; color: #111827; font-weight: 500;">
                                                        {{ $requestDate }}
                                                    </td>
                                                </tr>
                                            @endif

                                            @if (isset($totalAmount))
                                                <tr>
                                                    <td style="padding: 8px 0; font-size: 14px; color: #6b7280;">
                                                        Total Nominal
                                                    </td>
                                                    <td
                                                        style="padding: 8px 0; font-size: 18px; color: #059669; font-weight: 700;">
                                                        {{ $totalAmount }}
                                                    </td>
                                                </tr>
                                            @endif

                                            @if (isset($itemCount))
                                                <tr>
                                                    <td style="padding: 8px 0; font-size: 14px; color: #6b7280;">
                                                        Jumlah Item
                                                    </td>
                                                    <td
                                                        style="padding: 8px 0; font-size: 14px; color: #111827; font-weight: 500;">
                                                        {{ $itemCount }} item
                                                    </td>
                                                </tr>
                                            @endif
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Request Items -->
                    @if (isset($requestItems) && count($requestItems) > 0)
                        <tr>
                            <td style="padding: 0 30px 20px 30px;">
                                <h2 style="margin: 0 0 15px 0; font-size: 16px; font-weight: 600; color: #111827;">
                                    Rincian Item Permintaan
                                </h2>

                                @foreach ($requestItems as $index => $item)
                                    <table role="presentation" cellspacing="0" cellpadding="0" border="0"
                                        width="100%"
                                        style="margin-bottom: 12px; background-color: #ffffff; border-radius: 6px; border: 1px solid #e5e7eb;">
                                        <tr>
                                            <td style="padding: 15px;">
                                                <!-- Item Header -->
                                                <div
                                                    style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 12px;">
                                                    <span
                                                        style="display: inline-block; background-color: #6366f1; color: #ffffff; font-size: 11px; font-weight: 600; padding: 4px 10px; border-radius: 12px;">
                                                        ITEM #{{ $index + 1 }}
                                                    </span>
                                                    @if (isset($item['paymentType']))
                                                        <span
                                                            style="display: inline-block; font-size: 11px; font-weight: 600; padding: 4px 10px; border-radius: 12px;
                                                @if ($item['paymentType'] === 'Advance Payment') background-color: #fef3c7; color: #92400e;
                                                @else background-color: #dbeafe; color: #1e40af; @endif">
                                                            {{ $item['paymentType'] }}
                                                        </span>
                                                    @endif
                                                </div>

                                                <!-- Item Details -->
                                                <table role="presentation" cellspacing="0" cellpadding="0"
                                                    border="0" width="100%">
                                                    @if (isset($item['coa']))
                                                        <tr>
                                                            <td
                                                                style="padding: 6px 0; font-size: 13px; color: #6b7280; width: 120px; vertical-align: top;">
                                                                COA
                                                            </td>
                                                            <td
                                                                style="padding: 6px 0; font-size: 13px; color: #111827; font-weight: 500;">
                                                                {{ $item['coa'] }}
                                                            </td>
                                                        </tr>
                                                    @endif

                                                    @if (isset($item['description']))
                                                        <tr>
                                                            <td
                                                                style="padding: 6px 0; font-size: 13px; color: #6b7280; vertical-align: top;">
                                                                Deskripsi
                                                            </td>
                                                            <td
                                                                style="padding: 6px 0; font-size: 13px; color: #111827;">
                                                                {{ $item['description'] }}
                                                            </td>
                                                        </tr>
                                                    @endif

                                                    @if (isset($item['quantity']) && isset($item['unitQuantity']))
                                                        <tr>
                                                            <td
                                                                style="padding: 6px 0; font-size: 13px; color: #6b7280; vertical-align: top;">
                                                                Kuantitas
                                                            </td>
                                                            <td
                                                                style="padding: 6px 0; font-size: 13px; color: #111827;">
                                                                {{ $item['quantity'] }} {{ $item['unitQuantity'] }}
                                                            </td>
                                                        </tr>
                                                    @endif

                                                    @if (isset($item['amountPerItem']))
                                                        <tr>
                                                            <td
                                                                style="padding: 6px 0; font-size: 13px; color: #6b7280; vertical-align: top;">
                                                                Harga Satuan
                                                            </td>
                                                            <td
                                                                style="padding: 6px 0; font-size: 13px; color: #111827;">
                                                                {{ $item['amountPerItem'] }}
                                                            </td>
                                                        </tr>
                                                    @endif

                                                    @if (isset($item['subtotal']))
                                                        <tr>
                                                            <td
                                                                style="padding: 6px 0; font-size: 13px; color: #6b7280; vertical-align: top;">
                                                                Subtotal
                                                            </td>
                                                            <td
                                                                style="padding: 6px 0; font-size: 13px; color: #111827; font-weight: 600;">
                                                                {{ $item['subtotal'] }}
                                                            </td>
                                                        </tr>
                                                    @endif

                                                    @if (isset($item['taxMethod']) || isset($item['taxType']))
                                                        <tr>
                                                            <td
                                                                style="padding: 6px 0; font-size: 13px; color: #6b7280; vertical-align: top;">
                                                                Pajak
                                                            </td>
                                                            <td
                                                                style="padding: 6px 0; font-size: 13px; color: #111827;">
                                                                @if (isset($item['taxType']))
                                                                    {{ $item['taxType'] }}
                                                                @endif
                                                                @if (isset($item['taxMethod']))
                                                                    ({{ $item['taxMethod'] }})
                                                                @endif
                                                                @if (isset($item['taxAmount']))
                                                                    - {{ $item['taxAmount'] }}
                                                                @endif
                                                            </td>
                                                        </tr>
                                                    @endif

                                                    @if (isset($item['netAmount']))
                                                        <tr>
                                                            <td
                                                                style="padding: 6px 0; font-size: 13px; color: #6b7280; vertical-align: top;">
                                                                Nominal Bersih
                                                            </td>
                                                            <td
                                                                style="padding: 6px 0; font-size: 14px; color: #059669; font-weight: 700;">
                                                                {{ $item['netAmount'] }}
                                                            </td>
                                                        </tr>
                                                    @endif

                                                    @if (isset($item['advancePercentage']))
                                                        <tr>
                                                            <td
                                                                style="padding: 6px 0; font-size: 13px; color: #6b7280; vertical-align: top;">
                                                                Persentase Advance
                                                            </td>
                                                            <td
                                                                style="padding: 6px 0; font-size: 13px; color: #d97706; font-weight: 600;">
                                                                {{ $item['advancePercentage'] }}%
                                                            </td>
                                                        </tr>
                                                    @endif

                                                    @if (!empty($item['attachments']))
                                                        @foreach ($item['attachments'] as $attachment)
                                                            <tr>
                                                                <td colspan="2"
                                                                    style="padding: 6px 0; vertical-align: top;">
                                                                    <img style="width: 100%;height: auto;"
                                                                        src="{{ $message->embed($attachment->getPath()) }}">
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    @endif
                                                </table>
                                            </td>
                                        </tr>
                                    </table>
                                @endforeach
                            </td>
                        </tr>
                    @endif

                    <!-- Approval Information -->
                    @if (isset($approvalInfo))
                        <tr>
                            <td style="padding: 0 30px 20px 30px;">
                                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%"
                                    style="background-color: #eff6ff; border-radius: 8px; border: 1px solid #bfdbfe;">
                                    <tr>
                                        <td style="padding: 20px;">
                                            <h2
                                                style="margin: 0 0 15px 0; font-size: 16px; font-weight: 600; color: #1e40af;">
                                                {{ $approvalInfo['title'] ?? 'Informasi Approval' }}
                                            </h2>

                                            @if (isset($approvalInfo['msg']))
                                                <p
                                                    style="margin: 0 0 15px 0; font-size: 14px; line-height: 1.6; color: #1e40af;">
                                                    {{ $approvalInfo['msg'] }}
                                                </p>
                                            @endif

                                            @if (isset($approvalInfo['currentStep']))
                                                <p style="margin: 0; font-size: 13px; color: #3b82f6;">
                                                    <strong>Step saat ini:</strong> {{ $approvalInfo['currentStep'] }}
                                                </p>
                                            @endif

                                            @if (isset($approvalInfo['nextApprover']))
                                                <p style="margin: 8px 0 0 0; font-size: 13px; color: #3b82f6;">
                                                    <strong>Approver berikutnya:</strong>
                                                    {{ $approvalInfo['nextApprover'] }}
                                                </p>
                                            @endif

                                            <!-- Approval History -->
                                            @if (isset($approvalInfo['history']) && count($approvalInfo['history']) > 0)
                                                <div
                                                    style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #bfdbfe;">
                                                    <p
                                                        style="margin: 0 0 10px 0; font-size: 13px; font-weight: 600; color: #1e40af;">
                                                        Riwayat Approval:
                                                    </p>
                                                    @foreach ($approvalInfo['history'] as $history)
                                                        <div
                                                            style="margin-bottom: 8px; padding: 8px; background-color: #ffffff; border-radius: 4px;">
                                                            <p style="margin: 0; font-size: 12px; color: #374151;">
                                                                <strong>{{ $history['approver'] }}</strong>
                                                                <span
                                                                    style="
                                                        @if ($history['action'] === 'approved') color: #059669;
                                                        @elseif($history['action'] === 'rejected') color: #dc2626;
                                                        @else color: #d97706; @endif
                                                        font-weight: 600;">
                                                                    {{ $history['action'] }}
                                                                </span>
                                                            </p>
                                                            @if (isset($history['date']))
                                                                <p
                                                                    style="margin: 4px 0 0 0; font-size: 11px; color: #9ca3af;">
                                                                    {{ $history['date'] }}
                                                                </p>
                                                            @endif
                                                            @if (isset($history['notes']) && $history['notes'])
                                                                <p
                                                                    style="margin: 4px 0 0 0; font-size: 12px; color: #6b7280; font-style: italic;">
                                                                    "{{ $history['notes'] }}"
                                                                </p>
                                                            @endif
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    @endif

                    <!-- Action Buttons -->
                    @if (isset($actionButtons) && count($actionButtons) > 0)
                        <tr>
                            <td style="padding: 0 30px 30px 30px;">
                                <table role="presentation" cellspacing="0" cellpadding="0" border="0"
                                    width="100%">
                                    <tr>
                                        @foreach ($actionButtons as $button)
                                            <td style="padding: 0 5px; text-align: center;">
                                                <a href="{{ $button['url'] }}"
                                                    style="display: inline-block; padding: 14px 28px; background-color: {{ $button['color'] ?? '#6366f1' }}; color: #ffffff; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 14px; text-align: center;">
                                                    {{ $button['text'] }}
                                                </a>
                                            </td>
                                        @endforeach
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    @endif

                    <!-- Single Action Button (centered) -->
                    @if (isset($actionButton))
                        <tr>
                            <td style="padding: 0 30px 30px 30px; text-align: center;">
                                <a href="{{ $actionButton['url'] }}"
                                    style="display: inline-block; padding: 14px 32px; background-color: {{ $actionButton['color'] ?? '#6366f1' }}; color: #ffffff; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 15px;">
                                    {{ $actionButton['text'] }}
                                </a>
                            </td>
                        </tr>
                    @endif

                    <!-- Additional Notes -->
                    @if (isset($additionalNotes))
                        <tr>
                            <td style="padding: 0 30px 30px 30px;">
                                <table role="presentation" cellspacing="0" cellpadding="0" border="0"
                                    width="100%"
                                    style="background-color: #fef3c7; border-radius: 8px; border: 1px solid #fde68a;">
                                    <tr>
                                        <td style="padding: 15px;">
                                            <p style="margin: 0; font-size: 13px; line-height: 1.6; color: #92400e;">
                                                <strong>📌 Catatan:</strong><br>
                                                {{ $additionalNotes }}
                                            </p>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    @endif

                    <!-- Footer -->
                    <tr>
                        <td
                            style="padding: 30px; background-color: #f9fafb; border-radius: 0 0 8px 8px; border-top: 1px solid #e5e7eb;">
                            <p style="margin: 0 0 10px 0; font-size: 13px; line-height: 1.6; color: #6b7280;">
                                Email ini dikirim secara otomatis dari sistem Daily Payment Request. Mohon tidak
                                membalas email ini.
                            </p>
                            <p style="margin: 0; font-size: 12px; color: #9ca3af;">
                                &copy; {{ date('Y') }} {{ config('app.name', 'Your Company') }}. All rights
                                reserved.
                            </p>
                        </td>
                    </tr>

                </table>
                <!-- End Main Content -->

            </td>
        </tr>
    </table>
    <!-- End Email Container -->

</body>

</html>
