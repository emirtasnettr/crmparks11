<?php

namespace App\Modules\Business\Services;

use App\Core\Helpers\MoneyCalculator;
use App\Models\EarningLine;
use App\Models\Contract;
use App\Models\Document;
use App\Modules\Business\Data\BusinessCommercialContractFormData;
use App\Modules\Business\Data\BusinessFormData;
use App\Modules\Business\Models\Business;
use App\Modules\Business\Models\BusinessContact;
use App\Modules\Business\Support\BusinessFeatures;
use App\Modules\Business\Support\BusinessLogo;

class BusinessPresenter
{
  public function __construct(
    private readonly BusinessMediaService $media,
    private readonly BusinessContactService $contacts,
    private readonly BusinessContactPresenter $contactPresenter,
    private readonly BusinessContractService $contracts,
    private readonly BusinessContractPresenter $contractPresenter,
    private readonly BusinessCommercialContractService $commercialContracts,
    private readonly BusinessCommercialContractPresenter $commercialContractPresenter,
    private readonly BusinessDocumentService $documents,
    private readonly BusinessDocumentPresenter $documentPresenter,
    private readonly BusinessEarningService $earnings,
    private readonly BusinessEarningPresenter $earningPresenter,
    private readonly BusinessActivityService $activities,
    private readonly BusinessActivityPresenter $activityPresenter,
  ) {}

  /**
   * @return array<string, mixed>
   */
  public function toBaseArray(Business $business): array
  {
    $business->loadMissing(['city', 'district', 'activeCommercialContract']);
    $logo = BusinessLogo::initials($business);
    $workType = $this->workTypeCode($business);
    $unitPrices = $this->unitPrices($business);

    return array_merge([
      'id' => $business->id,
      'company_name' => $business->company_name,
      'brand_name' => $business->brand_name,
      'display_name' => $business->displayName(),
      'phone' => $business->phone,
      'email' => $business->email,
      'website' => $business->website,
      'tax_office' => $business->tax_office,
      'tax_number' => $business->tax_number,
      'city' => $business->city?->name ?? '',
      'district' => $business->district?->name ?? '',
      'address' => $business->address,
      'status' => $business->status,
      'contract_end_date' => $business->contract_end_date?->toDateString(),
      'estimated_opening_date' => $business->estimated_opening_date?->toDateString(),
      'start_date' => $business->start_date?->toDateString(),
      'notes' => $business->notes,
      'earning_period' => $business->earning_period,
      'first_invoice_date' => $business->first_invoice_date?->toDateString(),
      'planned_courier_count' => (int) ($business->planned_courier_count ?? 0),
      'work_type' => $workType,
      'pricing_model' => $workType ?? '',
      'has_active_contract' => $workType !== null,
      'active_couriers' => $business->activeCourierCount(),
      'logo_path' => $business->logo_path,
      'logo_url' => $this->media->url($business->logo_path),
      'has_logo_image' => ! empty($business->logo_path),
      'can_delete' => auth()->user()?->hasRole('super_admin') ?? false,
      'customer_unit' => $unitPrices['revenue_unit'],
      'courier_unit' => $unitPrices['courier_unit'],
      'guaranteed_hourly_package_fee' => $unitPrices['guaranteed_hourly_package_fee'],
    ], $logo);
  }

  /**
   * @return array<string, mixed>
   */
  public function indexRow(Business $business): array
  {
    $base = $this->toBaseArray($business);
    $workType = $base['work_type'];

    return array_merge($base, [
      'customer_price_label' => $workType
        ? $this->formatStoredPrice((float) $base['customer_unit'], $workType)
        : '—',
      'courier_price_label' => $workType
        ? $this->formatStoredPrice((float) $base['courier_unit'], $workType)
        : '—',
      'work_type_label' => $workType
        ? (BusinessCommercialContractFormData::workTypes()[$workType] ?? $workType)
        : '—',
    ]);
  }

  /**
   * @return array<string, mixed>
   */
  public function detailPayload(Business $business): array
  {
    $base = $this->toBaseArray($business);
    $workTypes = BusinessCommercialContractFormData::workTypes();
    $statusLabels = BusinessFormData::statuses();
    $id = $business->id;

    return array_merge([
      'id' => $id,
      'logo' => $base['logo'],
      'logo_color' => $base['logo_color'],
      'logo_url' => $base['logo_url'],
      'has_logo_image' => $base['has_logo_image'],
      'company_name' => $base['company_name'],
      'brand_name' => $base['brand_name'],
      'display_name' => $base['display_name'] ?? $base['brand_name'] ?? $base['company_name'],
      'phone' => $base['phone'],
      'location' => trim($base['city'].' / '.$base['district'], ' /'),
      'work_type' => $base['work_type'],
      'work_type_label' => $base['work_type']
        ? ($workTypes[$base['work_type']] ?? $base['work_type'])
        : '—',
      'pricing_model_label' => $base['work_type']
        ? ($workTypes[$base['work_type']] ?? $base['work_type'])
        : '—',
      'active_couriers' => $base['active_couriers'],
      'planned_courier_count' => $base['planned_courier_count'],
      'status' => $base['status'],
      'status_label' => $statusLabels[$base['status']] ?? $base['status'],
      'can_delete' => $base['can_delete'],
      'contacts_url' => route('businesses.contacts.index', ['business_id' => $id]),
      'contracts_url' => route('businesses.contracts.index', ['business_id' => $id]),
      'documents_url' => route('businesses.documents.index', ['business_id' => $id]),
      'activities_url' => route('businesses.activities.index', ['business_id' => $id]),
    ], BusinessFeatures::earningsEnabled() ? [
      'earnings_url' => route('businesses.earnings.index', ['business_id' => $id]),
    ] : []);
  }

  /**
   * @return array<string, mixed>
   */
  public function showPayload(Business $business): array
  {
    $base = $this->toBaseArray($business);
    $workType = $base['work_type'];
    $earningPeriods = BusinessFormData::earningPeriods();
    $activeContract = $this->commercialContracts->activeForBusiness($business->id);

    return array_merge($this->detailPayload($business), [
      'uuid' => $business->uuid,
      'public_id' => $business->public_id,
      'email' => $base['email'],
      'website' => $base['website'],
      'tax_office' => $base['tax_office'],
      'tax_number' => $base['tax_number'],
      'address' => $base['address'],
      'customer_price' => $workType
        ? $this->formatStoredPrice((float) $base['customer_unit'], $workType)
        : '—',
      'courier_price' => $workType
        ? $this->formatStoredPrice((float) $base['courier_unit'], $workType)
        : '—',
      'pricing_model' => $workType ?? '',
      'work_type' => $workType,
      'has_active_contract' => $activeContract !== null,
      'guaranteed_hourly_package_fee' => $base['guaranteed_hourly_package_fee'],
      'guaranteed_hourly_package_fee_formatted' => $base['guaranteed_hourly_package_fee'] !== null
        ? MoneyCalculator::formatVatAmount((float) $base['guaranteed_hourly_package_fee'])
        : '—',
      'notes' => $base['notes'],
      'contract_end_date_formatted' => $business->contract_end_date?->format('d.m.Y'),
      'estimated_opening_date_formatted' => $business->estimated_opening_date?->format('d.m.Y'),
      'start_date_formatted' => $business->start_date?->format('d.m.Y'),
      'created_at_formatted' => $business->created_at?->format('d.m.Y') ?? now()->format('d.m.Y'),
      'contacts' => $this->contacts
        ->forBusiness($business->id)
        ->map(fn (BusinessContact $contact) => $this->contactPresenter->showRow($contact))
        ->values()
        ->all(),
      'contracts' => $this->contracts
        ->forBusiness($business->id)
        ->map(fn (Contract $contract) => $this->contractPresenter->showRow($contract))
        ->values()
        ->all(),
      'commercial_contracts' => $this->commercialContracts
        ->forBusiness($business->id)
        ->map(fn ($contract) => $this->commercialContractPresenter->indexRow($contract))
        ->values()
        ->all(),
      'active_commercial_contract' => $activeContract
        ? $this->commercialContractPresenter->indexRow($activeContract)
        : null,
      'documents' => $this->documents
        ->forBusiness($business->id)
        ->map(fn (Document $document) => $this->documentPresenter->indexRow($document))
        ->values()
        ->all(),
      'activities' => $this->activities
        ->forBusiness($business->id)
        ->map(fn ($log) => $this->activityPresenter->indexRow($log))
        ->values()
        ->all(),
    ], BusinessFeatures::earningsEnabled() ? [
      'earning_period_label' => $earningPeriods[$base['earning_period'] ?? ''] ?? '—',
      'first_invoice_date' => $base['first_invoice_date'] ?? null,
      'first_invoice_date_formatted' => ! empty($base['first_invoice_date'])
        ? \Carbon\Carbon::parse($base['first_invoice_date'])->format('d.m.Y')
        : '—',
      'earnings' => $this->earnings
        ->forBusiness($business->id)
        ->map(fn (EarningLine $line) => $this->earningPresenter->showRow($line))
        ->values()
        ->all(),
    ] : []);
  }

  /**
   * @return array<string, mixed>
   */
  public function formPayload(Business $business): array
  {
    $base = $this->toBaseArray($business);

    return [
      'company_name' => $base['company_name'],
      'brand_name' => $base['brand_name'],
      'phone' => $base['phone'],
      'email' => $base['email'],
      'website' => $base['website'],
      'tax_office' => $base['tax_office'],
      'tax_number' => $base['tax_number'],
      'city' => $base['city'],
      'district' => $base['district'],
      'address' => $base['address'],
      'earning_period' => $base['earning_period'] ?? 'weekly',
      'first_invoice_date' => ! empty($base['first_invoice_date'])
        ? $base['first_invoice_date']
        : BusinessFormData::defaultFirstInvoiceDate(),
      'planned_courier_count' => $base['planned_courier_count'] > 0 ? (string) $base['planned_courier_count'] : '',
      'status' => $base['status'],
      'contract_end_date' => $base['contract_end_date'] ?? '',
      'estimated_opening_date' => $base['estimated_opening_date'] ?? '',
      'start_date' => $base['start_date'] ?? '',
      'notes' => $base['notes'],
      'logo_url' => $base['logo_url'],
    ];
  }

  /**
   * @return array{revenue_unit: float, courier_unit: float, guaranteed_hourly_package_fee: float|null, from_profile: bool}
   */
  public function unitPrices(Business $business): array
  {
    $contract = $this->commercialContracts->activeForBusiness($business->id);
    if ($contract !== null) {
      return [
        'revenue_unit' => (float) $contract->business_amount,
        'courier_unit' => (float) $contract->courier_amount,
        'guaranteed_hourly_package_fee' => $contract->guaranteed_hourly_package_fee !== null
          ? (float) $contract->guaranteed_hourly_package_fee
          : null,
        'from_profile' => true,
      ];
    }

    return [
      'revenue_unit' => 0.0,
      'courier_unit' => 0.0,
      'guaranteed_hourly_package_fee' => null,
      'from_profile' => false,
    ];
  }

  public function formatStoredPrice(float $amount, string $workType): string
  {
    $formatted = MoneyCalculator::formatVatAmount($amount);

    return match ($workType) {
      'hourly' => $formatted.' / saat',
      default => $formatted,
    };
  }

  private function workTypeCode(Business $business): ?string
  {
    $contract = $this->commercialContracts->activeForBusiness($business->id);

    return $contract?->work_type;
  }
}
