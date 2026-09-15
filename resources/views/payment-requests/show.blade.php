@extends('layouts.admin')

@section('title')
{{ $paymentRequest->displayReference() }} | Retirement Portal
@endsection

@section('bodyClass', 'light-header-page form-page')

@push('styles')
<link href="{{ asset('assets/css/table.css') }}" rel="stylesheet">
@endpush

@section('content')

    @php
        $status = $paymentRequest->status;
        $type = $paymentRequest->payment_type;
        $user = auth()->user();
    @endphp

        <div class="page-title web-hero web-panel-hero page-header--admin">
            <div class="container position-relative" data-aos="fade-up">

                <div class="web-hero-badge">
                    <i class="bi {{ $type->icon() }}"></i>
                    {{ $type->shortLabel() }}
                </div>

                <h1>{{ $paymentRequest->displayReference() }}</h1>

                <p>
                    <span class="status-badge {{ $status->badgeClass() }}">
                        <i class="bi {{ $status->icon() }}"></i>
                        {{ $status->label() }}
                    </span>
                </p>

                <div class="form-feedback mb-4">
                    @include('components.form-alerts', ['flush' => true])
                </div>

                <div class="breadcrumbs">
                    <ol>
                        <li><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li><a href="{{ route('payment-requests.index') }}">Payment Requests</a></li>
                        <li>{{ $paymentRequest->displayReference() }}</li>
                    </ol>
                </div>

            </div>
        </div>

        <section class="form-shell section">
            <div class="container" data-aos="fade-up" data-aos-delay="100">

                <div class="form-section-title-row">
                    <div class="section-title form-section-title mb-0">
                        <span class="portal-section-eyebrow">Request Details</span>
                        <h2>{{ $paymentRequest->displayReference() }}</h2>
                        <p>{{ $type->shortLabel() }} &middot; {{ $paymentRequest->formattedAmount() }}</p>
                    </div>

                    <a href="{{ route('payment-requests.index') }}" class="form-btn form-btn-light form-back-btn">
                        <i class="bi bi-arrow-left"></i>
                        Go Back
                    </a>
                </div>

                {{-- =========================================================
                    ACTION BAR - every button is policy-gated, so the visible
                    set already matches what the backend would actually allow.
                ========================================================== --}}
                <div class="form-card portal-form-card mb-4" data-aos="fade-up" data-aos-delay="120">
                    <div class="form-body">
                        <div class="form-actions portal-form-actions flex-wrap">

                            @can('update', $paymentRequest)
                                <a href="{{ route('payment-requests.edit', $paymentRequest) }}" class="form-btn form-btn-light">
                                    <i class="bi bi-pencil-square"></i>
                                    Edit
                                </a>
                            @endcan

                            @can('submit', $paymentRequest)
                                <form action="{{ route('payment-requests.submit', $paymentRequest) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="form-btn form-btn-primary">
                                        <span class="form-btn-spinner"></span>
                                        <i class="bi bi-send-check-fill"></i>
                                        {{ $status === \App\Enums\PaymentRequestStatus::Returned ? 'Resubmit' : 'Submit' }}
                                    </button>
                                </form>
                            @endcan

                            @can('assign', $paymentRequest)
                                <button type="button" class="form-btn form-btn-light" data-bs-toggle="modal" data-bs-target="#assignModal">
                                    <i class="bi bi-person-arms-up"></i>
                                    Forward / Assign
                                </button>
                            @endcan

                            @can('review', $paymentRequest)
                                @if (in_array($status, [\App\Enums\PaymentRequestStatus::Assigned], true))
                                    <form action="{{ route('payment-requests.review', $paymentRequest) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="form-btn form-btn-light">
                                            <span class="form-btn-spinner"></span>
                                            <i class="bi bi-search"></i>
                                            Mark Under Review
                                        </button>
                                    </form>
                                @endif
                            @endcan

                            @can('returnForCorrection', $paymentRequest)
                                <button type="button" class="form-btn form-btn-light" data-bs-toggle="modal" data-bs-target="#returnModal">
                                    <i class="bi bi-reply-fill"></i>
                                    Return for Correction
                                </button>
                            @endcan

                            @can('approve', $paymentRequest)
                                <button type="button" class="form-btn form-btn-primary" data-bs-toggle="modal" data-bs-target="#approveModal">
                                    <i class="bi bi-check-circle-fill"></i>
                                    Approve
                                </button>
                            @endcan

                            @can('reject', $paymentRequest)
                                <button type="button" class="form-btn form-btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal">
                                    <i class="bi bi-x-circle-fill"></i>
                                    Reject
                                </button>
                            @endcan

                            @can('markReadyForPayment', $paymentRequest)
                                @if ($status === \App\Enums\PaymentRequestStatus::Approved)
                                    <form action="{{ route('payment-requests.ready-for-payment', $paymentRequest) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="form-btn form-btn-primary">
                                            <span class="form-btn-spinner"></span>
                                            <i class="bi bi-hourglass-split"></i>
                                            Mark Ready for Payment
                                        </button>
                                    </form>
                                @endif
                            @endcan

                            @can('markPaid', $paymentRequest)
                                @if ($status === \App\Enums\PaymentRequestStatus::ReadyForPayment)
                                    <button type="button" class="form-btn form-btn-primary" data-bs-toggle="modal" data-bs-target="#markPaidModal">
                                        <i class="bi bi-cash-stack"></i>
                                        Record Payment
                                    </button>
                                @endif
                            @endcan

                            @can('print', $paymentRequest)
                                <a href="{{ route('payment-requests.print', $paymentRequest) }}" target="_blank" class="form-btn form-btn-light">
                                    <i class="bi bi-printer-fill"></i>
                                    Print Voucher
                                </a>
                            @endcan

                            @can('cancel', $paymentRequest)
                                <button type="button" class="form-btn form-btn-danger" data-bs-toggle="modal" data-bs-target="#cancelModal">
                                    <i class="bi bi-slash-circle-fill"></i>
                                    Cancel
                                </button>
                            @endcan

                        </div>
                    </div>
                </div>

                <div class="form-layout">

                    {{-- =====================================================
                        SIDEBAR - who/when at a glance
                    ====================================================== --}}
                    <aside class="portal-profile-aside" data-aos="fade-up" data-aos-delay="140">

                        <div class="form-guide-card portal-profile-card">
                            <div class="form-guide-badge">
                                <i class="bi bi-people-fill"></i>
                                People
                            </div>

                            <div class="form-guide-mini-card">
                                <div class="form-guide-mini-icon"><i class="bi bi-person-fill"></i></div>
                                <div>
                                    <h4>Requested By</h4>
                                    <p>{{ $paymentRequest->creator->name ?? '—' }}</p>
                                </div>
                            </div>

                            <div class="form-guide-mini-card">
                                <div class="form-guide-mini-icon"><i class="bi bi-person-arms-up"></i></div>
                                <div>
                                    <h4>Currently Assigned To</h4>
                                    <p>{{ $paymentRequest->currentAssignee->name ?? 'Unassigned' }}</p>
                                </div>
                            </div>

                            @if ($paymentRequest->approver)
                                <div class="form-guide-mini-card">
                                    <div class="form-guide-mini-icon"><i class="bi bi-check-circle-fill"></i></div>
                                    <div>
                                        <h4>Approved By</h4>
                                        <p>{{ $paymentRequest->approver->name }} &middot; {{ $paymentRequest->approved_at?->format('d M Y') }}</p>
                                    </div>
                                </div>
                            @endif

                            @if ($paymentRequest->processor)
                                <div class="form-guide-mini-card">
                                    <div class="form-guide-mini-icon"><i class="bi bi-cash-stack"></i></div>
                                    <div>
                                        <h4>Processed By</h4>
                                        <p>{{ $paymentRequest->processor->name }} &middot; {{ $paymentRequest->paid_at?->format('d M Y') }}</p>
                                    </div>
                                </div>
                            @endif

                            @if ($paymentRequest->rejecter)
                                <div class="form-guide-mini-card">
                                    <div class="form-guide-mini-icon"><i class="bi bi-x-circle-fill"></i></div>
                                    <div>
                                        <h4>Rejected By</h4>
                                        <p>{{ $paymentRequest->rejecter->name }} &middot; {{ $paymentRequest->rejected_at?->format('d M Y') }}</p>
                                    </div>
                                </div>
                            @endif

                            <div class="form-guide-mini-card">
                                <div class="form-guide-mini-icon"><i class="bi bi-calendar3"></i></div>
                                <div>
                                    <h4>Created</h4>
                                    <p>{{ $paymentRequest->created_at->format('d M Y, g:ia') }}</p>
                                </div>
                            </div>

                            @if ($paymentRequest->submitted_at)
                                <div class="form-guide-mini-card">
                                    <div class="form-guide-mini-icon"><i class="bi bi-send-check-fill"></i></div>
                                    <div>
                                        <h4>Submitted</h4>
                                        <p>{{ $paymentRequest->submitted_at->format('d M Y, g:ia') }}</p>
                                    </div>
                                </div>
                            @endif
                        </div>

                    </aside>

                    {{-- =====================================================
                        MAIN DETAIL PANEL
                    ====================================================== --}}
                    <div class="form-card portal-form-card" data-aos="fade-up" data-aos-delay="180">

                        <div class="form-card-header">
                            <div class="form-card-icon">
                                <i class="bi {{ $type->icon() }}"></i>
                            </div>

                            <div class="form-card-heading">
                                <div class="form-card-badge">
                                    <i class="bi bi-eye-fill"></i>
                                    Details
                                </div>

                                <h3>{{ $type->shortLabel() }}</h3>
                                <p>{{ $paymentRequest->description }}</p>
                            </div>
                        </div>

                        <div class="form-body">

                            @if ($status === \App\Enums\PaymentRequestStatus::Returned && $paymentRequest->returned_reason)
                                <div class="form-alert form-alert-warning">
                                    <span class="form-alert-icon" aria-hidden="true"><i class="bi bi-reply-fill"></i></span>
                                    <div class="form-alert-content">
                                        <strong class="form-alert-title">Returned for correction</strong>
                                        <p class="form-alert-message">{{ $paymentRequest->returned_reason }}</p>
                                    </div>
                                </div>
                            @endif

                            @if ($status === \App\Enums\PaymentRequestStatus::Rejected && $paymentRequest->rejection_reason)
                                <div class="form-alert form-alert-danger">
                                    <span class="form-alert-icon" aria-hidden="true"><i class="bi bi-x-circle-fill"></i></span>
                                    <div class="form-alert-content">
                                        <strong class="form-alert-title">Rejected</strong>
                                        <p class="form-alert-message">{{ $paymentRequest->rejection_reason }}</p>
                                    </div>
                                </div>
                            @endif

                            <div class="form-section-card">
                                <div class="form-section-header">
                                    <h4>Payment Details</h4>
                                    <p>Core voucher information.</p>
                                </div>

                                <div class="detail-view-grid">

                                    @if ($type->requiresConsultancyDetails() && $paymentRequest->consultancyDetails)
                                        <div class="detail-view-item">
                                            <span><i class="bi bi-person-fill"></i> Consultant's Name</span>
                                            <strong>{{ $paymentRequest->consultancyDetails->consultant_name }}</strong>
                                        </div>

                                        <div class="detail-view-item">
                                            <span><i class="bi bi-upc-scan"></i> TIN Number</span>
                                            <strong>{{ $paymentRequest->consultancyDetails->tin_number }}</strong>
                                        </div>

                                        <div class="detail-view-item">
                                            <span><i class="bi bi-building"></i> Client's Name</span>
                                            <strong>{{ $paymentRequest->consultancyDetails->client_name }}</strong>
                                        </div>
                                    @else
                                        <div class="detail-view-item">
                                            <span><i class="bi bi-person-fill"></i> Payee's Name</span>
                                            <strong>{{ $paymentRequest->payee_name }}</strong>
                                        </div>
                                    @endif

                                    <div class="detail-view-item">
                                        <span><i class="bi bi-calendar-event-fill"></i> Date</span>
                                        <strong>{{ $paymentRequest->payment_date->format('d M Y') }}</strong>
                                    </div>

                                    <div class="detail-view-item">
                                        <span><i class="bi bi-hash"></i> Reference Number</span>
                                        <strong>{{ $paymentRequest->displayReference() }}</strong>
                                    </div>

                                    <div class="detail-view-item detail-view-item--full">
                                        <span><i class="bi bi-card-text"></i> Payment Description</span>
                                        <strong>{{ $paymentRequest->description }}</strong>
                                    </div>

                                </div>
                            </div>

                            @if ($type->requiresConsultancyDetails() && $paymentRequest->consultancyDetails)
                                <div class="form-section-card">
                                    <div class="form-section-header">
                                        <h4>Fee &amp; Withholding Tax</h4>
                                    </div>

                                    <div class="detail-view-grid">
                                        <div class="detail-view-item">
                                            <span><i class="bi bi-cash-stack"></i> Total Consultancy Fee</span>
                                            <strong>{{ $paymentRequest->currency }} {{ number_format((float) $paymentRequest->consultancyDetails->consultancy_fee, 2) }}</strong>
                                        </div>

                                        <div class="detail-view-item">
                                            <span><i class="bi bi-percent"></i> Withholding Tax %</span>
                                            <strong>{{ number_format((float) $paymentRequest->consultancyDetails->withholding_tax_percentage, 2) }}%</strong>
                                        </div>

                                        <div class="detail-view-item">
                                            <span><i class="bi bi-dash-circle"></i> Withholding Tax Amount</span>
                                            <strong>{{ $paymentRequest->currency }} {{ number_format((float) $paymentRequest->consultancyDetails->withholding_tax_amount, 2) }}</strong>
                                        </div>

                                        <div class="detail-view-item">
                                            <span><i class="bi bi-cash-coin"></i> Amount Due to Consultant</span>
                                            <strong>{{ $paymentRequest->currency }} {{ number_format((float) $paymentRequest->consultancyDetails->amount_due, 2) }}</strong>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div class="form-section-card">
                                    <div class="form-section-header">
                                        <h4>Amount</h4>
                                    </div>

                                    <div class="detail-view-grid">
                                        <div class="detail-view-item">
                                            <span><i class="bi bi-cash-stack"></i> Amount</span>
                                            <strong>{{ $paymentRequest->formattedAmount() }}</strong>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <div class="form-section-card">
                                <div class="form-section-header">
                                    <h4>Amount in Words</h4>
                                </div>

                                <div class="detail-view-grid">
                                    <div class="detail-view-item detail-view-item--full">
                                        <span><i class="bi bi-fonts"></i> Written Amount</span>
                                        <strong>{{ $paymentRequest->amount_in_words ?: '—' }}</strong>
                                    </div>
                                </div>
                            </div>

                            @if ($paymentRequest->cheque_number || $paymentRequest->transaction_reference || $paymentRequest->cashier_name)
                                <div class="form-section-card">
                                    <div class="form-section-header">
                                        <h4>Payment Method</h4>
                                    </div>

                                    <div class="detail-view-grid">
                                        @if ($paymentRequest->cheque_number)
                                            <div class="detail-view-item">
                                                <span><i class="bi bi-vector-pen"></i> Cheque Number</span>
                                                <strong>{{ $paymentRequest->cheque_number }}</strong>
                                            </div>
                                        @endif

                                        @if ($paymentRequest->transaction_reference)
                                            <div class="detail-view-item">
                                                <span><i class="bi bi-bank2"></i> Transaction Reference</span>
                                                <strong>{{ $paymentRequest->transaction_reference }}</strong>
                                            </div>
                                        @endif

                                        @if ($paymentRequest->cashier_name)
                                            <div class="detail-view-item">
                                                <span><i class="bi bi-person-badge-fill"></i> Cashier</span>
                                                <strong>{{ $paymentRequest->cashier_name }}</strong>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endif

                            {{-- =========================================
                                DOCUMENTS
                            ========================================== --}}
                            <div class="form-section-card" id="documents">
                                <div class="form-section-header">
                                    <h4>Supporting Documents</h4>
                                    <p>{{ $paymentRequest->documents->count() }} file(s) attached.</p>
                                </div>

                                @forelse ($paymentRequest->documents as $document)
                                    <div class="detail-view-item detail-view-item--full mb-2">
                                        <span><i class="bi bi-paperclip"></i> {{ $document->typeLabel() }}</span>
                                        <strong class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                                            <span>
                                                {{ $document->original_filename }}
                                                <small class="text-muted">({{ $document->humanSize() }}, by {{ $document->uploader->name ?? '—' }})</small>
                                            </span>
                                            <a href="{{ route('payment-requests.documents.download', [$paymentRequest, $document]) }}" class="form-btn form-btn-light">
                                                <i class="bi bi-download"></i>
                                                Download
                                            </a>
                                        </strong>
                                    </div>
                                @empty
                                    <p class="text-muted mb-0">No documents attached yet.</p>
                                @endforelse

                                @can('uploadDocument', $paymentRequest)
                                    <form action="{{ route('payment-requests.documents.store', $paymentRequest) }}" method="POST" enctype="multipart/form-data" class="form-body mt-3">
                                        @csrf
                                        <div class="form-grid">
                                            <div class="form-field">
                                                <label for="document_type" class="form-label">Document Type <span>*</span></label>
                                                <div class="form-input-wrap">
                                                    <i class="bi bi-tag-fill"></i>
                                                    <select id="document_type" name="document_type" class="form-control-custom" required>
                                                        @foreach (config('payment_requests.documents.types') as $value => $label)
                                                            <option value="{{ $value }}">{{ $label }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="form-field">
                                                <label for="file" class="form-label">File <span>*</span></label>
                                                <div class="form-input-wrap">
                                                    <i class="bi bi-file-earmark-arrow-up-fill"></i>
                                                    <input type="file" id="file" name="file" class="form-control-custom" required>
                                                </div>
                                                <span class="form-input-help">PDF or image, up to 10 MB.</span>
                                            </div>
                                        </div>

                                        <div class="form-actions portal-form-actions">
                                            <button type="submit" class="form-btn form-btn-light">
                                                <span class="form-btn-spinner"></span>
                                                <i class="bi bi-upload"></i>
                                                Upload Document
                                            </button>
                                        </div>
                                    </form>
                                @endcan
                            </div>

                            {{-- =========================================
                                COMMENTS
                            ========================================== --}}
                            <div class="form-section-card" id="comments">
                                <div class="form-section-header">
                                    <h4>Internal Comments</h4>
                                    <p>Discussion between staff - kept separate from the workflow history below.</p>
                                </div>

                                <div class="portal-thread-replies mb-3">
                                    @forelse ($paymentRequest->comments as $comment)
                                        <div class="portal-thread-message is-attendee">
                                            <div class="portal-thread-avatar">
                                                <i class="bi bi-person-fill"></i>
                                            </div>
                                            <div class="portal-thread-content">
                                                <div class="portal-thread-meta">
                                                    <strong>{{ $comment->user->name ?? 'Unknown' }}</strong>
                                                    <time>{{ $comment->created_at->format('d M, g:ia') }}</time>
                                                </div>
                                                <p>{{ $comment->body }}</p>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="portal-thread-empty">
                                            <i class="bi bi-chat-left-dots"></i>
                                            <strong>No comments yet</strong>
                                            <span>Start the discussion below.</span>
                                        </div>
                                    @endforelse
                                </div>

                                @can('comment', $paymentRequest)
                                    <form method="POST" action="{{ route('payment-requests.comments.store', $paymentRequest) }}" class="portal-thread-composer">
                                        @csrf
                                        <textarea name="body" required minlength="1" maxlength="4000" placeholder="Add a comment as {{ $user->name }}..."></textarea>
                                        <button type="submit" class="portal-thread-composer-send">
                                            <i class="bi bi-send-fill"></i> Comment
                                        </button>
                                    </form>
                                @endcan
                            </div>

                            {{-- =========================================
                                HISTORY / AUDIT TRAIL
                            ========================================== --}}
                            <div class="form-section-card">
                                <div class="form-section-header">
                                    <h4>History &amp; Audit Trail</h4>
                                    <p>A complete, immutable record of every action taken on this request.</p>
                                </div>

                                @include('payment-requests._timeline', ['history' => $paymentRequest->history, 'assignments' => $paymentRequest->assignments])
                            </div>

                        </div>

                    </div>

                </div>

            </div>
        </section>

        {{-- =============================================================
            MODALS - each posts to its own workflow route; opened via
            data-bs-toggle="modal" on the action bar buttons above.
        ============================================================== --}}

        @can('assign', $paymentRequest)
            <div class="modal fade" id="assignModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <form method="POST" action="{{ route('payment-requests.assign', $paymentRequest) }}" class="form-body">
                            @csrf
                            <div class="form-card-header">
                                <div class="form-card-icon"><i class="bi bi-person-arms-up"></i></div>
                                <div class="form-card-heading">
                                    <h3>Forward / Assign Request</h3>
                                    <p>Choose who should act on this request next.</p>
                                </div>
                            </div>

                            <div class="form-section-card">
                                <div class="form-grid">
                                    <div class="form-field form-grid-full">
                                        <label for="assigned_to" class="form-label">Assign To <span>*</span></label>
                                        <div class="form-input-wrap">
                                            <i class="bi bi-person-fill"></i>
                                            <select id="assigned_to" name="assigned_to" class="form-control-custom" required>
                                                <option value="">Select a staff member...</option>
                                                @foreach ($assignableUsers as $assignable)
                                                    <option value="{{ $assignable->id }}">{{ $assignable->name }} ({{ ucfirst($assignable->role) }})</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div class="form-field form-grid-full">
                                        <label for="assign_comment" class="form-label">Comment</label>
                                        <div class="form-textarea-wrap">
                                            <textarea id="assign_comment" name="comment" rows="2" class="form-control-custom" maxlength="2000"></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-actions portal-form-actions">
                                <button type="button" class="form-btn form-btn-light" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="form-btn form-btn-primary">
                                    <span class="form-btn-spinner"></span>
                                    <i class="bi bi-send-check-fill"></i>
                                    Assign
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endcan

        @can('returnForCorrection', $paymentRequest)
            <div class="modal fade" id="returnModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <form method="POST" action="{{ route('payment-requests.return', $paymentRequest) }}" class="form-body">
                            @csrf
                            <div class="form-card-header">
                                <div class="form-card-icon"><i class="bi bi-reply-fill"></i></div>
                                <div class="form-card-heading">
                                    <h3>Return for Correction</h3>
                                    <p>The creator will be able to edit and resubmit.</p>
                                </div>
                            </div>

                            <div class="form-section-card">
                                <div class="form-grid">
                                    <div class="form-field form-grid-full">
                                        <label for="return_reason" class="form-label">Reason <span>*</span></label>
                                        <div class="form-textarea-wrap">
                                            <textarea id="return_reason" name="reason" rows="3" class="form-control-custom" maxlength="2000" required></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-actions portal-form-actions">
                                <button type="button" class="form-btn form-btn-light" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="form-btn form-btn-primary">
                                    <span class="form-btn-spinner"></span>
                                    <i class="bi bi-reply-fill"></i>
                                    Return
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endcan

        @can('approve', $paymentRequest)
            <div class="modal fade" id="approveModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <form method="POST" action="{{ route('payment-requests.approve', $paymentRequest) }}" class="form-body">
                            @csrf
                            <div class="form-card-header">
                                <div class="form-card-icon"><i class="bi bi-check-circle-fill"></i></div>
                                <div class="form-card-heading">
                                    <h3>Approve Request</h3>
                                    <p>{{ $paymentRequest->displayReference() }} &middot; {{ $paymentRequest->payee_name ?? optional($paymentRequest->consultancyDetails)->consultant_name }} &middot; {{ $paymentRequest->formattedAmount() }}</p>
                                </div>
                            </div>

                            <div class="form-section-card">
                                <div class="form-grid">
                                    <div class="form-field form-grid-full">
                                        <label for="approve_comment" class="form-label">Comment</label>
                                        <div class="form-textarea-wrap">
                                            <textarea id="approve_comment" name="comment" rows="2" class="form-control-custom" maxlength="2000"></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-actions portal-form-actions">
                                <button type="button" class="form-btn form-btn-light" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="form-btn form-btn-primary">
                                    <span class="form-btn-spinner"></span>
                                    <i class="bi bi-check-circle-fill"></i>
                                    Confirm Approval
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endcan

        @can('reject', $paymentRequest)
            <div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <form method="POST" action="{{ route('payment-requests.reject', $paymentRequest) }}" class="form-body">
                            @csrf
                            <div class="form-card-header">
                                <div class="form-card-icon"><i class="bi bi-x-circle-fill"></i></div>
                                <div class="form-card-heading">
                                    <h3>Reject Request</h3>
                                    <p>This is a terminal action - the request cannot be edited or resubmitted afterward.</p>
                                </div>
                            </div>

                            <div class="form-section-card">
                                <div class="form-grid">
                                    <div class="form-field form-grid-full">
                                        <label for="reject_reason" class="form-label">Reason <span>*</span></label>
                                        <div class="form-textarea-wrap">
                                            <textarea id="reject_reason" name="reason" rows="3" class="form-control-custom" maxlength="2000" required></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-actions portal-form-actions">
                                <button type="button" class="form-btn form-btn-light" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="form-btn form-btn-danger">
                                    <span class="form-btn-spinner"></span>
                                    <i class="bi bi-x-circle-fill"></i>
                                    Confirm Rejection
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endcan

        @can('markPaid', $paymentRequest)
            <div class="modal fade" id="markPaidModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <form method="POST" action="{{ route('payment-requests.mark-paid', $paymentRequest) }}" class="form-body">
                            @csrf
                            <div class="form-card-header">
                                <div class="form-card-icon"><i class="bi bi-cash-stack"></i></div>
                                <div class="form-card-heading">
                                    <h3>Record Payment</h3>
                                    <p>Once saved, this request becomes Paid and is locked.</p>
                                </div>
                            </div>

                            <div class="form-section-card">
                                <div class="form-grid">
                                    <div class="form-field">
                                        <label for="paid_payment_date" class="form-label">Payment Date <span>*</span></label>
                                        <div class="form-input-wrap">
                                            <i class="bi bi-calendar-event-fill"></i>
                                            <input type="date" id="paid_payment_date" name="payment_date" value="{{ now()->format('Y-m-d') }}" class="form-control-custom" required>
                                        </div>
                                    </div>

                                    @if ($type->usesChequeNumber())
                                        <div class="form-field">
                                            <label for="paid_cheque_number" class="form-label">Cheque Number <span>*</span></label>
                                            <div class="form-input-wrap">
                                                <i class="bi bi-vector-pen"></i>
                                                <input type="text" id="paid_cheque_number" name="cheque_number" value="{{ $paymentRequest->cheque_number }}" class="form-control-custom" maxlength="100" required>
                                            </div>
                                        </div>
                                    @endif

                                    @if ($type->usesTransactionReference())
                                        <div class="form-field">
                                            <label for="paid_transaction_reference" class="form-label">Transaction Reference <span>*</span></label>
                                            <div class="form-input-wrap">
                                                <i class="bi bi-bank2"></i>
                                                <input type="text" id="paid_transaction_reference" name="transaction_reference" value="{{ $paymentRequest->transaction_reference }}" class="form-control-custom" maxlength="100" required>
                                            </div>
                                        </div>
                                    @endif

                                    @if ($type->usesCashier())
                                        <div class="form-field">
                                            <label for="paid_cashier_name" class="form-label">Cashier <span>*</span></label>
                                            <div class="form-input-wrap">
                                                <i class="bi bi-person-badge-fill"></i>
                                                <input type="text" id="paid_cashier_name" name="cashier_name" value="{{ $paymentRequest->cashier_name }}" class="form-control-custom" maxlength="255" required>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <div class="form-actions portal-form-actions">
                                <button type="button" class="form-btn form-btn-light" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="form-btn form-btn-primary">
                                    <span class="form-btn-spinner"></span>
                                    <i class="bi bi-cash-stack"></i>
                                    Confirm Payment
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endcan

        @can('cancel', $paymentRequest)
            <div class="modal fade" id="cancelModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <form method="POST" action="{{ route('payment-requests.cancel', $paymentRequest) }}" class="form-body">
                            @csrf
                            <div class="form-card-header">
                                <div class="form-card-icon"><i class="bi bi-slash-circle-fill"></i></div>
                                <div class="form-card-heading">
                                    <h3>Cancel Request</h3>
                                    <p>This is a terminal action - the request is kept for audit but can no longer move forward.</p>
                                </div>
                            </div>

                            <div class="form-section-card">
                                <div class="form-grid">
                                    <div class="form-field form-grid-full">
                                        <label for="cancel_reason" class="form-label">Reason</label>
                                        <div class="form-textarea-wrap">
                                            <textarea id="cancel_reason" name="reason" rows="2" class="form-control-custom" maxlength="2000"></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-actions portal-form-actions">
                                <button type="button" class="form-btn form-btn-light" data-bs-dismiss="modal">Go Back</button>
                                <button type="submit" class="form-btn form-btn-danger">
                                    <span class="form-btn-spinner"></span>
                                    <i class="bi bi-slash-circle-fill"></i>
                                    Confirm Cancellation
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endcan

@endsection
