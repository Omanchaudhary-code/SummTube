<?php
// ==========================================
// CORE VALIDATOR (CONTINUED)
// File: core/Validator.php
// ==========================================

namespace Core;

class Validator
{
    private array $data;
    private array $rules;
    private array $errors = [];

    public function __construct(array $data, array $rules)
    {
        $this->data = $data;
        $this->rules = $rules;
    }

    public function validate(): bool
    {
        foreach ($this->rules as $field => $ruleString) {
            $rules = explode('|', $ruleString);
            
            foreach ($rules as $rule) {
                $this->applyRule($field, $rule);
            }
        }

        return empty($this->errors);
    }

    private function applyRule(string $field, string $rule): void
    {
        $value = $this->data[$field] ?? null;

        // Required
        if ($rule === 'required' && empty($value)) {
            $this->errors[$field][] = "$field is required";
            return;
        }

        // Email
        if ($rule === 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field][] = "$field must be a valid email";
        }

        // Min length (e.g., min:8)
        if (str_starts_with($rule, 'min:')) {
            $min = (int) str_replace('min:', '', $rule);
            if (strlen($value) < $min) {
                $this->errors[$field][] = "$field must be at least $min characters";
            }
        }

        // Max length (e.g., max:255)
        if (str_starts_with($rule, 'max:')) {
            $max = (int) str_replace('max:', '', $rule);
            if (strlen($value) > $max) {
                $this->errors[$field][] = "$field must not exceed $max characters";
            }
        }

        // URL
        if ($rule === 'url' && !filter_var($value, FILTER_VALIDATE_URL)) {
            $this->errors[$field][] = "$field must be a valid URL";
        }
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function fails(): bool
    {
        return !empty($this->errors);
    }
}
