<?php

namespace App\Controllers;

use App\lib\Http\Request;
use App\lib\Http\Response;
use App\lib\Controllers\AbstractController;

class HomeController extends AbstractController {
    public function process(Request $request): Response {
        $response = new Response();
        $response->statusCode = 200;
        $response->content = 'Success';

        return $response;
    }
}
