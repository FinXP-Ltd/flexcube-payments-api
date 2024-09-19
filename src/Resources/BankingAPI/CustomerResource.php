<?php

namespace Finxp\Flexcube\Resources\BankingAPI;

use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'private_customer'  => $this[ 'private_customer' ],
            'customer_no'       => $this[ 'customer_no' ],
            'ctype'             => $this[ 'ctype' ],
            'name'              => $this[ 'name' ],
            'addrln1'           => $this[ 'addrln1' ],
            'addrln2'           => $this[ 'addrln2' ],
            'addrln4'           => $this[ 'addrln4' ],
            'country'           => $this[ 'country' ],
            'sname'             => $this[ 'sname' ],
            'lbrn'              => $this[ 'lbrn' ],
            'category'          => $this[ 'category' ],
            'full_name'         => $this[ 'full_name' ],
            'registration_date' => $this[ 'registration_date' ],
            'customer_personal' => [
                'mobile_no'       => $this[ 'customer_personal'][ 'mobile_no' ],
                'mobile_isd_code' => $this[ 'customer_personal'][ 'mobile_isd_code' ]
            ],
            'customer_corporate'  => [
                'corp_name'       => $this[ 'customer_corporate' ][ 'corp_name' ],
                'reg_add1'        => $this[ 'customer_corporate' ][ 'reg_add1' ],
                'reg_add2'        => $this[ 'customer_corporate' ][ 'reg_add2' ],
                'mobile_no'       => $this[ 'customer_corporate' ][ 'mobile_no' ],
                'mobile_isd_code' => $this[ 'customer_corporate' ][ 'mobile_isd_code' ],
                'r_pin_code'      => $this[ 'customer_corporate' ][ 'r_pin_code' ]
            ]
        ];
    }
}