<?php

namespace App\Modules\Finance\Models;

use App\Core\Traits\HasUuid;
use App\Models\EarningLine;
use App\Models\User;
use App\Modules\Business\Models\Business;
use Database\Factories\FinanceInvoiceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinanceInvoice extends Model
{
    /** @use HasFactory<FinanceInvoiceFactory> */
    use HasFactory, HasUuid;

    protected $fillable = [
        'reference',
        'business_id',
        'earning_line_id',
        'current_account_id',
        'collection_id',
        'invoice_type',
        'invoice_status',
        'collection_status',
        'invoice_date',
        'due_date',
        'subtotal',
        'vat_rate',
        'vat_amount',
        'grand_total',
        'collected_amount',
        'source',
        'e_invoice_uuid',
        'e_archive_uuid',
        'gib_status',
        'pdf_filename',
        'description',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'vat_amount' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'collected_amount' => 'decimal:2',
            'invoice_date' => 'date',
            'due_date' => 'date',
        ];
    }

    /**
     * @return BelongsTo<Business, $this>
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * @return BelongsTo<EarningLine, $this>
     */
    public function earningLine(): BelongsTo
    {
        return $this->belongsTo(EarningLine::class);
    }

    /**
     * @return BelongsTo<CurrentAccount, $this>
     */
    public function currentAccount(): BelongsTo
    {
        return $this->belongsTo(CurrentAccount::class);
    }

    /**
     * @return BelongsTo<FinanceCollection, $this>
     */
    public function collection(): BelongsTo
    {
        return $this->belongsTo(FinanceCollection::class, 'collection_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    protected static function newFactory(): FinanceInvoiceFactory
    {
        return FinanceInvoiceFactory::new();
    }
}
