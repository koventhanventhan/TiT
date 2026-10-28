<?php

namespace Tests\Feature;

use App\Models\Institute;
use App\Models\Payment;
use App\Models\Subject;
use App\Models\SubjectTopup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AdminStudentsListSimpleAndPendingPaymentsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $unconfirmedStudent;
    protected User $confirmedStudent;
    protected Institute $institute;

    protected function setUp(): void
    {
        parent::setUp();

        $this->institute = Institute::create([
            'name' => 'Test Institute',
            'slug' => 'test-institute',
            'code' => 'TIT',
            'is_active' => true,
            'index_format' => 'grade',
            'index_prefix' => 'TIT',
        ]);

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'institute_id' => $this->institute->id,
            'email' => 'admin@test.com',
        ]);

        // Student awaiting approval (unconfirmed)
        $this->unconfirmedStudent = User::factory()->create([
            'role' => 'user',
            'full_name' => 'Alice Unconfirmed',
            'email' => 'alice@test.com',
            'current_grade' => 'Grade 10',
            'medium' => 'english',
            'institute_id' => $this->institute->id,
            'admin_confirmed_at' => null,
            'registration_status' => 'payment_completed',
        ]);

        // Student confirmed
        $this->confirmedStudent = User::factory()->create([
            'role' => 'user',
            'full_name' => 'Bob Confirmed',
            'email' => 'bob@test.com',
            'current_grade' => 'Grade 10',
            'medium' => 'english',
            'institute_id' => $this->institute->id,
            'admin_confirmed_at' => now(),
            'registration_status' => 'approved',
            'index_number' => 'TIT/10/001',
        ]);
    }

    /**
     * Requirement: Restored simple student list:
     * - 2-line STATUS column (badge: Graduated / Deactivated / Confirmed / "Awaiting approval"; line 2: "Paid <date>" or "Not paid").
     * - Does NOT contain "Pending (", inline "Mark Paid", row dropdown "Confirm + Mark Paid", "Assign Index Number", "Change Index Number", "Convert to new format".
     * - Displays friendly "Awaiting approval" badge instead of raw payment_completed.
     */
    public function test_students_list_page_has_clean_simple_view_without_clutter(): void
    {
        // Add pending payment for unconfirmed student for current month
        $currYM = now()->format('Y-m');
        Payment::create([
            'user_id' => $this->unconfirmedStudent->id,
            'institute_id' => $this->institute->id,
            'year_month' => $currYM,
            'amount' => 2000.00,
            'status' => 'pending',
            'payment_method' => 'offline',
            'gateway_ref' => 'OFF_' . time(),
        ]);

        $res = $this->actingAs($this->admin, 'web')->get(route('admin.students.index'));
        $res->assertStatus(200);

        // Friendly badge appears
        $res->assertSee('Awaiting approval');
        $res->assertSee('Confirmed');
        $res->assertSee('Not paid');

        // Verify none of the cluttering strings appear in the HTML
        $content = $res->getContent();
        $this->assertStringNotContainsString('Pending (', $content);
        $this->assertStringNotContainsString('Confirm + Mark Paid', $content);
        $this->assertStringNotContainsString('Assign Index Number<', $content);
        $this->assertStringNotContainsString('Change Index Number', $content);
        $this->assertStringNotContainsString('Convert to new format', $content);
        $this->assertStringNotContainsString('promptConfirmAndMarkPaid', $content);
        $this->assertStringNotContainsString('promptAssignIndexSingle', $content);
        $this->assertStringNotContainsString('promptReassignIndexSingle', $content);
        $this->assertStringNotContainsString('promptConvertToNewFormatIndex', $content);
    }

    /**
     * Requirement: Header strip Pending Payments pill links to dedicated page
     * and old ?pending_payments=1 redirects 302 to admin.students.pending-payments.
     */
    public function test_pending_payments_strip_and_redirect_behavior(): void
    {
        // Header strip link
        $res = $this->actingAs($this->admin, 'web')->get(route('admin.students.index'));
        $res->assertStatus(200);
        $res->assertSee(route('admin.students.pending-payments'));

        // Old filter ?pending_payments=1 redirects to dedicated page
        $redirectRes = $this->actingAs($this->admin, 'web')
            ->get(route('admin.students.index', ['pending_payments' => 1]));
        $redirectRes->assertRedirect(route('admin.students.pending-payments'));

        // Sidebar link exists
        $res->assertSee(route('admin.students.pending-payments'));
    }

    /**
     * Requirement: Dedicated pending payments page displays monthly and top-up rows,
     * supports filters (type, grade, search), and allows Mark Paid with "Received by".
     */
    public function test_dedicated_pending_payments_page_monthly_and_topup(): void
    {
        Mail::fake();
        $currYM = now()->format('Y-m');

        // 1. Pending monthly payment for unconfirmed student
        $monthlyPayment = Payment::create([
            'user_id' => $this->unconfirmedStudent->id,
            'institute_id' => $this->institute->id,
            'year_month' => $currYM,
            'amount' => 2500.00,
            'status' => 'pending',
            'kind' => 'monthly',
            'payment_method' => 'offline',
            'gateway_ref' => 'OFF_MONTH_' . time(),
        ]);

        // 2. Pending subject topup for confirmed student
        $topup = SubjectTopup::create([
            'user_id' => $this->confirmedStudent->id,
            'institute_id' => $this->institute->id,
            'year_month' => $currYM,
            'added_subjects' => ['Chemistry'],
            'previous_subjects' => ['Physics'],
            'amount' => 1500.00,
            'status' => 'pending',
            'payment_method' => 'offline',
        ]);

        $topupPayment = Payment::create([
            'user_id' => $this->confirmedStudent->id,
            'institute_id' => $this->institute->id,
            'year_month' => $currYM,
            'amount' => 1500.00,
            'status' => 'pending',
            'kind' => 'topup',
            'payment_method' => 'offline',
            'meta' => [
                'topup_id' => $topup->id,
                'added_subjects' => ['Chemistry'],
            ],
        ]);
        $topup->update(['payment_id' => $topupPayment->id]);

        // Visit dedicated page
        $pageRes = $this->actingAs($this->admin, 'web')
            ->get(route('admin.students.pending-payments'));
        $pageRes->assertStatus(200);

        // Both rows are listed
        $pageRes->assertSee('Alice Unconfirmed');
        $pageRes->assertSee('2,500.00');
        $pageRes->assertSee('Monthly');

        $pageRes->assertSee('Bob Confirmed');
        $pageRes->assertSee('1,500.00');
        $pageRes->assertSee('Subject top-up');
        $pageRes->assertSee('Chemistry');

        // Test Type Filter: Monthly only
        $monthlyRes = $this->actingAs($this->admin, 'web')
            ->get(route('admin.students.pending-payments', ['type' => 'monthly']));
        $monthlyRes->assertStatus(200);
        $monthlyRes->assertSee('Alice Unconfirmed');
        $monthlyRes->assertDontSee('Bob Confirmed');

        // Test Type Filter: Topup only
        $topupRes = $this->actingAs($this->admin, 'web')
            ->get(route('admin.students.pending-payments', ['type' => 'topup']));
        $topupRes->assertStatus(200);
        $topupRes->assertSee('Bob Confirmed');
        $topupRes->assertDontSee('Alice Unconfirmed');

        // Test Search Filter
        $searchRes = $this->actingAs($this->admin, 'web')
            ->get(route('admin.students.pending-payments', ['search' => 'Alice']));
        $searchRes->assertStatus(200);
        $searchRes->assertSee('Alice Unconfirmed');
        $searchRes->assertDontSee('Bob Confirmed');

        // Mark Paid on Monthly payment with Received by = cash
        $markPaidRes = $this->actingAs($this->admin, 'web')
            ->post(route('admin.students.payments.mark-paid', [
                'id' => $this->unconfirmedStudent->id,
                'payment' => $monthlyPayment->id,
            ]), [
                'received_by' => 'cash',
            ]);
        $markPaidRes->assertSessionHas('success');

        $monthlyPayment->refresh();
        $this->assertEquals('paid', $monthlyPayment->status);
        $this->assertEquals('cash', $monthlyPayment->meta['received_by'] ?? null);

        // Mark Paid on Top-up via admin.students.subject-topups.mark-paid with Received by = bank_transfer
        $topupMarkPaidRes = $this->actingAs($this->admin, 'web')
            ->post(route('admin.students.subject-topups.mark-paid', $topup->id), [
                'received_by' => 'bank_transfer',
            ]);
        $topupMarkPaidRes->assertSessionHas('success');

        $topupPayment->refresh();
        $topup->refresh();
        $this->assertEquals('paid', $topupPayment->status);
        $this->assertEquals('paid', $topup->status);
        $this->assertEquals('bank_transfer', $topupPayment->meta['received_by'] ?? null);
    }

    /**
     * Requirement: Cancel button on pending payments page cancels payment request.
     */
    public function test_cancel_pending_payment(): void
    {
        $currYM = now()->format('Y-m');
        $payment = Payment::create([
            'user_id' => $this->unconfirmedStudent->id,
            'institute_id' => $this->institute->id,
            'year_month' => $currYM,
            'amount' => 3000.00,
            'status' => 'pending',
            'kind' => 'monthly',
            'payment_method' => 'offline',
            'gateway_ref' => 'OFF_CANCEL_' . time(),
        ]);

        $cancelRes = $this->actingAs($this->admin, 'web')
            ->post(route('admin.students.pending-payments.cancel', $payment->id));
        $cancelRes->assertSessionHas('success');

        $payment->refresh();
        $this->assertEquals('cancelled', $payment->status);
    }
}
