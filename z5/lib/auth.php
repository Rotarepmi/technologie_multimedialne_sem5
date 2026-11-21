<?php
declare(strict_types=1);
session_start(); // zapewnia dostęp do zmiennych sesyjnych w danym pliku
if (!isset($_SESSION['loggedin'], $_SESSION['home'])) {
  header('Location: ../auth/login.php');
  exit();
}

function is_admin(): bool
{
  return !empty($_SESSION['is_admin']);
}

function require_admin(): void
{
  if (!is_admin()) {
    header('Location: ../drive/index.php');
    exit();
  }
}

// Komunikat przekazywany między stronami przez sesję (po header('Location: ...'))
function flash(string $type, string $message): void
{
  $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function redirect(string $url): never
{
  header('Location: ' . $url);
  exit();
}

// Token chroniący operacje zmieniające dane (tworzenie, usuwanie, upload) przed CSRF
function csrf_token(): string
{
  return $_SESSION['csrf'] ??= bin2hex(random_bytes(16));
}

function csrf_field(): string
{
  return '<input type="hidden" name="csrf" value="' . csrf_token() . '">';
}

function require_post_csrf(): void
{
  if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !hash_equals(csrf_token(), (string)($_POST['csrf'] ?? ''))) {
    flash('danger', 'Nieprawidłowe żądanie - odśwież stronę i spróbuj ponownie.');
    redirect('../drive/index.php');
  }
}
