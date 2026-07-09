<?php

namespace App\Modules\Finance\Requests;

use App\Modules\Finance\Data\PaymentFormData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('dashboard.financial') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'recipient_type' => ['required', Rule::in(array_keys(PaymentFormData::recipientTypes()))],
            'recipient_id' => ['required', 'integer', 'min:1'],
            'earning_line_id' => ['nullable', 'integer', 'exists:earning_lines,id'],
            'payment_date' => ['required', 'date'],
            'total_amount' => ['required', 'numeric', 'gt:0'],
            'paid_amount' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['nullable', Rule::in(array_keys(PaymentFormData::paymentMethods()))],
            'payment_reference' => ['nullable', 'string', 'max:50'],
            'bank_account' => ['nullable', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $type = (string) $this->input('recipient_type');
            $id = (int) $this->input('recipient_id');

            if ($type === 'courier' && ! \App\Modules\Courier\Models\Courier::query()->whereKey($id)->exists()) {
                $validator->errors()->add('recipient_id', 'Seçilen kurye bulunamadı.');
            }

            if ($type === 'agency' && ! \App\Modules\Agency\Models\Agency::query()->whereKey($id)->exists()) {
                $validator->errors()->add('recipient_id', 'Seçilen acente bulunamadı.');
            }

            if (in_array($type, ['personnel', 'supplier'], true) && PaymentFormData::staticRecipientName($type, $id) === null) {
                $validator->errors()->add('recipient_id', 'Seçilen alıcı bulunamadı.');
            }

            $paidAmount = (float) ($this->input('paid_amount') ?? 0);
            $totalAmount = (float) $this->input('total_amount');

            if ($paidAmount > 0 && ! $this->filled('payment_method')) {
                $validator->errors()->add('payment_method', 'Ödeme yapılacaksa ödeme yöntemi zorunludur.');
            }

            if ($paidAmount > $totalAmount) {
                $validator->errors()->add('paid_amount', 'Ödenen tutar toplam tutardan büyük olamaz.');
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'recipient_type' => 'alıcı türü',
            'recipient_id' => 'alıcı',
            'earning_line_id' => 'hakediş kaydı',
            'payment_date' => 'ödeme tarihi',
            'total_amount' => 'toplam tutar',
            'paid_amount' => 'ödenen tutar',
            'payment_method' => 'ödeme yöntemi',
            'payment_reference' => 'referans no',
            'bank_account' => 'banka hesabı',
            'description' => 'açıklama',
        ];
    }
}
