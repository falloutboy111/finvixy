<?php

namespace Tests\Unit\Services;

use App\Models\Expense;
use App\Models\Organisation;
use App\Models\OrganisationBudget;
use App\Services\BudgetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetServiceTest extends TestCase
{
    use RefreshDatabase;

    private BudgetService $budgetService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->budgetService = app(BudgetService::class);
    }

    /**
     * Test 1: Budget creation & retrieval
     */
    public function test_budget_creation_and_retrieval()
    {
        $org = Organisation::factory()->create();

        $budget = $this->budgetService->setBudget(
            organisationId: $org->id,
            vendor: 'Woolworths',
            category: 'Groceries',
            budgetLimit: 500.00
        );

        $this->assertNotNull($budget->id);
        $this->assertEquals('Woolworths', $budget->vendor_name);
        $this->assertEquals('Groceries', $budget->expense_category);
        $this->assertEquals(500.00, (float) $budget->budget_limit);

        $budgets = $this->budgetService->getOrganisationBudgets($org->id);
        $this->assertCount(1, $budgets);
    }

    /**
     * Test 2: Monthly spending tracking
     */
    public function test_monthly_spending_tracking()
    {
        $org = Organisation::factory()->create();
        $budget = $this->budgetService->setBudget(
            organisationId: $org->id,
            vendor: 'Woolworths',
            category: 'Groceries',
            budgetLimit: 500.00
        );

        $expense = Expense::factory()->for($org)->create([
            'name' => 'Woolworths',
            'category' => 'Groceries',
            'amount' => 100.00,
        ]);

        $this->budgetService->checkExpenseBudget($expense);
        $budget->refresh();

        $this->assertEquals(100.00, (float) $budget->current_month_spent);
    }

    /**
     * Test 3: Budget exceeded detection
     */
    public function test_budget_exceeded_detection()
    {
        $org = Organisation::factory()->create();
        $budget = $this->budgetService->setBudget(
            organisationId: $org->id,
            vendor: 'Woolworths',
            category: 'Groceries',
            budgetLimit: 500.00
        );

        // Add expenses totaling 550
        $expense1 = Expense::factory()->for($org)->create([
            'name' => 'Woolworths',
            'category' => 'Groceries',
            'amount' => 400.00,
        ]);
        $this->budgetService->checkExpenseBudget($expense1);

        $expense2 = Expense::factory()->for($org)->create([
            'name' => 'Woolworths',
            'category' => 'Groceries',
            'amount' => 150.00,
        ]);
        $alert = $this->budgetService->checkExpenseBudget($expense2);

        $this->assertNotNull($alert);
        $this->assertTrue($alert['exceeded']);
        $this->assertEquals('Woolworths', $alert['vendor_name']);
        $this->assertEquals(550.00, $alert['current_month_spent']);
        $this->assertEquals(50.00, $alert['overage']);
    }

    /**
     * Test 4: WhatsApp alert formatting
     */
    public function test_whatsapp_alert_formatting()
    {
        $org = Organisation::factory()->create();
        $budget = $this->budgetService->setBudget(
            organisationId: $org->id,
            vendor: 'Woolworths',
            category: 'Groceries',
            budgetLimit: 500.00
        );

        $expense1 = Expense::factory()->for($org)->create([
            'name' => 'Woolworths',
            'category' => 'Groceries',
            'amount' => 400.00,
        ]);
        $this->budgetService->checkExpenseBudget($expense1);

        $expense2 = Expense::factory()->for($org)->create([
            'name' => 'Woolworths',
            'category' => 'Groceries',
            'amount' => 150.00,
        ]);
        $alert = $this->budgetService->checkExpenseBudget($expense2);

        // Check the alert contains all required fields for formatting
        $this->assertTrue($alert['exceeded']);
        $this->assertEquals('Woolworths', $alert['vendor_name']);
        $this->assertEquals('Groceries', $alert['category']);
        $this->assertEquals(500.00, $alert['budget_limit']);
        $this->assertEquals(550.00, $alert['current_month_spent']);
        $this->assertEquals(50.00, $alert['overage']);
    }

    /**
     * Test 5: Budget reset logic
     */
    public function test_budget_reset_logic()
    {
        $org = Organisation::factory()->create();
        $budget = OrganisationBudget::factory()->for($org)->create([
            'vendor_name' => 'Woolworths',
            'expense_category' => 'Groceries',
            'budget_limit' => 500.00,
            'current_month_spent' => 500.00,
            'monthly_reset_day' => 1,
            'last_reset_at' => now()->subMonths(2),
        ]);

        // Calling isExceeded should trigger reset
        $isExceeded = $budget->isExceeded();

        // After reset, spending should be 0
        $budget->refresh();
        $this->assertEquals(0, (float) $budget->current_month_spent);
    }
}
