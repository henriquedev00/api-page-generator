<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function withResponse($request, $response)
    {
        $response->setEncodingOptions(JSON_UNESCAPED_SLASHES);
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = [
            'header' => json_decode($this->header, true),
            'details' => json_decode($this->details, true),
            'footer' => json_decode($this->footer, true),
            'images' => json_decode($this->images, true),
        ];

        foreach($data['images'] as $section => $images) {
            foreach($images as $field => $image) {
                $data['images'][$section][$field] = Storage::url($image);
            }
        }

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'header' => $data['header'],
            'details' => $data['details'],
            'footer' => $data['footer'],
            'images' => $data['images']
        ];
    }
}
