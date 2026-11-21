<?php

namespace App\Repositories;

use App\Entities\Contact;
use App\Exceptions\ContactAlreadyExistsException;
use App\Exceptions\StorageException;

/**
 * Stores each contact form as a JSON file named "<timestamp>_<email>.json" in app/var/contacts.
 */
final class ContactRepository {
    private const string STORAGE_DIRECTORY = __DIR__ . '/../../var/contacts';

    private const int JSON_FLAGS = JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;

    /**
     * Saves a new contact form and returns the name of the created file.
     *
     * @throws ContactAlreadyExistsException when a contact with the same email was already created during the same second
     * @throws StorageException
     */
    public function add(Contact $contact): string
    {
        $this->ensureStorageDirectoryExists();

        $filename = $contact->getDateOfCreation() . '_' . $contact->getEmail() . '.json';
        $path = $this->getPath($filename);

        // "x" mode fails if the file already exists, so an existing contact is never overwritten.
        $handle = @fopen($path, 'x');

        if ($handle === false) {
            if (file_exists($path)) {
                throw new ContactAlreadyExistsException('a contact form with this name already exists: ' . $filename);
            }

            throw new StorageException('unable to create the contact file ' . $filename);
        }

        try {
            $written = fwrite($handle, json_encode($contact->toArray(), self::JSON_FLAGS));
        } finally {
            fclose($handle);
        }

        if ($written === false) {
            @unlink($path);

            throw new StorageException('unable to write the contact file ' . $filename);
        }

        return $filename;
    }

    private function ensureStorageDirectoryExists(): void
    {
        if (is_dir(self::STORAGE_DIRECTORY) === false
            && @mkdir(self::STORAGE_DIRECTORY, 0775, true) === false
            && is_dir(self::STORAGE_DIRECTORY) === false) {
            throw new StorageException('unable to create the contacts storage directory');
        }
    }

    private function getPath(string $filename): string
    {
        return self::STORAGE_DIRECTORY . '/' . $filename;
    }
}
