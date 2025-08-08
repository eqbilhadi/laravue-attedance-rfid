<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RfidResource extends JsonResource
{   
    public bool $status;
    public string $message;
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
    public function __construct(bool $status, string $message, $resource)
    {
        parent::__construct($resource);
        $this->status  = $status;
        $this->message = $message;
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
            'user' => $this->resource
        ];
    }
}
