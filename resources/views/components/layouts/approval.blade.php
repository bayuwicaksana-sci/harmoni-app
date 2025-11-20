<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Approval Permintaan Pembayaran</title>

    <style>
        /* * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        } */

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 15px;
            line-height: 1.6;
            color: #1f2937;
        }

        .approval-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }

        /* Header */
        .approval-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 25px 20px;
            color: white;
            text-align: center;
        }

        .page-title {
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-warning {
            background: #fef3c7;
            color: #92400e;
        }

        .status-success {
            background: #d1fae5;
            color: #065f46;
        }

        .status-danger {
            background: #fee2e2;
            color: #991b1b;
        }

        /* Alerts */
        .alert {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 15px 20px;
            margin: 20px;
            border-radius: 8px;
            font-size: 14px;
        }

        .alert-danger {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #dc2626;
        }

        .alert-icon {
            width: 24px;
            height: 24px;
            flex-shrink: 0;
        }

        /* Cards */
        .details-card {
            padding: 20px;
            border-bottom: 1px solid #e5e7eb;
        }

        .card-title {
            font-size: 18px;
            font-weight: 600;
            color: #111827;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Detail Grid */
        .detail-grid {
            display: grid;
            gap: 12px;
        }

        .detail-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px;
            background: #f9fafb;
            border-radius: 6px;
            gap: 10px;
        }

        .detail-item.highlight {
            background: #eff6ff;
            border: 2px solid #3b82f6;
        }

        .detail-label {
            font-size: 13px;
            color: #6b7280;
            font-weight: 500;
        }

        .detail-value {
            font-size: 14px;
            color: #111827;
            font-weight: 600;
            text-align: right;
        }

        .detail-value-amount {
            font-size: 18px;
            color: #059669;
            font-weight: 700;
            text-align: right;
        }

        /* Item Cards */
        .item-card {
            background: #f9fafb;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 12px;
            border: 1px solid #e5e7eb;
        }

        .item-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }

        .item-number {
            font-size: 12px;
            font-weight: 700;
            color: white;
            background: #6366f1;
            padding: 4px 12px;
            border-radius: 12px;
        }

        .payment-type-badge {
            font-size: 11px;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 12px;
            text-transform: uppercase;
        }

        .payment-type-reimbursement {
            background: #dbeafe;
            color: #1e40af;
        }

        .payment-type-advance-payment {
            background: #fef3c7;
            color: #92400e;
        }

        .item-details {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .item-row {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            font-size: 13px;
        }

        .item-row.highlight {
            padding-top: 8px;
            border-top: 2px solid #e5e7eb;
            margin-top: 4px;
        }

        .item-label {
            color: #6b7280;
            font-weight: 500;
        }

        .item-value {
            color: #111827;
            font-weight: 600;
            text-align: right;
        }

        .item-value-amount {
            color: #059669;
            font-weight: 700;
            font-size: 14px;
        }

        .item-value.advance {
            color: #d97706;
            font-weight: 700;
        }

        /* History */
        .history-item {
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 8px;
            border-left: 4px solid #e5e7eb;
        }

        .history-approved {
            background: #f0fdf4;
            border-left-color: #059669;
        }

        .history-rejected {
            background: #fef2f2;
            border-left-color: #dc2626;
        }

        .history-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 4px;
        }

        .history-role {
            font-size: 12px;
            color: #6b7280;
        }

        .history-badge {
            font-size: 11px;
            font-weight: 600;
            padding: 3px 10px;
            border-radius: 10px;
            text-transform: uppercase;
        }

        .history-badge-approved {
            background: #d1fae5;
            color: #065f46;
        }

        .history-badge-rejected {
            background: #fee2e2;
            color: #991b1b;
        }

        .history-date {
            font-size: 12px;
            color: #9ca3af;
            margin-bottom: 4px;
        }

        .history-notes {
            font-size: 13px;
            color: #4b5563;
            font-style: italic;
            margin-top: 6px;
        }

        /* Your Turn Box */
        .your-turn-box {
            display: flex;
            gap: 15px;
            padding: 20px;
            margin: 20px;
            background: #eff6ff;
            border-radius: 8px;
            border: 2px solid #3b82f6;
        }

        .your-turn-icon {
            flex-shrink: 0;
        }

        .your-turn-icon svg {
            width: 32px;
            height: 32px;
            color: #3b82f6;
        }

        .your-turn-box p {
            margin-top: 5px;
            font-size: 14px;
            color: #1e40af;
        }

        /* Form */
        .form-group {
            padding: 20px;
        }

        .form-label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 8px;
        }

        .text-muted {
            color: #9ca3af;
            font-weight: 400;
            font-size: 13px;
        }

        .form-textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #d1d5db;
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
            resize: vertical;
            transition: border-color 0.2s;
        }

        .form-textarea:focus {
            outline: none;
            border-color: #3b82f6;
        }

        .form-hint {
            font-size: 12px;
            color: #9ca3af;
            margin-top: 5px;
        }

        .error-message {
            display: block;
            color: #dc2626;
            font-size: 12px;
            margin-top: 5px;
        }

        /* Action Buttons */
        .action-buttons {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            padding: 20px;
            background: #f9fafb;
        }

        .btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 14px 20px;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
        }

        .btn-icon {
            width: 20px;
            height: 20px;
        }

        .btn-success {
            background: #059669;
            color: white;
        }

        .btn-success:hover {
            background: #047857;
        }

        .btn-danger {
            background: #dc2626;
            color: white;
        }

        .btn-danger:hover {
            background: #b91c1c;
        }

        .btn-secondary {
            background: #6b7280;
            color: white;
        }

        .btn-secondary:hover {
            background: #4b5563;
        }

        /* Modal */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            z-index: 1000;
        }

        .modal-content {
            background: white;
            border-radius: 12px;
            max-width: 500px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            padding: 20px;
            border-bottom: 1px solid #e5e7eb;
        }

        .modal-title {
            font-size: 18px;
            font-weight: 600;
            color: #111827;
        }

        .modal-body {
            padding: 20px;
        }

        .modal-info {
            background: #f9fafb;
            padding: 12px;
            border-radius: 6px;
            margin: 15px 0;
            font-size: 14px;
        }

        .modal-info div {
            margin-bottom: 5px;
        }

        .modal-notes {
            background: #fef3c7;
            padding: 12px;
            border-radius: 6px;
            margin: 15px 0;
            font-size: 13px;
        }

        .modal-warning {
            color: #dc2626;
            font-size: 13px;
            margin-top: 15px;
        }

        .modal-footer {
            padding: 20px;
            border-top: 1px solid #e5e7eb;
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }

        /* Success State */
        .success-container {
            padding: 40px 20px;
            text-align: center;
        }

        .success-icon {
            margin-bottom: 20px;
        }

        .icon-success {
            width: 80px;
            height: 80px;
            color: #059669;
            margin: 0 auto;
        }

        .icon-danger {
            width: 80px;
            height: 80px;
            color: #dc2626;
            margin: 0 auto;
        }

        .success-title {
            font-size: 24px;
            font-weight: 700;
            color: #111827;
            margin-bottom: 10px;
        }

        .success-message {
            font-size: 15px;
            color: #6b7280;
            line-height: 1.6;
            margin-bottom: 30px;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        .request-summary-box {
            background: #f9fafb;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e5e7eb;
        }

        .summary-item:last-child {
            border-bottom: none;
        }

        .summary-label {
            font-size: 14px;
            color: #6b7280;
        }

        .summary-value {
            font-size: 14px;
            font-weight: 600;
            color: #111827;
        }

        .summary-value-amount {
            font-size: 18px;
            font-weight: 700;
            color: #059669;
        }

        .notes-box {
            background: #eff6ff;
            border: 2px solid #3b82f6;
            border-radius: 8px;
            padding: 15px;
            margin-top: 20px;
            text-align: left;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
        }

        .notes-box p {
            margin-top: 8px;
            color: #1e40af;
            font-size: 14px;
        }

        /* Mobile Responsive */
        @media (max-width: 640px) {
            body {
                padding: 10px;
            }

            .page-title {
                font-size: 18px;
            }

            .action-buttons {
                grid-template-columns: 1fr;
            }

            .detail-item {
                flex-direction: column;
                align-items: flex-start;
            }

            .detail-value,
            .detail-value-amount {
                text-align: left;
            }

            .item-row {
                flex-direction: column;
                gap: 4px;
            }

            .item-value,
            .item-value-amount {
                text-align: left;
            }

            .modal-footer {
                flex-direction: column-reverse;
            }

            .modal-footer .btn {
                width: 100%;
            }
        }
    </style>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>

<body>
    {{ $slot }}

    @livewireScripts
</body>

</html>
