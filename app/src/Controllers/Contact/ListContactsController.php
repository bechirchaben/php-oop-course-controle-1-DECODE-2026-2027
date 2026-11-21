<?php

namespace App\Controllers\Contact;

use App\Entities\Contact;
use App\lib\Controllers\AbstractJsonController;
use App\lib\Http\Request;
use App\lib\Http\Response;
use App\Repositories\ContactRepository;

/**
 * GET /contact
 */
class ListContactsController extends AbstractJsonController {

    protected function handle(Request $request): Response
    {
        $contacts = (new ContactRepository())->findAll();

        return Response::json(
            array_values(array_map(static fn (Contact $contact): array => $contact->toArray(), $contacts)),
            200
        );
    }
}
