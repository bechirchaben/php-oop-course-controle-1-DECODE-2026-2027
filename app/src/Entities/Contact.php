<?php

namespace App\Entities;

use App\Exceptions\InvalidContactDataException;
use App\Validators\ContactValidator;

final class Contact {
    private const array STORED_FIELDS = ['email', 'subject', 'message', 'dateOfCreation', 'dateOfLastUpdate'];

    private function __construct(
        private string $email,
        private string $subject,
        private string $message,
        private readonly int $dateOfCreation,
        private int $dateOfLastUpdate
    ) {
    }

    /**
     * Creates a new contact form. The data must have been validated with ContactValidator::validateForCreation().
     */
    public static function create(string $email, string $subject, string $message, int $now): self
    {
        return new self($email, $subject, $message, $now, $now);
    }

    /**
     * Rebuilds a contact form from stored data, checking that the data has the expected structure.
     *
     * @throws InvalidContactDataException
     */
    public static function fromArray(array $data): self
    {
        $errors = [];

        foreach (array_keys($data) as $field) {
            if (in_array($field, self::STORED_FIELDS, true) === false) {
                $errors[(string) $field] = 'unexpected field';
            }
        }

        $editableData = array_intersect_key($data, array_flip(ContactValidator::EDITABLE_FIELDS));
        $errors += (new ContactValidator())->validateForCreation($editableData);

        foreach (['dateOfCreation', 'dateOfLastUpdate'] as $field) {
            if (array_key_exists($field, $data) === false) {
                $errors[$field] = 'missing field';
            } elseif (is_int($data[$field]) === false || $data[$field] < 0) {
                $errors[$field] = 'must be a positive integer timestamp';
            }
        }

        if ($errors === [] && $data['dateOfLastUpdate'] < $data['dateOfCreation']) {
            $errors['dateOfLastUpdate'] = 'must not be earlier than dateOfCreation';
        }

        if ($errors !== []) {
            throw new InvalidContactDataException('invalid contact data', $errors);
        }

        return new self(
            $data['email'],
            $data['subject'],
            $data['message'],
            $data['dateOfCreation'],
            $data['dateOfLastUpdate']
        );
    }

    /**
     * Applies a partial update. The data must have been validated with ContactValidator::validateForUpdate().
     */
    public function update(array $changes, int $now): void
    {
        $this->email = $changes['email'] ?? $this->email;
        $this->subject = $changes['subject'] ?? $this->subject;
        $this->message = $changes['message'] ?? $this->message;
        $this->dateOfLastUpdate = max($now, $this->dateOfCreation);
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getDateOfCreation(): int
    {
        return $this->dateOfCreation;
    }

    public function getDateOfLastUpdate(): int
    {
        return $this->dateOfLastUpdate;
    }

    public function toArray(): array
    {
        return [
            'email' => $this->email,
            'subject' => $this->subject,
            'message' => $this->message,
            'dateOfCreation' => $this->dateOfCreation,
            'dateOfLastUpdate' => $this->dateOfLastUpdate,
        ];
    }
}
