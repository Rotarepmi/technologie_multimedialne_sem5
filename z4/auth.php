<?php
declare(strict_types=1);
session_start(); // zapewnia dostęp do zmiennych sesyjnych w danym pliku
if (!isset($_SESSION['loggedin'])) {
  header('Location: login.php');
  exit();
}

function is_admin(): bool
{
  return !empty($_SESSION['is_admin']);
}

// Strona główna zależy od roli: admin widzi wszystkie hosty (admin.php), reszta tylko swoje (index.php)
function home_url(): string
{
  return is_admin() ? 'admin.php' : 'index.php';
}

function require_admin(): void
{
  if (!is_admin()) {
    header('Location: index.php');
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
