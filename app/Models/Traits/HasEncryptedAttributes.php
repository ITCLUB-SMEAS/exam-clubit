<?php

namespace App\Models\Traits;

use App\Services\EncryptionService;

trait HasEncryptedAttributes
{
    /**
     * Get encrypted attribute
     */
    public function getAttribute($key)
    {
        $value = parent::getAttribute($key);
        
        if (in_array($key, $this->encrypted ?? [])) {
            return EncryptionService::decrypt($value);
        }
        
        return $value;
    }

    /**
     * Set encrypted attribute
     */
    public function setAttribute($key, $value)
    {
        if (in_array($key, $this->encrypted ?? [])) {
            $value = EncryptionService::encrypt($value);
        }
        
        return parent::setAttribute($key, $value);
    }
}
