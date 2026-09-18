@extends('layouts.admin')

@section('title')
New Activity | Retirement Portal
@endsection

@section('bodyClass', 'light-header-page form-page')

@section('content')

        <div class="page-title web-hero web-panel-hero page-header--admin">
            <div class="container position-relative" data-aos="fade-up">

                <div class="web-hero-badge">
                    <i class="bi bi-clipboard2-data-fill"></i>
                    New Activity
                </div>

                <h1>Register an Activity</h1>

                <p>The umbrella record for a travel, training, or project activity - its budget is built afterwards.</p>

                <div class="breadcrumbs">
                    <ol>
                        <li><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li><a href="{{ route('activities.index') }}">Activities</a></li>
                        <li>New Activity</li>
                    </ol>
                </div>

            </div>
        </div>

        <section class="form-shell section">
            <div class="container" data-aos="fade-up" data-aos-delay="100">

                <div class="form-section-title-row">
                    <div class="section-title form-section-title mb-0">
                        <span class="portal-section-eyebrow">New Activity</span>
                        <h2>Activity Details</h2>
                        <p>A system reference is generated automatically once you save.</p>
                    </div>

                    <a href="{{ route('activities.index') }}" class="form-btn form-btn-light form-back-btn">
                        <i class="bi bi-arrow-left"></i>
                        Back to Activities
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

                        <h3>Before you save</h3>

                        <div class="form-guide-list">
                            <div class="form-guide-item">
                                <i class="bi bi-check-circle-fill"></i>
                                <span>Registering an activity creates its permanent <strong>ACT</strong> reference and starts it in <strong>Draft</strong>.</span>
                            </div>

                            <div class="form-guide-item">
                                <i class="bi bi-check-circle-fill"></i>
                                <span>The <strong>Budget / Accounting Code</strong> is optional - it's only used for the printed budget's item numbering.</span>
                            </div>

                            <div class="form-guide-item">
                                <i class="bi bi-check-circle-fill"></i>
                                <span>Once you build and submit a budget for this activity, its core details lock and this form can no longer be edited freely.</span>
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
                                    <i class="bi bi-plus-circle-fill"></i>
                                    New
                                </div>

                                <h3>Activity Registration</h3>
                                <p>Complete the activity information below.</p>
                            </div>
                        </div>

                        <form action="{{ route('activities.store') }}" method="POST" class="form-body" autocomplete="off" novalidate>
                            @csrf

                            @include('activities._form-fields', ['activity' => null])

                            <div class="form-actions portal-form-actions">
                                <button type="submit" class="form-btn form-btn-primary">
                                    <span class="form-btn-spinner"></span>
                                    <i class="bi bi-save"></i>
                                    Register Activity
                                </button>
                            </div>
                        </form>

                    </div>

                </div>

            </div>
        </section>

@endsection
