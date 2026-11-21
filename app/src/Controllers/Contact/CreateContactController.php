<?php

namespace App\Controllers\Contact;

use App\Entities\Contact;
use App\Exceptions\ContactAlreadyExistsException;
use App\lib\Controllers\AbstractJsonController;
use App\lib\Http\HttpException;
use App\lib\Http\Request;
use App\lib\Http\Response;
use App\Repositories\ContactRepository;
use App\Validators\ContactValidator;

/**
 * POST /contact
 */
class CreateContactController extends AbstractJsonController {

    protected function expectsJsonBody(): bool
    {
        return true;
    }

    protected function handle(Request $request): Response
    {
        $data = $this->decodeJsonBody($request);

        $errors = (new ContactValidator())->validateForCreation($data);

        if ($errors !== []) {
            throw new HttpException(400, 'invalid contact form', $errors);
        }

        $contact = Contact::create($data['email'], $data['subject'], $data['message'], time());

        try {
            $filename = (new ContactRepository())->add($contact);
        } catch (ContactAlreadyExistsException $e) {
            throw new HttpException(409, 'a contact form was already sent with this email in the same second, please retry', [], $e);
        }

        $response = Response::json(['file' => $filename], 201);
        $response->headers['Location'] = '/contact/' . rawurlencode($filename);

        return $response;
    }
}
