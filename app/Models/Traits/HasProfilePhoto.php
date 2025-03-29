<?php

namespace App\Models\Traits;

use Illuminate\Support\Facades\Storage;

trait HasProfilePhoto
{
    /**
     * Get the URL to the user's profile photo.
     */
    public function getProfilePhotoUrlAttribute(): string
    {
        return $this->profile_photo_path
            ? Storage::url($this->profile_photo_path)
            : $this->defaultProfilePhotoUrl();
    }

    /**
     * Get the default profile photo URL if none is uploaded.
     */
    protected function defaultProfilePhotoUrl(): string
    {
        $name = urlencode($this->name);
        return "https://ui-avatars.com/api/?name={$name}&color=7F9CF5&background=EBF4FF";
    }
}
