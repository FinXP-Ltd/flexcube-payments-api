<?php

namespace Finxp\Flexcube\Tests\Mocks\Models\ModelFilters;

use EloquentFilter\ModelFilter;

class BeneficiaryFilter extends ModelFilter
{
    public function beneficiaryId($beneficiaryId)
    {
        return $this->where('uuid', $beneficiaryId);
    }

    public function name($name)
    {
        return $this->where('name', 'LIKE',  "%{$name}%");
    }

    public function iban($iban)
    {
        return $this->where('iban', 'LIKE',  "%{$iban}%");
    }

    public function bic($bic)
    {
        return $this->where('bic', 'LIKE',  "%{$bic}%");
    }

    public function isActive($isActive)
    {
        return $this->where('is_active', $isActive);
    }

    public function dateCreated(string $dateCreated)
    {
        return $this->whereDate('created_at', $dateCreated);
    }
}
