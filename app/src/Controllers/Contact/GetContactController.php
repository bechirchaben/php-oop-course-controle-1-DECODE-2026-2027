<?php

namespace App\Controllers\Contact;

use App\lib\Http\Request;
use App\lib\Http\Response;

/**
 * GET /contact/{filename}
 */
class GetContactController extends AbstractSingleContactController {

    protected function handle(Request $request): Response
    {
        $contact = $this->findContactOrFail($this->getFilename($request));

        return Response::json($contact->toArray(), 200);
    }
}
