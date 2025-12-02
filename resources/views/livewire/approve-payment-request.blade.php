<div class="approval-container">
    @if ($actionCompleted)
        {{-- Success State --}}
        <div class="success-container">
            <div class="success-icon">
                @if ($actionResult === 'approved')
                    <svg class="icon-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                @else
                    <svg class="icon-danger" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                @endif
            </div>

            <h1 class="success-title">
                @if ($actionResult === 'approved')
                    Permintaan Telah Disetujui
                @else
                    Permintaan Telah Ditolak
                @endif
            </h1>

            <p class="success-message">
                @if ($actionResult === 'approved')
                    Terima kasih! Permintaan pembayaran telah berhasil disetujui. Pemohon dan approver berikutnya
                    (jika
                    ada) telah menerima notifikasi.
                @else
                    Permintaan pembayaran telah ditolak. Pemohon telah menerima notifikasi beserta alasan penolakan.
                @endif
            </p>

            <div class="request-summary-box">
                <div class="summary-item">
                    <span class="summary-label">Nomor Permintaan</span>
                    <span class="summary-value">{{ $request->request_number }}</span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">Total Nominal</span>
                    <span
                        class="summary-value-amount">{{ $this->formatCurrency($request->total_request_amount) }}</span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">Waktu Keputusan</span>
                    <span
                        class="summary-value">{{ $approvalHistory->approved_at->timezone('Asia/Jakarta')->format('d M Y, H:i') }}</span>
                </div>
            </div>

            @if ($notes)
                <div class="notes-box">
                    <strong>Catatan Anda:</strong>
                    <p>{{ $notes }}</p>
                </div>
            @endif
        </div>
    @else
        {{-- Approval Form --}}
        <div class="approval-header">
            <h1 class="page-title">Approval Permintaan Pembayaran</h1>
            <div class="status-badge status-{{ $this->getStatusColor() }}">
                {{ $this->getStatusText() }}
            </div>
        </div>

        @if ($errorMessage)
            <div class="alert alert-danger">
                <svg class="alert-icon" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                        d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                        clip-rule="evenodd"></path>
                </svg>
                {{ $errorMessage }}
            </div>
        @endif

        {{-- Request Details --}}
        <div class="details-card">
            <h2 class="card-title">Detail Permintaan</h2>

            <div class="detail-grid">
                <div class="detail-item">
                    <span class="detail-label">Nomor Permintaan</span>
                    <span class="detail-value">{{ $request->request_number }}</span>
                </div>

                <div class="detail-item">
                    <span class="detail-label">Pemohon</span>
                    <span class="detail-value">{{ $request->requester->user->name }}</span>
                </div>

                <div class="detail-item">
                    <span class="detail-label">Departemen</span>
                    <span class="detail-value">{{ $request->requester->jobTitle->department->name }}</span>
                </div>

                <div class="detail-item">
                    <span class="detail-label">Tanggal Pengajuan</span>
                    <span class="detail-value">{{ $request->created_at->format('d F Y') }}</span>
                </div>

                <div class="detail-item highlight">
                    <span class="detail-label">Total Nominal</span>
                    <span
                        class="detail-value-amount">{{ $this->formatCurrency($request->total_request_amount) }}</span>
                </div>

                <div class="detail-item">
                    <span class="detail-label">Jumlah Item</span>
                    <span class="detail-value">{{ $request->requestItems->count() }} item</span>
                </div>
            </div>
        </div>

        {{-- Request Items --}}
        <div class="details-card">
            <h2 class="card-title">Rincian Item</h2>

            @foreach ($request_items as $index => $item)
                <div class="item-card">
                    <div class="item-header">
                        {{-- <span class="item-number">Item #{{ $index + 1 }}</span> --}}
                        <span
                            class="payment-type-badge payment-type-{{ strtolower(str_replace(' ', '-', $item->payment_type->value)) }}">
                            {{ $item->payment_type->getLabel() }}
                        </span>
                    </div>

                    <div class="item-details">
                        <div class="item-row">
                            <span class="item-label">COA</span>
                            <span class="item-value">{{ $item->coa->name }}</span>
                        </div>

                        <div class="item-row">
                            <span class="item-label">Deskripsi</span>
                            <span class="item-value">{{ $item->description }}</span>
                        </div>

                        <div class="item-row">
                            <span class="item-label">Kuantitas</span>
                            <span
                                class="item-value">{{ $item->payment_type === \App\Enums\RequestPaymentType::Advance ? $item->quantity : $item->act_quantity }}
                                {{ $item->unit_quantity }}</span>
                        </div>

                        <div class="item-row">
                            <span class="item-label">Harga Satuan</span>
                            <span
                                class="item-value">{{ $this->formatCurrency($item->payment_type === \App\Enums\RequestPaymentType::Advance ? $item->amount_per_item : $item->act_amount_per_item) }}</span>
                        </div>

                        <div class="item-row">
                            <span class="item-label">Subtotal</span>
                            <span
                                class="item-value">{{ $this->formatCurrency($item->payment_type === \App\Enums\RequestPaymentType::Advance ? $item->total_amount : $item->total_act_amount) }}</span>
                        </div>

                        @if ($item->is_taxed && !is_null($item->tax_id) && !is_null($item->tax_method))
                            <div class="item-row">
                                <span class="item-label">Pajak</span>
                                <span class="item-value">{{ $item->tax->name }} (Ditanggung oleh:
                                    {{ \App\Enums\TaxMethod::from($item->tax_method->value)->getLabel() }}) -
                                    {{ $this->formatCurrency($item->tax_amount) }}</span>
                            </div>

                            <div class="item-row highlight">
                                <span class="item-label">Nominal Bersih</span>
                                <span class="item-value-amount">{{ $this->formatCurrency($item->net_amount) }}</span>
                            </div>
                        @endif

                        @if ($item->notes)
                            <div class="item-row">
                                <span class="item-label">Note Requester</span>
                                <span class="item-value"
                                    style="font-style: italic; color: gray;">{{ $item->notes }}</span>
                            </div>
                        @endif

                        @if (!empty($item->getMedia('request_item_attachments')) || !empty($item->getMedia('request_item_image')))
                            @foreach ($item->getMedia('request_item_attachments') ?? [] as $attachment)
                                <img style="max-width: 100%;max-height: 400px;width: auto;height: auto;object-fit: contain;"
                                    src="{{ $attachment->getTemporaryUrl(Carbon\Carbon::now()->addMinutes(5)) }}">
                            @endforeach
                            @foreach ($item->getMedia('request_item_image') ?? [] as $itemImage)
                                <img style="max-width: 100%;max-height: 400px;width: auto;height: auto;object-fit: contain;"
                                    src="{{ $itemImage->getTemporaryUrl(Carbon\Carbon::now()->addMinutes(5)) }}">
                            @endforeach
                        @endif

                    </div>
                </div>
            @endforeach
            <div class="w-full">
                {{ $request_items->links(data: ['scrollTo' => false]) }}
            </div>
        </div>

        {{-- Approval History --}}
        @if ($request->approvalHistories->where('action', '!=', 'pending')->count() > 0)
            <div class="details-card">
                <h2 class="card-title">Riwayat Approval</h2>

                @foreach ($request->approvalHistories->sortBy('sequence') as $history)
                    @if ($history->action->value !== 'pending')
                        <div class="history-item history-{{ $history->action->value }}">
                            <div class="history-header">
                                <div>
                                    <strong>{{ $history->approver->user->name }}</strong>
                                    <span class="history-role">({{ $history->approver->jobTitle->title }})</span>
                                </div>
                                <span class="history-badge history-badge-{{ $history->action->value }}">
                                    {{ ucfirst($history->action->getLabel()) }}
                                </span>
                            </div>
                            @if ($history->approved_at)
                                <div class="history-date">{{ $history->approved_at->format('d M Y, H:i') }}</div>
                            @endif
                            @if ($history->notes)
                                <div class="history-notes">{{ $history->notes }}</div>
                            @endif
                        </div>
                    @endif
                @endforeach
            </div>
        @endif

        {{-- Your Turn Info --}}
        <div class="your-turn-box">
            <div class="your-turn-icon">
                <svg fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                        d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                        clip-rule="evenodd"></path>
                </svg>
            </div>
            <div>
                <strong>Giliran Anda untuk Approve</strong>
                <p>Sebagai <strong>{{ $approvalHistory->approver->jobTitle->title }}</strong>, Anda diminta untuk
                    meninjau dan memberikan keputusan pada permintaan ini.</p>
            </div>
        </div>

        {{-- Notes Textarea --}}
        <div class="form-group">
            <label for="notes" class="form-label">
                Catatan <span class="text-muted">(Opsional untuk Approval, Wajib untuk Reject)</span>
            </label>
            <textarea wire:model="notes" id="notes" rows="4" class="form-textarea"
                placeholder="Tambahkan catatan Anda di sini..."></textarea>
            @error('notes')
                <span class="error-message">{{ $message }}</span>
            @enderror
            <div class="form-hint">
                @if ($notes)
                    {{ strlen($notes) }}/500 karakter
                @else
                    Maksimal 500 karakter
                @endif
            </div>
        </div>

        {{-- Action Buttons --}}
        <div class="action-buttons">
            <button wire:click="confirmApprove" type="button" class="btn btn-success">
                <svg class="btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7">
                    </path>
                </svg>
                Setujui Permintaan
            </button>

            <button wire:click="confirmReject" type="button" class="btn btn-danger">
                <svg class="btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                    </path>
                </svg>
                Tolak Permintaan
            </button>
        </div>

        {{-- Approve Confirmation Modal --}}
        @if ($showApproveConfirm)
            <div class="modal-overlay" wire:click="cancelConfirm">
                <div class="modal-content" wire:click.stop>
                    <div class="modal-header">
                        <h3 class="modal-title">Konfirmasi Approval</h3>
                    </div>
                    <div class="modal-body">
                        <p>Apakah Anda yakin ingin <strong>menyetujui</strong> permintaan pembayaran ini?</p>
                        <div class="modal-info">
                            <div>Nomor: <strong>{{ $request->request_number }}</strong></div>
                            <div>Nominal:
                                <strong>{{ $this->formatCurrency($request->total_request_amount) }}</strong>
                            </div>
                        </div>
                        <p class="modal-warning">Keputusan ini tidak dapat dibatalkan.</p>
                    </div>
                    <div class="modal-footer">
                        <button wire:click="cancelConfirm" type="button" class="btn btn-secondary">
                            Batal
                        </button>
                        <button wire:click="approve" type="button" class="btn btn-success">
                            Ya, Setujui
                        </button>
                    </div>
                </div>
            </div>
        @endif

        {{-- Reject Confirmation Modal --}}
        @if ($showRejectConfirm)
            <div class="modal-overlay" wire:click="cancelConfirm">
                <div class="modal-content" wire:click.stop>
                    <div class="modal-header">
                        <h3 class="modal-title">Konfirmasi Penolakan</h3>
                    </div>
                    <div class="modal-body">
                        <p>Apakah Anda yakin ingin <strong>menolak</strong> permintaan pembayaran ini?</p>
                        <div class="modal-info">
                            <div>Nomor: <strong>{{ $request->request_number }}</strong></div>
                            <div>Nominal:
                                <strong>{{ $this->formatCurrency($request->total_request_amount) }}</strong>
                            </div>
                        </div>
                        @if ($notes)
                            <div class="modal-notes">
                                <strong>Alasan penolakan:</strong>
                                <p>{{ $notes }}</p>
                            </div>
                        @endif
                        <p class="modal-warning">Keputusan ini tidak dapat dibatalkan dan pemohon akan menerima
                            notifikasi penolakan.</p>
                    </div>
                    <div class="modal-footer">
                        <button wire:click="cancelConfirm" type="button" class="btn btn-secondary">
                            Batal
                        </button>
                        <button wire:click="reject" type="button" class="btn btn-danger">
                            Ya, Tolak
                        </button>
                    </div>
                </div>
            </div>
        @endif
    @endif
</div>
