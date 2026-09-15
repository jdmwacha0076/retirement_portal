<?php

namespace Tests\Feature\PaymentRequests;

use App\Enums\PaymentRequestStatus;
use App\Models\PaymentRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * End-to-end coverage of the Payment Request & Voucher workflow, run
 * against a real (in-memory/sqlite) database via RefreshDatabase - not
 * mocks - so a regression in PaymentRequestWorkflowService's transaction
 * boundaries or PaymentRequestPolicy's authorization rules actually fails
 * a test, not just a static read of the code.
 *
 * Run with: php artisan test --filter=PaymentRequestWorkflowTest
 */
class PaymentRequestWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role = 'staff'): User
    {
        return User::factory()->create([
            'role' => $role,
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
    }

    private function cashPayload(): array
    {
        return [
            'payee_name' => 'Jane Consultant',
            'payment_date' => now()->format('Y-m-d'),
            'currency' => 'TZS',
            'description' => 'Office supplies reimbursement',
            'amount' => 150000,
        ];
    }

    public function test_staff_can_save_a_draft_and_it_has_no_reference_number_yet(): void
    {
        $staff = $this->makeUser('staff');

        $response = $this->actingAs($staff)->post(route('payment-requests.store', 'cash'), [
            ...$this->cashPayload(),
            'form_action' => 'draft',
        ]);

        $paymentRequest = PaymentRequest::firstOrFail();

        $response->assertRedirect(route('payment-requests.show', $paymentRequest));
        $this->assertSame(PaymentRequestStatus::Draft, $paymentRequest->status);
        $this->assertNull($paymentRequest->reference_number);
        $this->assertSame($staff->id, $paymentRequest->created_by);
    }

    public function test_submitting_generates_a_permanent_reference_number_once(): void
    {
        $staff = $this->makeUser('staff');

        $this->actingAs($staff)->post(route('payment-requests.store', 'cash'), [
            ...$this->cashPayload(),
            'form_action' => 'submit',
        ]);

        $paymentRequest = PaymentRequest::firstOrFail();

        $this->assertSame(PaymentRequestStatus::Submitted, $paymentRequest->status);
        $this->assertNotNull($paymentRequest->reference_number);
        $this->assertStringContainsString('PRX/PAY/'.now()->format('Y').'/', $paymentRequest->reference_number);

        // Returning it and resubmitting must reuse the same reference,
        // never generate a second one for the same request.
        $admin = $this->makeUser('admin');
        $originalReference = $paymentRequest->reference_number;

        $this->actingAs($admin)->post(route('payment-requests.return', $paymentRequest), [
            'reason' => 'Please attach the receipt.',
        ]);

        $this->actingAs($staff)->post(route('payment-requests.submit', $paymentRequest));

        $paymentRequest->refresh();
        $this->assertSame(PaymentRequestStatus::Submitted, $paymentRequest->status);
        $this->assertSame($originalReference, $paymentRequest->reference_number);
    }

    public function test_full_workflow_from_submission_to_paid(): void
    {
        $creator = $this->makeUser('staff');
        $assignee = $this->makeUser('staff');
        $admin = $this->makeUser('admin');

        $this->actingAs($creator)->post(route('payment-requests.store', 'cash'), [
            ...$this->cashPayload(),
            'form_action' => 'submit',
        ]);

        $paymentRequest = PaymentRequest::firstOrFail();

        $this->actingAs($admin)->post(route('payment-requests.assign', $paymentRequest), [
            'assigned_to' => $assignee->id,
        ])->assertSessionHasNoErrors();

        $paymentRequest->refresh();
        $this->assertSame(PaymentRequestStatus::Assigned, $paymentRequest->status);
        $this->assertSame($assignee->id, $paymentRequest->current_assignee_id);

        $this->actingAs($admin)->post(route('payment-requests.approve', $paymentRequest), [
            'comment' => 'Looks good.',
        ])->assertSessionHasNoErrors();

        $paymentRequest->refresh();
        $this->assertSame(PaymentRequestStatus::Approved, $paymentRequest->status);
        $this->assertSame($admin->id, $paymentRequest->approved_by);

        $this->actingAs($admin)->post(route('payment-requests.ready-for-payment', $paymentRequest))
            ->assertSessionHasNoErrors();

        $this->actingAs($admin)->post(route('payment-requests.mark-paid', $paymentRequest), [
            'payment_date' => now()->format('Y-m-d'),
            'cashier_name' => 'Front Desk Cashier',
        ])->assertSessionHasNoErrors();

        $paymentRequest->refresh();
        $this->assertSame(PaymentRequestStatus::Paid, $paymentRequest->status);
        $this->assertSame($admin->id, $paymentRequest->processed_by);
        $this->assertNotNull($paymentRequest->paid_at);

        // Every transition left an audit trail row - nothing silently skipped.
        $this->assertGreaterThanOrEqual(5, $paymentRequest->history()->count());

        // Paid is terminal - no further workflow action is legal.
        $this->assertSame([], PaymentRequestStatus::Paid->allowedTransitions());
    }

    public function test_consultancy_withholding_tax_is_calculated_server_side(): void
    {
        $staff = $this->makeUser('staff');

        $this->actingAs($staff)->post(route('payment-requests.store', 'consultancy-cheque'), [
            'consultant_name' => 'Dr. Amina Consultant',
            'tin_number' => '123-456-789',
            'client_name' => 'Praxis for Health and Development',
            'payment_date' => now()->format('Y-m-d'),
            'currency' => 'TZS',
            'description' => 'Mid-term evaluation consultancy',
            'consultancy_fee' => 1000000,
            'withholding_tax_percentage' => 5,
            'form_action' => 'draft',
        ]);

        $paymentRequest = PaymentRequest::with('consultancyDetails')->firstOrFail();
        $details = $paymentRequest->consultancyDetails;

        $this->assertNotNull($details);
        $this->assertEquals(50000, (float) $details->withholding_tax_amount);
        $this->assertEquals(950000, (float) $details->amount_due);
        $this->assertEquals(950000, (float) $paymentRequest->amount);
    }

    public function test_non_admin_cannot_approve_a_request(): void
    {
        $creator = $this->makeUser('staff');
        $otherStaff = $this->makeUser('staff');

        $this->actingAs($creator)->post(route('payment-requests.store', 'cash'), [
            ...$this->cashPayload(),
            'form_action' => 'submit',
        ]);

        $paymentRequest = PaymentRequest::firstOrFail();

        $this->actingAs($otherStaff)
            ->post(route('payment-requests.approve', $paymentRequest), ['comment' => 'Trying anyway'])
            ->assertForbidden();

        $this->assertSame(PaymentRequestStatus::Submitted, $paymentRequest->fresh()->status);
    }

    public function test_staff_unrelated_to_a_request_cannot_view_it(): void
    {
        $creator = $this->makeUser('staff');
        $unrelated = $this->makeUser('staff');

        $this->actingAs($creator)->post(route('payment-requests.store', 'cash'), [
            ...$this->cashPayload(),
            'form_action' => 'draft',
        ]);

        $paymentRequest = PaymentRequest::firstOrFail();

        $this->actingAs($unrelated)
            ->get(route('payment-requests.show', $paymentRequest))
            ->assertForbidden();

        $this->actingAs($creator)
            ->get(route('payment-requests.show', $paymentRequest))
            ->assertOk();
    }

    public function test_illegal_transition_is_rejected_without_a_server_error(): void
    {
        $creator = $this->makeUser('staff');
        $admin = $this->makeUser('admin');

        // Still a Draft - never submitted - so approve() must refuse it.
        $this->actingAs($creator)->post(route('payment-requests.store', 'cash'), [
            ...$this->cashPayload(),
            'form_action' => 'draft',
        ]);

        $paymentRequest = PaymentRequest::firstOrFail();

        $response = $this->actingAs($admin)
            ->post(route('payment-requests.approve', $paymentRequest), ['comment' => null]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertSame(PaymentRequestStatus::Draft, $paymentRequest->fresh()->status);
    }

    public function test_drafts_and_returned_requests_are_the_only_ones_a_creator_can_edit(): void
    {
        $creator = $this->makeUser('staff');

        $this->actingAs($creator)->post(route('payment-requests.store', 'cash'), [
            ...$this->cashPayload(),
            'form_action' => 'submit',
        ]);

        $paymentRequest = PaymentRequest::firstOrFail();

        // Submitted - no longer editable by the creator.
        $this->actingAs($creator)
            ->get(route('payment-requests.edit', $paymentRequest))
            ->assertForbidden();
    }
}
