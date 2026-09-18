@extends('layouts.admin')

@section('title')
Edit {{ $activity->reference }} | Retirement Portal
@endsection

@section('bodyClass', 'light-header-page form-page')

@section('content')

        <div class="page-title web-hero web-panel-hero page-header--admin">
            <div class="container position-relative" data-aos="fade-up">

                <div class="web-hero-badge">
                    <i class="bi bi-clipboard2-data-fill"></i>
                    Edit Activity
                </div>

                <h1>{{ $activity->reference }}</h1>

                <p>{{ $activity->title }}</p>

                <div class="breadcrumbs">
                    <ol>
                        <li><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li><a href="{{ route('activities.index') }}">Activities</a></li>
                        <li><a href="{{ route('activities.show', $activity) }}">{{ $activity->reference }}</a></li>
                        <li>Edit</li>
                    </ol>
                </div>

            </div>
        </div>

        <section class="form-shell section">
            <div class="container" data-aos="fade-up" data-aos-delay="100">

                <div class="form-section-title-row">
                    <div class="section-title form-section-title mb-0">
                        <span class="portal-section-eyebrow">Edit Activity</span>
                        <h2>Activity Details</h2>
                        <p>The system reference cannot change once generated.</p>
                    </div>

                    <a href="{{ route('activities.show', $activity) }}" class="form-btn form-btn-light form-back-btn">
                        <i class="bi bi-arrow-left"></i>
                        Back to Activity
                    </a>
                </div>

                <div class="form-feedback">
                    @include('components.form-alerts')
                </div>

                <div class="form-layout">

                    <aside class="form-guide-card portal-sticky-card" data-aos="fade-up" data-aos-delay="120">
                        <div class="form-guide-badge">
                            <i class="bi bi-lightbulb-fill"></i>
                            Quick Guide
                        </div>

                        <h3>Editing {{ $activity->reference }}</h3>

                        <div class="form-guide-list">
                            <div class="form-guide-item">
                                <i class="bi bi-check-circle-fill"></i>
                                <span>Details stay editable only while the activity is <strong>Draft</strong> and has no submitted budget.</span>
                            </div>

                            <div class="form-guide-item">
                                <i class="bi bi-check-circle-fill"></i>
                                <span>Changing the currency here does not affect a budget already in progress.</span>
                            </div>
                        </div>

                        <div class="form-guide-note">
                            <i class="bi bi-info-circle-fill"></i>
                            <span>Required fields are marked with a red star.</span>
                        </div>
                    </aside>

                    <div class="form-card portal-form-card" data-aos="fade-up" data-aos-delay="160">

                        <div class="form-card-header">
                            <div class="form-card-icon">
                                <i class="bi bi-clipboard2-data-fill"></i>
                            </div>

                            <div class="form-card-heading">
                                <div class="form-card-badge">
                                    <i class="bi bi-pencil-square"></i>
                                    Editing
                                </div>

                                <h3>{{ $activity->reference }}</h3>
                                <p>Update the activity information below.</p>
                            </div>
                        </div>

                        <form action="{{ route('activities.update', $activity) }}" method="POST" class="form-body" autocomplete="off" novalidate>
                            @csrf
                            @method('PUT')

                            @include('activities._form-fields', ['activity' => $activity])

                            <div class="form-actions portal-form-actions">
                                <button type="submit" class="form-btn form-btn-primary">
                                    <span class="form-btn-spinner"></span>
                                    <i class="bi bi-save"></i>
                                    Save Changes
                                </button>
                            </div>
                        </form>

                    </div>

                </div>

            </div>
        </section>

@endsection
