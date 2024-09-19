<?php

namespace Finxp\Flexcube\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MerchantAccountResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'merchant_id' => $this->merchant_id,
            'customer_ac_no' => $this->account_number,
            'iban_ac_no' => $this->iban_number,
            'account_desc' => $this->account_desc,
            'is_notification_active' => $this->is_notification_active,
        ];
    }
}
