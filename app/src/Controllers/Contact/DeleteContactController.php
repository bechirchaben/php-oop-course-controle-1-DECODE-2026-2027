<?php

namespace App\Controllers\Contact;

use App\lib\Http\HttpException;
use App\lib\Http\Request;
use App\lib\Http\Response;

/**
 * DELETE /contact/{filename}
 *
 * The file content is not read, so a corrupted contact file can still be deleted.
 */
class DeleteContactController extends AbstractSingleContactController {

    protected function handle(Request $request): Response
    {
        if ($this->repository->delete($this->getFilename($request)) === false) {
            throw new HttpException(404, 'contact form not found');
        }

        return Response::noContent();
    }
}
