@ECHO OFF
setlocal EnableDelayedExpansion

set phpLocations="%PROGRAMFILES%\PHP 7.0\php.exe"
set phpLocations=%phpLocations%;"%PROGRAMFILES%\PHP 7.1\php.exe"
set phpLocations=%phpLocations%;"%PROGRAMFILES%\PHP 7.2\php.exe"
set phpLocations=%phpLocations%;"%PROGRAMFILES%\PHP 7.3\php.exe"
set phpLocations=%phpLocations%;"%PROGRAMFILES%\PHP 7.4\php.exe"
set phpLocations=%phpLocations%;"%PROGRAMFILES%\PHP 8.1\php.exe"
set exe=""

WHERE php >nul 2>nul
IF %ERRORLEVEL% NEQ 0 (
    (for %%a in (%phpLocations%) do (
       if exist %%a (
           set exe=%%a
       )
    ))
    IF [!exe!] EQU [""] (
        echo Konnte keine PHP Version finden
        pause
        EXIT /B
    )
) ELSE (
    set exe=php
)

!exe! -f config-rewrite.php

pause