<?php

namespace App\Validators;

/**
 * Validates the user-editable fields of a contact form.
 * Every method returns an array of errors indexed by field name; an empty array means the data is valid.
 */
final class ContactValidator {
    public const array EDITABLE_FIELDS = ['email', 'subject', 'message'];

    private const array MAX_LENGTHS = [
        'email' => 254,
        'subject' => 255,
        'message' => 5000,
    ];

    /**
     * Restricts the characters of an email so it can safely be used in a file name.
     */
    private const string SAFE_EMAIL_PATTERN = '/^[A-Za-z0-9._%+-]+@[A-Za-z0-9-]+(\.[A-Za-z0-9-]+)*\.[A-Za-z]{2,}$/';

    /**
     * All editable fields are required, and no other field is allowed.
     */
    public function validateForCreation(array $data): array
    {
        $errors = $this->validateUnknownFields($data);

        foreach (self::EDITABLE_FIELDS as $field) {
            if (array_key_exists($field, $data) === false) {
                $errors[$field] = 'this field is required';
            }
        }

        return $errors + $this->validateFieldValues($data);
    }

    /**
     * At least one editable field is required, and no other field is allowed.
     */
    public function validateForUpdate(array $data): array
    {
        if ($data === []) {
            return ['body' => 'at least one of these fields is required: ' . implode(', ', self::EDITABLE_FIELDS)];
        }

        return $this->validateUnknownFields($data) + $this->validateFieldValues($data);
    }

    private function validateUnknownFields(array $data): array
    {
        $errors = [];

        foreach (array_keys($data) as $field) {
            if (in_array($field, self::EDITABLE_FIELDS, true) === false) {
                $errors[(string) $field] = 'this field is not allowed';
            }
        }

        return $errors;
    }

    private function validateFieldValues(array $data): array
    {
        $errors = [];

        foreach (self::EDITABLE_FIELDS as $field) {
            if (array_key_exists($field, $data) === false) {
                continue;
            }

            $error = $this->validateValue($field, $data[$field]);

            if ($error !== null) {
                $errors[$field] = $error;
            }
        }

        return $errors;
    }

    private function validateValue(string $field, mixed $value): ?string
    {
        if (is_string($value) === false) {
            return 'this field must be a string';
        }

        if (preg_match('//u', $value) !== 1) {
            return 'this field must be valid UTF-8';
        }

        $trimmed = trim($value);

        if ($trimmed === '') {
            return 'this field must not be empty';
        }

        if ($trimmed !== $value) {
            return 'this field must not start or end with whitespace';
        }

        if (mb_strlen($value) > self::MAX_LENGTHS[$field]) {
            return 'this field must not exceed ' . self::MAX_LENGTHS[$field] . ' characters';
        }

        if ($field === 'email'
            && (filter_var($value, FILTER_VALIDATE_EMAIL) === false || preg_match(self::SAFE_EMAIL_PATTERN, $value) !== 1)) {
            return 'this field must be a valid email address';
        }

        if ($field === 'subject' && preg_match('/[\x00-\x1F\x7F]/', $value) === 1) {
            return 'this field must not contain control characters';
        }

        if ($field === 'message' && preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $value) === 1) {
            return 'this field must not contain control characters';
        }

        return null;
    }
}
