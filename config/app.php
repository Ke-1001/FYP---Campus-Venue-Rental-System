<?php
// This section handles app logic.
function getAppBaseUrl(): string
{
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'];
    $scriptName = $_SERVER['SCRIPT_NAME'];
    $parts = explode('/', trim($scriptName, '/'));
    $knownFolders = ['actions', 'User', 'admin', 'api', 'cron'];
    if (!empty($parts[0]) && !in_array($parts[0], $knownFolders, true))
    {
        return $protocol . "://" . $host . "/" . $parts[0];
    }
    return $protocol . "://" . $host;
}
