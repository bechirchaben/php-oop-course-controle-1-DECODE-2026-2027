<?php

namespace App\Controllers\Contact;

use App\lib\Http\HttpException;
use App\lib\Http\Request;
use App\lib\Http\Response;
use App\Validators\ContactValidator;

/**
 * PATCH /contact/{filename}
 */
class UpdateContactController extends AbstractSingleContactController {

    protected function expectsJsonBody(): bool
    {
        return true;
    }

    protected function handle(Request $request): Response
    {
        $filename = $this->getFilename($request);
        $contact = $this->findContactOrFail($filename);

        $changes = $this->decodeJsonBody($request);
        $errors = (new ContactValidator())->validateForUpdate($changes);

        if ($errors !== []) {
            throw new HttpException(400, 'invalid contact form update', $errors);
        }

        $contact->update($changes, time());
        $this->repository->update($filename, $contact);

        return Response::json($contact->toArray(), 200);
    }
}
