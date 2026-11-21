<?php

namespace App\Repositories;

use App\Entities\Contact;
use App\Exceptions\ContactAlreadyExistsException;
use App\Exceptions\InvalidContactDataException;
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

    /**
     * Overwrites an existing contact file. The file name is kept, so the contact uri stays the same.
     * The content is written to a temporary file then renamed, so a reader never sees a half-written file.
     *
     * @throws StorageException
     */
    public function update(string $filename, Contact $contact): void
    {
        if (self::isValidFilename($filename) === false || is_file($this->getPath($filename)) === false) {
            throw new StorageException('the contact file ' . $filename . ' does not exist');
        }

        $temporaryPath = $this->getPath($filename) . '.' . bin2hex(random_bytes(6)) . '.tmp';

        if (@file_put_contents($temporaryPath, json_encode($contact->toArray(), self::JSON_FLAGS), LOCK_EX) === false) {
            @unlink($temporaryPath);

            throw new StorageException('unable to write the contact file ' . $filename);
        }

        if (@rename($temporaryPath, $this->getPath($filename)) === false) {
            @unlink($temporaryPath);

            throw new StorageException('unable to replace the contact file ' . $filename);
        }
    }

    /**
     * Deletes a contact file. Returns false if there is no such file.
     *
     * @throws StorageException when the file exists but cannot be deleted
     */
    public function delete(string $filename): bool
    {
        $path = $this->getPath($filename);

        if (self::isValidFilename($filename) === false || is_file($path) === false) {
            return false;
        }

        if (@unlink($path) === false) {
            throw new StorageException('unable to delete the contact file ' . $filename);
        }

        return true;
    }

    /**
     * Returns every valid stored contact form, oldest first, indexed by file name.
     * Files that cannot be read or do not contain a valid contact form are skipped and logged.
     *
     * @return array<string, Contact>
     * @throws StorageException
     */
    public function findAll(): array
    {
        if (is_dir(self::STORAGE_DIRECTORY) === false) {
            return [];
        }

        $paths = glob(self::STORAGE_DIRECTORY . '/*.json');

        if ($paths === false) {
            throw new StorageException('unable to list the contact files');
        }

        $filenames = array_map('basename', $paths);
        natsort($filenames);

        $contacts = [];

        foreach ($filenames as $filename) {
            if (self::isValidFilename($filename) === false) {
                continue;
            }

            try {
                $contacts[$filename] = $this->read($filename);
            } catch (InvalidContactDataException | StorageException $e) {
                error_log('Skipping contact file ' . $filename . ': ' . $e->getMessage() . ' ' . json_encode(
                    $e instanceof InvalidContactDataException ? $e->getErrors() : []
                ));
            }
        }

        return $contacts;
    }

    /**
     * Returns the contact form stored in the given file, or null if there is no such file.
     *
     * @throws StorageException when the file exists but cannot be read
     * @throws InvalidContactDataException when the file does not contain a valid contact form
     */
    public function find(string $filename): ?Contact
    {
        if (self::isValidFilename($filename) === false || is_file($this->getPath($filename)) === false) {
            return null;
        }

        return $this->read($filename);
    }

    /**
     * Checks that a file name follows the "<timestamp>_<email>.json" template,
     * which also guarantees it cannot be used to reach a file outside the storage directory.
     */
    public static function isValidFilename(string $filename): bool
    {
        return preg_match('/^\d{1,20}_[A-Za-z0-9._%+-]+@[A-Za-z0-9-]+(\.[A-Za-z0-9-]+)*\.[A-Za-z]{2,}\.json$/', $filename) === 1;
    }

    /**
     * @throws StorageException when the file cannot be read
     * @throws InvalidContactDataException when the file does not contain a valid contact form
     */
    private function read(string $filename): Contact
    {
        $content = @file_get_contents($this->getPath($filename));

        if ($content === false) {
            throw new StorageException('unable to read the contact file ' . $filename);
        }

        try {
            $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new InvalidContactDataException('the contact file is not valid JSON');
        }

        if (is_array($data) === false) {
            throw new InvalidContactDataException('the contact file does not contain a JSON object');
        }

        return Contact::fromArray($data);
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
