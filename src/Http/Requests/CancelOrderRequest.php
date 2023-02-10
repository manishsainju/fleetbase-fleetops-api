<?php

namespace Fleetbase\Http\Requests;

use Fleetbase\Support\Utils;

class CancelOrderRequest extends FleetbaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return Utils::notEmpty(session('user'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'order' => 'required|exists:orders,uuid',
        ];
    }
}
