<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Server-side validation. Rules are strings like `required|email|max:255`.
 * Never trust the browser: this is the authoritative check.
 */
final class Validator
{
    private array $errors = [];
    private array $clean  = [];

    /**
     * @param array<string,mixed> $data
     * @param array<string,string> $rules  field => "rule|rule:arg"
     */
    public function __construct(private array $data, private array $rules) {}

    public function validate(): bool
    {
        foreach ($this->rules as $field => $ruleString) {
            $value = $this->data[$field] ?? null;
            $value = is_string($value) ? trim($value) : $value;
            $rules = explode('|', $ruleString);
            $required = in_array('required', $rules, true);

            if ($value === null || $value === '' || $value === []) {
                if ($required) {
                    $this->errors[$field] = $this->label($field) . ' is required.';
                } else {
                    $this->clean[$field] = is_string($value) ? '' : $value;
                }
                continue;
            }

            $failed = false;
            foreach ($rules as $rule) {
                if ($rule === 'required' || $rule === '') {
                    continue;
                }

                [$name, $arg] = array_pad(explode(':', $rule, 2), 2, null);
                $error = $this->applyRule($field, $name, $arg, $value);
                if ($error !== null) {
                    $this->errors[$field] = $error;
                    $failed = true;
                    break;
                }
            }

            if (!$failed) {
                $this->clean[$field] = is_string($value) ? trim($value) : $value;
            }
        }

        return $this->errors === [];
    }

    private function applyRule(string $field, string $name, ?string $arg, mixed $value): ?string
    {
        $label = $this->label($field);

        return match ($name) {
            'email' => filter_var($value, FILTER_VALIDATE_EMAIL) ? null : "$label must be a valid email address.",
            'min'   => mb_strlen((string) $value) >= (int) $arg ? null : "$label must be at least $arg characters.",
            'max'   => mb_strlen((string) $value) <= (int) $arg ? null : "$label must not exceed $arg characters.",
            'int'   => filter_var($value, FILTER_VALIDATE_INT) !== false ? null : "$label must be a whole number.",
            'numeric' => is_numeric($value) ? null : "$label must be a number.",
            'url'   => filter_var($value, FILTER_VALIDATE_URL) ? null : "$label must be a valid URL.",
            'slug'  => preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', (string) $value) ? null : "$label may only contain lowercase letters, numbers and hyphens.",
            // Values are separated by `;` so that option text may itself contain
            // commas (e.g. budget ranges like "$5,000 – $10,000").
            'in'    => in_array((string) $value, explode(';', (string) $arg), true) ? null : "$label is not a valid option.",
            'year'  => (preg_match('/^\d{4}$/', (string) $value) && (int) $value >= 1900 && (int) $value <= (int) date('Y') + 5)
                        ? null : "$label must be a four-digit year.",
            'date'  => strtotime((string) $value) !== false ? null : "$label must be a valid date.",
            default => null,
        };
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function firstError(): ?string
    {
        return $this->errors === [] ? null : reset($this->errors);
    }

    public function clean(): array
    {
        return $this->clean;
    }

    private function label(string $field): string
    {
        return ucfirst(str_replace(['_', '-'], ' ', $field));
    }
}
