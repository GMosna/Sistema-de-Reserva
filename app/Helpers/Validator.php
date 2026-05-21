<?php
namespace App\Helpers;

class Validator
{
    private array $data;
    private array $errors = [];

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function required(string $field, string $label = ''): static
    {
        if (!isset($this->errors[$field])) {
            $label = $label ?: ucfirst($field);
            if (trim($this->data[$field] ?? '') === '') {
                $this->errors[$field] = "{$label} é obrigatório.";
            }
        }
        return $this;
    }

    public function email(string $field, string $label = ''): static
    {
        if (!isset($this->errors[$field])) {
            $label = $label ?: ucfirst($field);
            $value = trim($this->data[$field] ?? '');
            if ($value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $this->errors[$field] = "{$label} deve ser um e-mail válido.";
            }
        }
        return $this;
    }

    public function min(string $field, int $min, string $label = ''): static
    {
        if (!isset($this->errors[$field])) {
            $label = $label ?: ucfirst($field);
            $value = $this->data[$field] ?? '';
            if ($value !== '' && mb_strlen($value) < $min) {
                $this->errors[$field] = "{$label} deve ter no mínimo {$min} caracteres.";
            }
        }
        return $this;
    }

    public function max(string $field, int $max, string $label = ''): static
    {
        if (!isset($this->errors[$field])) {
            $label = $label ?: ucfirst($field);
            $value = $this->data[$field] ?? '';
            if ($value !== '' && mb_strlen($value) > $max) {
                $this->errors[$field] = "{$label} deve ter no máximo {$max} caracteres.";
            }
        }
        return $this;
    }

    public function numeric(string $field, string $label = ''): static
    {
        if (!isset($this->errors[$field])) {
            $label = $label ?: ucfirst($field);
            $value = $this->data[$field] ?? '';
            if ($value !== '' && !is_numeric($value)) {
                $this->errors[$field] = "{$label} deve ser um número.";
            }
        }
        return $this;
    }

    public function in(string $field, array $allowed, string $label = ''): static
    {
        if (!isset($this->errors[$field])) {
            $label = $label ?: ucfirst($field);
            $value = $this->data[$field] ?? '';
            if ($value !== '' && !in_array($value, $allowed, true)) {
                $this->errors[$field] = "{$label} contém um valor inválido.";
            }
        }
        return $this;
    }

    public function date(string $field, string $format = 'Y-m-d', string $label = ''): static
    {
        if (!isset($this->errors[$field])) {
            $label = $label ?: ucfirst($field);
            $value = $this->data[$field] ?? '';
            if ($value !== '') {
                $dt = \DateTime::createFromFormat($format, $value);
                if (!$dt || $dt->format($format) !== $value) {
                    $this->errors[$field] = "{$label} deve ser uma data válida ({$format}).";
                }
            }
        }
        return $this;
    }

    public function fails(): bool
    {
        return !empty($this->errors);
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function firstError(): ?string
    {
        return $this->errors ? array_values($this->errors)[0] : null;
    }
}
