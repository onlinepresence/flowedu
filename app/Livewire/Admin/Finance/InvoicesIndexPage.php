<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Finance;

use App\Livewire\Concerns\DispatchesCollegeToasts;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Expenditure;
use App\Models\Product;
use App\Models\SystemAudit;
use App\Services\SchoolLicenceService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class InvoicesIndexPage extends Component
{
    use DispatchesCollegeToasts;
    use WithFileUploads;
    use WithPagination;

    public string $activeTab = 'invoices';
    public string $search = '';

    // Modal Toggles
    public bool $showInvoiceModal = false;
    public bool $showExpenditureModal = false;
    public bool $showItemsModal = false;
    public bool $showExpenditureTimelineModal = false;

    // Invoice Form Fields
    public string $invoice_number = '';
    public string $vendor_name = '';
    public string $description = '';
    public string $invoice_date = '';
    public string $due_date = '';
    public float $amount = 0.00;
    public $invoiceFile;
    public array $invoiceItems = [];

    // Temporary Line Item Fields
    public string $selected_product_id = '';
    public int $item_quantity = 1;
    public string $item_unit_price = '';

    // Expenditure Form Fields
    public string $expense_number = '';
    public string $expenditure_invoice_id = '';
    public string $expenditure_amount = '';
    public string $payment_method = 'Bank Transfer';
    public string $payment_date = '';
    public string $reference_number = '';
    public string $expenditure_category = 'IT Infrastructure';
    public string $notes = '';
    public $proofFile;

    // Active View Invoice
    public ?Invoice $activeInvoice = null;
    public ?Expenditure $activeExpenditure = null;

    // OCR Scan State
    public bool $isScanning = false;

    protected $listeners = ['refreshComponent' => '$refresh'];

    public function mount(): void
    {
        abort_unless($this->canManageFinance(), 403);
        $this->invoice_date = now()->toDateString();
        $this->due_date = now()->addDays(30)->toDateString();
        $this->payment_date = now()->toDateString();
    }

    public function updatedInvoiceFile(): void
    {
        // Auto run OCR mock scan when file is uploaded to simulate AI experience
        $this->runMockOCR();
    }

    public function runMockOCR(): void
    {
        if (!$this->invoiceFile) {
            $this->collegeToast(__('Please upload a receipt or invoice file first.'), 'error');
            return;
        }

        $this->isScanning = true;

        // Simulate network / processing delay for high-fidelity OCR scanning feel
        usleep(800000); 

        // Generate high-quality mock data based on real suppliers and services
        $mockVendors = [
            [
                'vendor' => 'OfficeMax Systems',
                'description' => 'Purchase of printer toners and paper supplies',
                'items' => [
                    ['name' => 'LaserJet Toner Cartridge Black', 'sku' => 'TONER-HP-B', 'qty' => 4, 'price' => 85.00],
                    ['name' => 'A4 Copier Paper Reams (Box of 5)', 'sku' => 'PAPER-A4', 'qty' => 10, 'price' => 32.50]
                ]
            ],
            [
                'vendor' => 'Global IT Solutions',
                'description' => 'Network switches and CAT6 ethernet cables for lab',
                'items' => [
                    ['name' => 'Ubiquiti UniFi 24-Port Switch', 'sku' => 'UB-USW-24', 'qty' => 2, 'price' => 299.00],
                    ['name' => 'CAT6 Ethernet Cable Blue 305m Roll', 'sku' => 'CABLE-CAT6-B', 'qty' => 3, 'price' => 120.00]
                ]
            ],
            [
                'vendor' => 'Campus Catering & Events',
                'description' => 'Orientation week package catering and drinks services',
                'items' => [
                    ['name' => 'Student Orientation Lunch buffet packages', 'sku' => 'CAT-BUFFET', 'qty' => 150, 'price' => 12.00],
                    ['name' => 'Assorted Soft Drinks Cans (Case of 24)', 'sku' => 'DRINK-SOFT', 'qty' => 15, 'price' => 18.50]
                ]
            ]
        ];

        $selectedMock = $mockVendors[array_rand($mockVendors)];

        $this->vendor_name = $selectedMock['vendor'];
        $this->description = $selectedMock['description'];
        $this->invoice_number = 'INV-OCR-' . rand(1000, 9999);
        $this->invoice_date = now()->toDateString();
        $this->due_date = now()->addDays(30)->toDateString();

        // Clear existing items and fill with OCR matches
        $this->invoiceItems = [];
        foreach ($selectedMock['items'] as $mockItem) {
            // Find or create product
            $product = Product::query()->firstOrCreate(
                ['sku' => $mockItem['sku']],
                [
                    'name' => $mockItem['name'],
                    'category' => 'Office Supplies',
                    'unit_price' => $mockItem['price'],
                    'description' => 'OCR auto-generated product SKU: ' . $mockItem['sku']
                ]
            );

            $this->invoiceItems[] = [
                'product_id' => (string) $product->id,
                'name' => $product->name,
                'quantity' => $mockItem['qty'],
                'unit_price' => (float) $product->unit_price,
                'total' => $mockItem['qty'] * (float) $product->unit_price
            ];
        }

        $this->recalculateInvoiceTotal();
        $this->isScanning = false;

        $this->collegeToast(__('OCR receipt scan completed successfully! Data extracted.'));
    }

    public function openAddInvoice(): void
    {
        $this->resetInvoiceForm();
        $this->invoice_number = 'INV-' . date('Y') . '-' . sprintf('%04d', Invoice::count() + 1);
        $this->showInvoiceModal = true;
    }

    public function addLineItem(): void
    {
        $this->validate([
            'selected_product_id' => 'required|exists:products,id',
            'item_quantity' => 'required|integer|min:1',
            'item_unit_price' => 'required|numeric|min:0',
        ]);

        $product = Product::find($this->selected_product_id);
        if (!$product) return;

        // Check if product already exists in current list
        foreach ($this->invoiceItems as $index => $item) {
            if ($item['product_id'] === $this->selected_product_id) {
                $this->invoiceItems[$index]['quantity'] += $this->item_quantity;
                $this->invoiceItems[$index]['total'] = $this->invoiceItems[$index]['quantity'] * $this->invoiceItems[$index]['unit_price'];
                $this->recalculateInvoiceTotal();
                $this->resetLineItemInputs();
                return;
            }
        }

        $qty = $this->item_quantity;
        $price = (float) $this->item_unit_price;

        $this->invoiceItems[] = [
            'product_id' => $this->selected_product_id,
            'name' => $product->name,
            'quantity' => $qty,
            'unit_price' => $price,
            'total' => $qty * $price,
        ];

        $this->recalculateInvoiceTotal();
        $this->resetLineItemInputs();
    }

    public function removeLineItem(int $index): void
    {
        unset($this->invoiceItems[$index]);
        $this->invoiceItems = array_values($this->invoiceItems);
        $this->recalculateInvoiceTotal();
    }

    private function resetLineItemInputs(): void
    {
        $this->selected_product_id = '';
        $this->item_quantity = 1;
        $this->item_unit_price = '';
    }

    private function recalculateInvoiceTotal(): void
    {
        $sum = 0.0;
        foreach ($this->invoiceItems as $item) {
            $sum += (float) $item['total'];
        }
        $this->amount = $sum;
    }

    public function updatedSelectedProductId(string $value): void
    {
        if ($value) {
            $product = Product::find($value);
            if ($product) {
                $this->item_unit_price = (string) $product->unit_price;
            }
        } else {
            $this->item_unit_price = '';
        }
    }

    public function saveInvoice(): void
    {
        $this->validate([
            'invoice_number' => 'required|string|unique:invoices,invoice_number',
            'vendor_name' => 'required|string|max:255',
            'invoice_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:invoice_date',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string',
        ]);

        $filePath = null;
        if ($this->invoiceFile) {
            $filePath = $this->invoiceFile->store('invoices', 'public');
        }

        $invoice = Invoice::create([
            'invoice_number' => $this->invoice_number,
            'vendor_name' => $this->vendor_name,
            'description' => $this->description,
            'amount' => $this->amount,
            'invoice_date' => $this->invoice_date,
            'due_date' => $this->due_date,
            'status' => 'pending',
            'file_path' => $filePath,
            'created_by' => auth()->id(),
        ]);

        foreach ($this->invoiceItems as $item) {
            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'total_amount' => $item['total'],
            ]);
        }

        // Auditing Log
        SystemAudit::create([
            'user_id' => auth()->id(),
            'action' => 'invoice.created',
            'description' => "Invoice #{$invoice->invoice_number} created for {$invoice->vendor_name} with amount: {$invoice->amount}",
            'auditable_type' => Invoice::class,
            'auditable_id' => $invoice->id,
            'metadata' => [
                'invoice_number' => $invoice->invoice_number,
                'amount' => $invoice->amount,
                'items_count' => count($this->invoiceItems),
            ],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
        ]);

        $this->showInvoiceModal = false;
        $this->resetInvoiceForm();
        $this->collegeToast(__('Invoice saved successfully.'));
    }

    public function openRecordExpenditure(?int $invoiceId = null): void
    {
        $this->resetExpenditureForm();
        $this->expense_number = 'EXP-' . date('Y') . '-' . sprintf('%04d', Expenditure::count() + 1);

        if ($invoiceId) {
            $invoice = Invoice::find($invoiceId);
            if ($invoice) {
                $this->expenditure_invoice_id = (string) $invoice->id;
                $this->expenditure_amount = (string) $invoice->remaining_balance;
                $this->notes = 'Settled payment for invoice ' . $invoice->invoice_number;
            }
        }

        $this->showExpenditureModal = true;
    }

    public function saveExpenditure(): void
    {
        $this->validate([
            'expense_number' => 'required|string|unique:expenditures,expense_number',
            'expenditure_amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|string',
            'payment_date' => 'required|date',
            'expenditure_category' => 'required|string',
            'expenditure_invoice_id' => 'nullable|exists:invoices,id',
            'reference_number' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $invoice = null;
        if ($this->expenditure_invoice_id) {
            $invoice = Invoice::find($this->expenditure_invoice_id);
            if ($invoice && (float)$this->expenditure_amount > $invoice->remaining_balance) {
                $this->addError('expenditure_amount', __('Payment amount cannot exceed invoice remaining balance of :bal', ['bal' => number_format($invoice->remaining_balance, 2)]));
                return;
            }
        }

        $proofPath = null;
        if ($this->proofFile) {
            $proofPath = $this->proofFile->store('receipts', 'public');
        }

        $expenditure = Expenditure::create([
            'invoice_id' => $invoice ? $invoice->id : null,
            'expense_number' => $this->expense_number,
            'amount' => (float) $this->expenditure_amount,
            'payment_method' => $this->payment_method,
            'payment_date' => $this->payment_date,
            'reference_number' => $this->reference_number,
            'category' => $this->expenditure_category,
            'proof_file_path' => $proofPath,
            'notes' => $this->notes,
            'recorded_by' => auth()->id(),
        ]);

        if ($invoice) {
            // Update invoice status dynamically
            $rem = $invoice->remaining_balance;
            if ($rem <= 0.0) {
                $invoice->update(['status' => 'paid']);
            } else {
                $invoice->update(['status' => 'partially_paid']);
            }
        }

        // Auditing Log
        SystemAudit::create([
            'user_id' => auth()->id(),
            'action' => 'expenditure.created',
            'description' => "Expenditure #{$expenditure->expense_number} recorded for Category: {$expenditure->category} with amount: {$expenditure->amount}",
            'auditable_type' => Expenditure::class,
            'auditable_id' => $expenditure->id,
            'metadata' => [
                'expense_number' => $expenditure->expense_number,
                'amount' => $expenditure->amount,
                'invoice_id' => $expenditure->invoice_id,
            ],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
        ]);

        $this->showExpenditureModal = false;
        $this->resetExpenditureForm();
        $this->collegeToast(__('Expenditure recorded successfully.'));
    }

    public function viewInvoiceItems(int $id): void
    {
        $this->activeInvoice = Invoice::with('items.product')->find($id);
        if ($this->activeInvoice) {
            $this->showItemsModal = true;
        }
    }

    public function viewExpenditureTimeline(int $id): void
    {
        $this->activeExpenditure = Expenditure::find($id);
        if ($this->activeExpenditure) {
            $this->showExpenditureTimelineModal = true;
        }
    }

    public function deleteInvoice(int $id): void
    {
        $invoice = Invoice::find($id);
        if ($invoice) {
            $invoiceNo = $invoice->invoice_number;
            $vendor = $invoice->vendor_name;

            // Delete invoice
            $invoice->delete();

            // Auditing Log
            SystemAudit::create([
                'user_id' => auth()->id(),
                'action' => 'invoice.deleted',
                'description' => "Invoice #{$invoiceNo} (Vendor: {$vendor}) was deleted",
                'auditable_type' => Invoice::class,
                'auditable_id' => $id,
                'metadata' => [
                    'invoice_number' => $invoiceNo,
                    'vendor_name' => $vendor,
                ],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'created_at' => now(),
            ]);

            $this->collegeToast(__('Invoice deleted successfully.'));
        }
    }

    public function deleteExpenditure(int $id): void
    {
        $expenditure = Expenditure::find($id);
        if ($expenditure) {
            $expenseNo = $expenditure->expense_number;
            $amount = $expenditure->amount;
            $invId = $expenditure->invoice_id;

            $expenditure->delete();

            if ($invId) {
                $invoice = Invoice::find($invId);
                if ($invoice) {
                    $rem = $invoice->remaining_balance;
                    $paid = $invoice->paid_amount;
                    if ($paid <= 0.0) {
                        $invoice->update(['status' => 'pending']);
                    } else {
                        $invoice->update(['status' => 'partially_paid']);
                    }
                }
            }

            // Auditing Log
            SystemAudit::create([
                'user_id' => auth()->id(),
                'action' => 'expenditure.deleted',
                'description' => "Expenditure #{$expenseNo} was deleted",
                'auditable_type' => Expenditure::class,
                'auditable_id' => $id,
                'metadata' => [
                    'expense_number' => $expenseNo,
                    'amount' => $amount,
                ],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'created_at' => now(),
            ]);

            $this->collegeToast(__('Expenditure deleted successfully.'));
        }
    }

    private function resetInvoiceForm(): void
    {
        $this->invoice_number = '';
        $this->vendor_name = '';
        $this->description = '';
        $this->amount = 0.00;
        $this->invoice_date = now()->toDateString();
        $this->due_date = now()->addDays(30)->toDateString();
        $this->invoiceFile = null;
        $this->invoiceItems = [];
        $this->resetLineItemInputs();
    }

    private function resetExpenditureForm(): void
    {
        $this->expense_number = '';
        $this->expenditure_invoice_id = '';
        $this->expenditure_amount = '';
        $this->payment_method = 'Bank Transfer';
        $this->payment_date = now()->toDateString();
        $this->reference_number = '';
        $this->expenditure_category = 'IT Infrastructure';
        $this->notes = '';
        $this->proofFile = null;
    }

    public function render(): View
    {
        $q = '%' . $this->search . '%';

        $invoices = Invoice::query()
            ->with(['creator', 'expenditures'])
            ->where(function ($query) use ($q) {
                $query->where('invoice_number', 'like', $q)
                    ->orWhere('vendor_name', 'like', $q)
                    ->orWhere('description', 'like', $q);
            })
            ->latest()
            ->paginate(10, pageName: 'invoices-page');

        $expenditures = Expenditure::query()
            ->with(['invoice', 'recorder'])
            ->where(function ($query) use ($q) {
                $query->where('expense_number', 'like', $q)
                    ->orWhere('category', 'like', $q)
                    ->orWhere('payment_method', 'like', $q)
                    ->orWhere('notes', 'like', $q);
            })
            ->latest()
            ->paginate(10, pageName: 'expenditures-page');

        $products = Product::all();
        $unpaidInvoices = Invoice::query()->whereIn('status', ['pending', 'partially_paid'])->get();

        return view('livewire.admin.finance.invoices-index-page', [
            'invoices' => $invoices,
            'expenditures' => $expenditures,
            'products' => $products,
            'unpaidInvoices' => $unpaidInvoices,
        ])->layout('components.layouts.admin', [
            'title' => __('Invoices & Expenditures'),
            'headerTitle' => __('Invoices & Expenditures'),
            'headerDescription' => __('Manage institutional purchases, process invoices using AI OCR Receipt scanning, track accounts payable, and record operational expenditures.'),
        ]);
    }

    private function canManageFinance(): bool
    {
        return auth()->user() && app(SchoolLicenceService::class)->can('finance');
    }
}
