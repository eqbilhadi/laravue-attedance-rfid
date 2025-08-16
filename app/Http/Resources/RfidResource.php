<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RfidResource extends JsonResource
{   
    public bool $status;
    public string $message;
    public string $title;
    public $resource;

    /**
     * Method __construct
     *
     * @param bool $status [explicite description]
     * @param string $message [explicite description]
     * @param $resource $resource [explicite description]
     *
     * @return void
     */
    public function __construct(bool $status, string $message, string $title, $resource)
    {
        parent::__construct($resource);
        $this->status  = $status;
        $this->message = $message;
        $this->title = $title;
    }
    
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'registered' => $this->status,
            'message' => $this->message,
            'title' => $this->title,
            'user' => $this->resource
        ];
    }
}
