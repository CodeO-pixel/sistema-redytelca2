<?php
declare(strict_types=1);

namespace App\Core;

/**
* Clase Validator
*
* Acumula reglas de validación sobre un array de datos y lanza
* ValidationException si alguna falla.
*/
final class Validator
{
/**
* @var array Datos de entrada a validar.
*/
private array $data;

/**
* @var array Errores acumulados (campo => mensaje).
*/
private array $errors = [];

/**
* Constructor privado.
*
* @param array $data Datos a validar.
*/
private function __construct(array $data)
{
$this->data = $data;
}

/**
* Crea una nueva instancia del validador.
*
* @param array $data Datos a validar.
* @return self
*/
public static function make(array $data): self
{
return new self($data);
}

/**
* Verifica si un valor se considera vacío.
*
* @param mixed $value
* @return bool
*/
private function isEmpty($value): bool
{
if ($value === null) {
return true;
}
if (is_string($value) && trim($value) === '') {
return true;
}
if (is_array($value) && empty($value)) {
return true;
}
return false;
}

/**
* Agrega un error a un campo si no tiene uno ya.
*
* @param string $field Nombre del campo.
* @param string $message Mensaje de error.
* @return void
*/
private function addError(string $field, string $message): void
{
if (!isset($this->errors[$field])) {
$this->errors[$field] = $message;
}
}

/**
* Valida que el campo sea obligatorio.
*
* @param string $field Nombre del campo.
* @param string|null $label Etiqueta humana para el mensaje.
* @return self
*/
public function required(string $field, ?string $label = null): self
{
$label = $label ?? $field;
$value = $this->data[$field] ?? null;

if ($this->isEmpty($value)) {
$this->addError($field, "El campo {$label} es obligatorio.");
}

return $this;
}

/**
* Valida que el campo sea una cadena de texto y cumpla con longitudes.
*
* @param string $field Nombre del campo.
* @param int|null $min Longitud mínima.
* @param int|null $max Longitud máxima.
* @param string|null $label Etiqueta humana para el mensaje.
* @return self
*/
public function string(string $field, ?int $min = null, ?int $max = null, ?string $label = null): self
{
$label = $label ?? $field;
$value = $this->data[$field] ?? null;

if ($this->isEmpty($value)) {
return $this;
}

if (!is_scalar($value) || is_bool($value)) {
$this->addError($field, "El campo {$label} debe ser texto.");
return $this;
}

$strValue = (string) $value;
$length = mb_strlen($strValue);

if ($min !== null && $length < $min) {
$this->addError($field, "El campo {$label} debe tener al menos {$min} caracteres.");
} elseif ($max !== null && $length > $max) {
$this->addError($field, "El campo {$label} no debe exceder {$max} caracteres.");
}

return $this;
}

/**
* Valida que el campo sea un número entero.
*
* @param string $field Nombre del campo.
* @param int|null $min Valor mínimo.
* @param int|null $max Valor máximo.
* @param string|null $label Etiqueta humana para el mensaje.
* @return self
*/
public function int(string $field, ?int $min = null, ?int $max = null, ?string $label = null): self
{
$label = $label ?? $field;
$value = $this->data[$field] ?? null;

if ($this->isEmpty($value)) {
return $this;
}

$filtered = filter_var($value, FILTER_VALIDATE_INT);
if ($filtered === false) {
$this->addError($field, "El campo {$label} debe ser un número entero.");
return $this;
}

if ($min !== null && $filtered < $min) {
$this->addError($field, "El campo {$label} debe ser al menos {$min}.");
} elseif ($max !== null && $filtered > $max) {
$this->addError($field, "El campo {$label} no debe exceder {$max}.");
}

return $this;
}

/**
* Valida que el campo sea numérico.
*
* @param string $field Nombre del campo.
* @param float|null $min Valor mínimo.
* @param float|null $max Valor máximo.
* @param string|null $label Etiqueta humana para el mensaje.
* @return self
*/
public function numeric(string $field, ?float $min = null, ?float $max = null, ?string $label = null): self
{
$label = $label ?? $field;
$value = $this->data[$field] ?? null;

if ($this->isEmpty($value)) {
return $this;
}

if (!is_numeric($value)) {
$this->addError($field, "El campo {$label} debe ser numérico.");
return $this;
}

$floatValue = (float) $value;

if ($min !== null && $floatValue < $min) {
$this->addError($field, "El campo {$label} debe ser al menos {$min}.");
} elseif ($max !== null && $floatValue > $max) {
$this->addError($field, "El campo {$label} no debe exceder {$max}.");
}

return $this;
}

/**
* Valida que el campo sea un correo electrónico válido.
*
* @param string $field Nombre del campo.
* @param string|null $label Etiqueta humana para el mensaje.
* @return self
*/
public function email(string $field, ?string $label = null): self
{
$label = $label ?? $field;
$value = $this->data[$field] ?? null;

if ($this->isEmpty($value)) {
return $this;
}

if (filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
$this->addError($field, "El campo {$label} debe ser un correo electrónico válido.");
}

return $this;
}

/**
* Valida que el campo esté dentro de una lista de valores permitidos.
*
* @param string $field Nombre del campo.
* @param array $allowed Lista de valores permitidos.
* @param string|null $label Etiqueta humana para el mensaje.
* @return self
*/
public function enum(string $field, array $allowed, ?string $label = null): self
{
$label = $label ?? $field;
$value = $this->data[$field] ?? null;

if ($this->isEmpty($value)) {
return $this;
}

if (!in_array($value, $allowed, true)) {
$lista = implode(', ', $allowed);
$this->addError($field, "El campo {$label} debe ser uno de: {$lista}.");
}

return $this;
}

/**
* Valida que el campo cumpla con una expresión regular.
*
* @param string $field Nombre del campo.
* @param string $pattern Expresión regular.
* @param string $message Mensaje de error exacto.
* @param string|null $label Etiqueta humana (no se usa en el mensaje, solo para firma).
* @return self
*/
public function regex(string $field, string $pattern, string $message, ?string $label = null): self
{
$value = $this->data[$field] ?? null;

if ($this->isEmpty($value)) {
return $this;
}

if (preg_match($pattern, (string) $value) !== 1) {
$this->addError($field, $message);
}

return $this;
}

/**
* Valida que el campo sea una fecha con un formato específico.
*
* @param string $field Nombre del campo.
* @param string $format Formato de fecha esperado.
* @param string|null $label Etiqueta humana para el mensaje.
* @return self
*/
public function date(string $field, string $format = 'Y-m-d', ?string $label = null): self
{
$label = $label ?? $field;
$value = $this->data[$field] ?? null;

if ($this->isEmpty($value)) {
return $this;
}

$date = \DateTime::createFromFormat($format, (string) $value);
$errors = \DateTime::getLastErrors();

if ($date === false || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) {
$this->addError($field, "El campo {$label} debe ser una fecha con formato {$format}.");
}

return $this;
}

/**
* Valida que el campo numérico esté dentro de un rango específico.
*
* @param string $field Nombre del campo.
* @param float|int $min Valor mínimo.
* @param float|int $max Valor máximo.
* @param string|null $label Etiqueta humana para el mensaje.
* @return self
*/
public function inRange(string $field, $min, $max, ?string $label = null): self
{
$label = $label ?? $field;
$value = $this->data[$field] ?? null;

if ($this->isEmpty($value)) {
return $this;
}

if (!is_numeric($value) || (float) $value < (float) $min || (float) $value > (float) $max) {
$this->addError($field, "El campo {$label} debe estar entre {$min} y {$max}.");
}

return $this;
}

/**
* Indica si la validación ha fallado.
*
* @return bool
*/
public function fails(): bool
{
return !empty($this->errors);
}

/**
* Obtiene los errores acumulados.
*
* @return array
*/
public function errors(): array
{
return $this->errors;
}

/**
* Lanza una excepción si la validación falla, o retorna los datos originales.
*
* @return array
* @throws \App\Exceptions\ValidationException
*/
public function validateOrFail(): array
{
if ($this->fails()) {
throw new \App\Exceptions\ValidationException($this->errors);
}

return $this->data;
}
}