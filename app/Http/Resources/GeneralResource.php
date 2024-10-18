<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GeneralResource extends JsonResource
{
     //define properti
     public $status;
     public $message;
     public $resource;
     public $statusCode;
 
     /**
      * __construct
      *
      * @param  mixed $status
      * @param  mixed $message
      * @param  mixed $resource
      * @return void
      */
     public function __construct($status, $message, $resource, $statusCode)
     {
         parent::__construct($resource);
         $this->status  = $status;
         $this->message = $message;
         $this->statusCode = $statusCode;
     }
 
     /**
      * toArray
      *
      * @param  mixed $request
      * @return array
      */
     public function toArray(Request $request): array
     {
         return [
             'success'      => $this->status,
             'message'      => $this->message,
             'data'         => $this->resource,
             'status_code'  => $this->statusCode
         ];
     }
}
