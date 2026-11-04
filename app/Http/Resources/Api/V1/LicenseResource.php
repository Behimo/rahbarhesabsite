<?php

namespace App\Http\Resources\Api\V1;

use App\Models\SpotplayerLicense;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin SpotplayerLicense */
class LicenseResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'course_id' => $this->course_id,
            'status' => $this->status,
            'license_key' => $this->license_key,
            'spot_url' => $this->spot_url,
            'issued_at' => $this->issued_at?->toIso8601String(),
        ];
    }
}
