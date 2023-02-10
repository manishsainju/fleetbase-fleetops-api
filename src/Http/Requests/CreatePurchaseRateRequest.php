<?php

namespace Fleetbase\Http\Requests;

use Fleetbase\Rules\ExistsInAny;

class CreatePurchaseRateRequest extends FleetbaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return request()->session()->has('api_credential');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'service_quote' => 'required|exists:service_quotes,public_id',
            'order' => 'nullable|exists:orders,public_id',
            'customer' => ['nullable', new ExistsInAny(['vendors', 'contacts'], 'public_id')],
        ];
    }
}
