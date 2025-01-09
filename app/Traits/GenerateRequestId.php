<?php

namespace App\Traits;

use Illuminate\Support\Str;

trait GenerateRequestId
{
    /**
     * Generate request ID
     *
     * @return string
     */
    public function generateRequestId()
    {
        return "REQ-" . Str::uuid()->toString();
    }
}
