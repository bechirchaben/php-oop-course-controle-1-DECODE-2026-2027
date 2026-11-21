<?php

namespace App\Controllers\Contact;

use App\Entities\Contact;
use App\Exceptions\InvalidContactDataException;
use App\lib\Controllers\AbstractJsonController;
use App\lib\Http\HttpException;
use App\lib\Http\Request;
use App\Repositories\ContactRepository;

/**
 * Base controller for the routes targeting one contact form: /contact/{filename}
 */
abstract class AbstractSingleContactController extends AbstractJsonController {
    protected readonly ContactRepository $repository;

    public function __construct()
    {
        $this->repository = new ContactRepository();
    }

    /**
     * @throws HttpException 404 when the file name is missing or does not follow the "<timestamp>_<email>.json" template
     */
    protected function getFilename(Request $request): string
    {
        $filename = $request->getPathParam('filename');

        if ($filename === null || ContactRepository::isValidFilename($filename) === false) {
            throw new HttpException(404, 'contact form not found');
        }

        return $filename;
    }

    /**
     * @throws HttpException 404 when there is no such contact form, 500 when the stored file is corrupted
     */
    protected function findContactOrFail(string $filename): Contact
    {
        try {
            $contact = $this->repository->find($filename);
        } catch (InvalidContactDataException $e) {
            error_log('Corrupted contact file ' . $filename . ': ' . json_encode($e->getErrors()));

            throw new HttpException(500, 'the stored contact form is corrupted', [], $e);
        }

        if ($contact === null) {
            throw new HttpException(404, 'contact form not found');
        }

        return $contact;
    }
}
