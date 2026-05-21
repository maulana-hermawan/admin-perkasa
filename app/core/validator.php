<?php
/**
 * app/core/validator.php
 * Validasi form input yang mudah dipakai.
 *
 * @example
 *   $v = new Validator($_POST);
 *   $v->required('nama')->min('nama', 2)->max('nama', 150);
 *   $v->required('email')->email('email');
 *   $v->numeric('nominal')->min_val('nominal', 1000);
 *   $v->enum('status', ['Aktif','Cuti','Lulus','Tidak Aktif']);
 *   if ($v->fails()) { flash('danger', $v->first()); redirect('...'); }
 */

declare(strict_types=1);

class Validator
{
    private array $data;
    private array $errors = [];

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    // =====================================================
    // RULES
    // =====================================================

    public function required(string $field, string $label = ''): static
    {
        $label = $label ?: ucfirst(str_replace('_', ' ', $field));
        $val   = trim((string)($this->data[$field] ?? ''));
        if ($val === '') {
            $this->errors[$field] = "{$label} wajib diisi.";
        }
        return $this;
    }

    public function email(string $field, string $label = ''): static
    {
        $label = $label ?: ucfirst(str_replace('_', ' ', $field));
        $val   = trim((string)($this->data[$field] ?? ''));
        if ($val !== '' && !filter_var($val, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = "{$label} harus berupa email yang valid.";
        }
        return $this;
    }

    public function min(string $field, int $length, string $label = ''): static
    {
        $label = $label ?: ucfirst(str_replace('_', ' ', $field));
        $val   = trim((string)($this->data[$field] ?? ''));
        if ($val !== '' && mb_strlen($val) < $length) {
            $this->errors[$field] = "{$label} minimal {$length} karakter.";
        }
        return $this;
    }

    public function max(string $field, int $length, string $label = ''): static
    {
        $label = $label ?: ucfirst(str_replace('_', ' ', $field));
        $val   = trim((string)($this->data[$field] ?? ''));
        if ($val !== '' && mb_strlen($val) > $length) {
            $this->errors[$field] = "{$label} maksimal {$length} karakter.";
        }
        return $this;
    }

    public function numeric(string $field, string $label = ''): static
    {
        $label = $label ?: ucfirst(str_replace('_', ' ', $field));
        $val   = trim((string)($this->data[$field] ?? ''));
        if ($val !== '' && !is_numeric($val)) {
            $this->errors[$field] = "{$label} harus berupa angka.";
        }
        return $this;
    }

    public function min_val(string $field, float $min, string $label = ''): static
    {
        $label = $label ?: ucfirst(str_replace('_', ' ', $field));
        $val   = trim((string)($this->data[$field] ?? ''));
        if (is_numeric($val) && (float)$val < $min) {
            $this->errors[$field] = "{$label} minimal {$min}.";
        }
        return $this;
    }

    public function max_val(string $field, float $max, string $label = ''): static
    {
        $label = $label ?: ucfirst(str_replace('_', ' ', $field));
        $val   = trim((string)($this->data[$field] ?? ''));
        if (is_numeric($val) && (float)$val > $max) {
            $this->errors[$field] = "{$label} maksimal {$max}.";
        }
        return $this;
    }

    public function enum(string $field, array $allowed, string $label = ''): static
    {
        $label = $label ?: ucfirst(str_replace('_', ' ', $field));
        $val   = $this->data[$field] ?? '';
        if ($val !== '' && !in_array($val, $allowed, true)) {
            $this->errors[$field] = "{$label} nilai tidak valid.";
        }
        return $this;
    }

    public function date(string $field, string $label = ''): static
    {
        $label = $label ?: ucfirst(str_replace('_', ' ', $field));
        $val   = trim((string)($this->data[$field] ?? ''));
        if ($val !== '') {
            $d = \DateTime::createFromFormat('Y-m-d', $val);
            if (!$d || $d->format('Y-m-d') !== $val) {
                $this->errors[$field] = "{$label} format tanggal tidak valid (YYYY-MM-DD).";
            }
        }
        return $this;
    }

    public function phone(string $field, string $label = ''): static
    {
        $label = $label ?: ucfirst(str_replace('_', ' ', $field));
        $val   = preg_replace('/[^0-9]/', '', (string)($this->data[$field] ?? ''));
        if ($val !== '' && (strlen($val) < 9 || strlen($val) > 15)) {
            $this->errors[$field] = "{$label} nomor HP tidak valid (9-15 digit).";
        }
        return $this;
    }

    public function unique(string $field, string $table, string $column, ?int $except_id = null, string $label = ''): static
    {
        $label = $label ?: ucfirst(str_replace('_', ' ', $field));
        $val   = trim((string)($this->data[$field] ?? ''));
        if ($val === '') return $this;

        $sql    = "SELECT id FROM `{$table}` WHERE `{$column}` = ? LIMIT 1";
        $params = [$val];
        $types  = 's';

        if ($except_id !== null) {
            $sql    = "SELECT id FROM `{$table}` WHERE `{$column}` = ? AND id != ? LIMIT 1";
            $params = [$val, $except_id];
            $types  = 'si';
        }

        $exists = db_fetch($sql, $types, $params);
        if ($exists) {
            $this->errors[$field] = "{$label} sudah digunakan. Pilih yang lain.";
        }
        return $this;
    }

    // =====================================================
    // RESULT
    // =====================================================

    public function fails(): bool
    {
        return !empty($this->errors);
    }

    public function passes(): bool
    {
        return empty($this->errors);
    }

    public function errors(): array
    {
        return $this->errors;
    }

    /** Return pesan error pertama */
    public function first(): string
    {
        return reset($this->errors) ?: '';
    }

    /** Return semua pesan error sebagai string list */
    public function all_messages(string $separator = ' | '): string
    {
        return implode($separator, $this->errors);
    }

    /** Apakah field tertentu punya error? */
    public function has(string $field): bool
    {
        return isset($this->errors[$field]);
    }

    /** Return class Bootstrap untuk field (valid/invalid) */
    public function class(string $field): string
    {
        return isset($this->errors[$field]) ? 'is-invalid' : '';
    }

    /** Return div invalid-feedback untuk field */
    public function feedback(string $field): string
    {
        if (!isset($this->errors[$field])) return '';
        return '<div class="invalid-feedback">' . htmlspecialchars($this->errors[$field], ENT_QUOTES, 'UTF-8') . '</div>';
    }
}

/**
 * Factory function untuk Validator (shortcut)
 * @example $v = validate($_POST);
 */
function validate(array $data): Validator
{
    return new Validator($data);
}
