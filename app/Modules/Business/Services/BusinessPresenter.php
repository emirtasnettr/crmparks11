<?php

namespace App\Modules\Business\Services;

use App\Core\Helpers\MoneyCalculator;
use App\Models\City;
use App\Models\District;
use App\Models\PricingModelType;
use App\Models\User;
use App\Modules\Business\Data\BusinessActivityDummyData;
use App\Modules\Business\Data\BusinessAssignmentDummyData;
use App\Modules\Business\Data\BusinessContactDummyData;
use App\Modules\Business\Data\BusinessContractDummyData;
use App\Modules\Business\Data\BusinessDocumentDummyData;
use App\Modules\Business\Data\BusinessEarningDummyData;
use App\Modules\Business\Data\BusinessFormData;
use App\Modules\Business\Models\Business;
use App\Modules\Business\Models\BusinessPricing;
use App\Modules\Business\Support\BusinessFeatures;
use App\Modules\Business\Support\BusinessLogo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BusinessPresenter
{
  public function __construct(
    private readonly BusinessMediaService $media,
  ) {}

  /**
   * @return array<string, mixed>
   */
  public function toBaseArray(Business $business): array
  {
    $business->loadMissing(['city', 'district', 'activePricing.pricingModelType']);
    $logo = BusinessLogo::initials($business);
    $pricingCode = $this->pricingModelCode($business);

    return array_merge([
      'id' => $business->id,
      'company_name' => $business->company_name,
      'brand_name' => $business->brand_name,
      'phone' => $business->phone,
      'email' => $business->email,
      'website' => $business->website,
      'tax_office' => $business->tax_office,
      'tax_number' => $business->tax_number,
      'city' => $business->city?->name ?? '',
      'district' => $business->district?->name ?? '',
      'address' => $business->address,
      'status' => $business->status,
      'notes' => $business->notes,
      'earning_period' => $business->earning_period,
      'pricing_model' => $this->normalizePricingModelForList($pricingCode),
      'active_couriers' => $business->activeCourierCount(),
      'logo_path' => $business->logo_path,
      'logo_url' => $this->media->url($business->logo_path),
      'has_logo_image' => ! empty($business->logo_path),
    ], $logo);
  }

  /**
   * @return array<string, mixed>
   */
  public function indexRow(Business $business): array
  {
    $base = $this->toBaseArray($business);
    $pricingModel = $base['pricing_model'] ?? 'per_package';
    $unitPrices = $this->unitPrices($business);

    return array_merge($base, [
      'customer_price_label' => $this->formatStoredPrice($unitPrices['revenue_unit'], $pricingModel),
      'courier_price_label' => $this->formatStoredPrice($unitPrices['courier_unit'], $pricingModel),
    ]);
  }

  /**
   * @return array<string, mixed>
   */
  public function detailPayload(Business $business): array
  {
    $base = $this->toBaseArray($business);
    $pricingLabels = array_merge(BusinessFormData::pricingModels(), [
      'fixed' => 'Sabit Ücret',
      'monthly_fixed' => 'Aylık Sabit',
    ]);
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
      'phone' => $base['phone'],
      'location' => trim($base['city'].' / '.$base['district'], ' /'),
      'pricing_model_label' => $pricingLabels[$base['pricing_model']] ?? $base['pricing_model'],
      'active_couriers' => $base['active_couriers'],
      'status' => $base['status'],
      'status_label' => $statusLabels[$base['status']] ?? $base['status'],
      'contacts_url' => route('businesses.contacts.index', ['business_id' => $id]),
      'contracts_url' => route('businesses.contracts.index', ['business_id' => $id]),
      'assignments_url' => route('businesses.assignments.index', ['business_id' => $id]),
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
    $pricingModel = $base['pricing_model'] ?? 'per_package';
    $unitPrices = $this->unitPrices($business);
    $earningPeriods = BusinessFormData::earningPeriods();

    return array_merge($this->detailPayload($business), [
      'uuid' => $business->uuid,
      'email' => $base['email'],
      'website' => $base['website'],
      'tax_office' => $base['tax_office'],
      'tax_number' => $base['tax_number'],
      'address' => $base['address'],
      'customer_price' => $this->formatStoredPrice($unitPrices['revenue_unit'], $pricingModel),
      'courier_price' => $this->formatStoredPrice($unitPrices['courier_unit'], $pricingModel),
      'notes' => $base['notes'],
      'created_at_formatted' => $business->created_at?->format('d.m.Y') ?? now()->format('d.m.Y'),
      'contacts' => BusinessContactDummyData::filter(['business_id' => $business->id]),
      'contracts' => BusinessContractDummyData::filter(['business_id' => $business->id]),
      'assignments' => BusinessAssignmentDummyData::filter(['business_id' => $business->id]),
      'documents' => BusinessDocumentDummyData::filter(['business_id' => $business->id]),
      'activities' => BusinessActivityDummyData::filter(['business_id' => $business->id]),
    ], BusinessFeatures::earningsEnabled() ? [
      'earning_period_label' => $earningPeriods[$base['earning_period'] ?? ''] ?? '—',
      'earnings' => BusinessEarningDummyData::filter(['business_id' => $business->id]),
    ] : []);
  }

  /**
   * @return array<string, mixed>
   */
  public function formPayload(Business $business): array
  {
    $base = $this->toBaseArray($business);
    $unitPrices = $this->unitPrices($business);
    $pricingCode = $this->pricingModelCode($business);

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
      'pricing_model' => $pricingCode === 'monthly_fixed' ? 'monthly_fixed' : $pricingCode,
      'customer_price' => number_format($unitPrices['revenue_unit'], 2, '.', ''),
      'courier_price' => number_format($unitPrices['courier_unit'], 2, '.', ''),
      'earning_period' => $base['earning_period'] ?? 'weekly',
      'status' => $base['status'],
      'notes' => $base['notes'],
      'logo_url' => $base['logo_url'],
    ];
  }

  /**
   * @return array{revenue_unit: float, courier_unit: float, from_profile: bool}
   */
  public function unitPrices(Business $business): array
  {
    $business->loadMissing('activePricing');

    if ($business->activePricing !== null) {
      return [
        'revenue_unit' => (float) $business->activePricing->customer_unit_price,
        'courier_unit' => (float) $business->activePricing->courier_unit_price,
        'from_profile' => true,
      ];
    }

    return [
      'revenue_unit' => 0.0,
      'courier_unit' => 0.0,
      'from_profile' => false,
    ];
  }

  public function formatStoredPrice(float $amount, string $pricingModel): string
  {
    $formatted = MoneyCalculator::formatVatAmount($amount);

    return match ($pricingModel) {
      'hourly' => $formatted.' / saat',
      'daily' => $formatted.' / gün',
      default => $formatted,
    };
  }

  private function pricingModelCode(Business $business): string
  {
    $business->loadMissing('activePricing.pricingModelType');

    return $business->activePricing?->pricingModelType?->code ?? 'per_package';
  }

  private function normalizePricingModelForList(string $code): string
  {
    return $code === 'monthly_fixed' ? 'fixed' : $code;
  }
}
