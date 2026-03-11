<?php
declare(strict_types=1);

define('TASK_STATUS_FILE', dirname(__FILE__, 2) . '/data/tasks_status.json');

/**
 * Lädt Status-Datei oder erstellt leere Struktur
 */
function loadTaskStatus(): array {
    if (!file_exists(TASK_STATUS_FILE)) {
        file_put_contents(TASK_STATUS_FILE, json_encode([], JSON_PRETTY_PRINT));
    }

    return json_decode(file_get_contents(TASK_STATUS_FILE), true) ?? [];
}

/**
 * Speichert Status-Datei atomar
 */
function saveTaskStatus(array $data): void {
    file_put_contents(
        TASK_STATUS_FILE,
        json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
        LOCK_EX
    );
}

/**
 * Task START melden
 */
function taskStart(string $taskName): void {
    $tasks = loadTaskStatus();

    $tasks[$taskName] = [
        "last_start"   => date("Y-m-d H:i:s"),
        "last_success" => $tasks[$taskName]["last_success"] ?? null,
        "status"       => "running",
        "pid"          => getmypid()
    ];

    saveTaskStatus($tasks);
}

/**
 * Task ERFOLG melden
 */
function taskSuccess(string $taskName): void {
    $tasks = loadTaskStatus();

    $tasks[$taskName]["last_success"] = date("Y-m-d H:i:s");
    $tasks[$taskName]["status"] = "ok";

    saveTaskStatus($tasks);
}

/**
 * Task FEHLER melden
 */
function taskError(string $taskName, Throwable $e): void {
    $tasks = loadTaskStatus();

    $tasks[$taskName]["status"] = "error";
    $tasks[$taskName]["error"] = $e->getMessage();

    saveTaskStatus($tasks);
}
