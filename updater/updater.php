<?php
require_once 'Helper.php';
$config = parse_ini_file('updater.ini');

$optionalConfigValues = [
    'projectConfig', 'proxyServer', 'proxyPort', 'proxyUser', 'proxyPassword'
];

foreach ($config as $key => $configValue) {
    if (!in_array($key, $optionalConfigValues, false) && empty($configValue)) {
        Helper::consoleLog("Config value for '$key' cannot be empty!");
        die(1);
    }
}

$projectConfig = [];
$version       = "";
if (!empty($config['projectConfig']) && file_exists($config['projectConfig'])) {
    $projectConfig = parse_ini_file($config['projectConfig']);
    if (!empty($projectConfig['VERSION_NR'])) {
        $version = $projectConfig['VERSION_NR'];
    }
}

$proxy     = null;
$proxyAuth = null;

if (!empty($projectConfig['REST_PROXY'])) {
    $proxy = $projectConfig['REST_PROXY'];
    if (!empty($projectConfig['REST_PROXYPORT'])) {
        $proxy .= ':' . $projectConfig['REST_PROXYPORT'];
    }
    if (!empty($projectConfig['REST_PROXYUSER'])) {
        $proxyAuth = $projectConfig['REST_PROXYUSER'] . ':' . $projectConfig['REST_PROXYPASS'];
    }
} elseif (!empty($config['proxyServer'])) {
    $proxy = $config['proxyServer'];
    if (!empty($config['proxyPort'])) {
        $proxy .= ':' . $config['proxyPort'];
    }
    if (!empty($config['proxyUser'])) {
        $proxyAuth = $config['proxyUser'] . ':' . $config['proxyPassword'];
    }
}

$updater = new PhpGithubUpdater($config['gitHubOwner'], $config['gitHubRepository'], $config['gitHubToken'], $proxy, $proxyAuth);

try {
    // Optional: Prerelease-Update + gezieltes Tag
    Helper::consoleLog("Optional: Prerelease-Update");
    $preAnswer = Helper::consolePrompt("Prereleases einbeziehen? (y/n, Enter = n): ");
    $includePrereleases = (trim(strtolower($preAnswer)) === 'y');

    $targetTag = '';
    if ($includePrereleases) {
        $targetTag = trim((string)Helper::consolePrompt("Git-Tag für Update (leer = automatisch neueste Version inkl. Prereleases): "));
        $updater->fetchPrereleasesToo(true);
    } else {
        $updater->fetchPrereleasesToo(false);
    }

    // Wenn ein konkretes Tag angegeben wurde, Update-Entscheidung daran festmachen (statt "latest stable")
    if ($targetTag !== '') {
        $releases = $updater->getReleases(true);
        if (!isset($releases[$targetTag])) {
            throw new PguRemoteException("Das angegebene Tag '$targetTag' wurde in den Releases nicht gefunden (ggf. Tippfehler oder kein GitHub-Release).");
        }
        $isUpToDate = (!empty($version) && $updater->compareVersions($version, $targetTag) === 0);
    } else {
        $isUpToDate = $updater->isUpToDate($version);
    }

} catch (PguRemoteException $e) {
    echo $e->getMessage();
    die;
}

if (!$isUpToDate) {
    if (!is_dir($config['tempDirectory']) && !mkdir($config['tempDirectory'], 0777, true) && !is_dir($config['tempDirectory'])) {
        throw new \RuntimeException(sprintf('Directory "%s" was not created', $config['tempDirectory']));
    }
    try {
        if (empty($version)) {
            $userInput = Helper::consolePrompt("No local version defined. This update will overwrite the current files. Are you sure? (y/n): ");
            if (trim(strtolower($userInput)) !== 'y') {
                throw new Exception("Update canceled!");
            }
        }

        Helper::consoleLog("New version found. Current version: " . $version);

        // Zielversion: entweder Tag aus Eingabe oder normales Next-Version-Verhalten
        if (!empty($targetTag)) {
            $nextVersion = $targetTag;
        } else {
            $nextVersion = $updater->getNextVersion($version);
        }

        Helper::consoleLog("Downloading version: $nextVersion");
        $newArchive = $updater->downloadVersion($nextVersion, $config['tempDirectory']);

        Helper::consoleLog("Extracting $newArchive");
        $newVersionPath = $updater->extractArchive($newArchive);

        if (!empty($version)) {
            Helper::consoleLog("Downloading version: $version");
            $currentArchive = $updater->downloadVersion($version, $config['tempDirectory']);

            Helper::consoleLog("Extracting $currentArchive");
            $oldVersionPath = $updater->extractArchive($currentArchive);

            Helper::consoleLog("Checking current version...");
            PhpGithubUpdater::checkCurrentVersion($config['installDirectory'], $config['tempDirectory'] . DIRECTORY_SEPARATOR . $oldVersionPath);
            Helper::consoleLog("Version Check completed");
        }

        Helper::consoleLog("Making backup...");
        $backupFile = $updater->makeBackup($config['installDirectory'], $config['backupDirectory'], $version, explode(',', $config['excludeDir'] ?? ''));
        Helper::consoleLog("Backup complete: $backupFile");
        try {
            Helper::consoleLog("Update wird durchgeführt...");
            $result = $updater->moveFilesRecursive(
                $config['tempDirectory'] . DIRECTORY_SEPARATOR . $newVersionPath,
                $config['installDirectory']
            );
            if ($result) {
                $oldVersion = $version;
                $version    = $nextVersion;

                if (file_exists($config['projectConfig'])) {
                    $projectConfig = parse_ini_file($config['projectConfig'], true);
                    if (empty($projectConfig['version'])) {
                        $projectConfigNew['config'] = $projectConfig;
                        unset($projectConfigNew['config']['VERSION_NR']);
                        $projectConfigNew['version']['VERSION_NR'] = $version;
                        Helper::write_php_ini($projectConfigNew, $config['projectConfig']);
                    } else {
                        $projectConfig['version']['VERSION_NR'] = $version;
                        Helper::write_php_ini($projectConfig, $config['projectConfig']);
                    }
                }
                sendVersionInfo($config, $version, $oldVersion, $proxy, $proxyAuth);
                Helper::consoleLog("Update completed");
            } else {
                Helper::consoleLog("Update failed");
            }
        } catch (PguOverwriteException $e) {
            Helper::consoleLog($e->getMessage());
            if (file_exists($backupFile)) {
                Helper::consoleLog("Trying to restore Backup");
                $backupPath = $updater->extractArchive($backupFile);
                $result     = $updater->moveFilesRecursive($config['tempDirectory'] . DIRECTORY_SEPARATOR . $backupPath, $config['installDirectory']);
                if ($result) {
                    Helper::consoleLog("Restore completed");
                } else {
                    Helper::consoleLog("Restore failed");
                }
            }
        }
    } catch (Exception $e) {
        Helper::consoleLog($e->getMessage());
    } finally {
        $tmpFiles = glob($config['tempDirectory'] . DIRECTORY_SEPARATOR . '*');
        foreach ($tmpFiles as $file) {
            if (basename($file) === 'backup') {
                continue;
            }
            Helper::rrmdir($file);
        }
        Helper::consoleLog("TMP Verzeichnis geleert");
    }

} else {
    sendVersionInfo($config, $version, $version, $proxy, $proxyAuth);
    Helper::consoleLog("Version is already up to date. No update needed.");
}

function sendVersionInfo($config, $version, $previousVersion = NULL, $proxy = NULL, $proxyAuth = NULL)
{
    $postData = [
        'function' => 'insert',
        'data' => [
            'customer' => $config['customerNo'],
            'product' => $config['gitHubRepository'],
            'install_version' => $version,
            'previous_version' => $previousVersion,
            'path' => str_replace("\\updater", '', str_replace('/', '\\', getcwd())),
            'platform_name' => gethostname()
        ]
    ];

    $url = $config['customerVersionControlUrl'];
    $ch  = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_USERPWD, $config['kvkUser'] . ":" . $config['kvkPassword']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
    if (!empty($proxy)) {
        curl_setopt($ch, CURLOPT_PROXY, $proxy);
        if (!empty($proxyAuth)) {
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxyAuth);
        }
    }
    $response = curl_exec($ch);

    if (curl_error($ch)) {
        Helper::consoleLog("Failed to send version Status to KVK: " . curl_error($ch));
    }
    curl_close($ch);
    $response = json_decode($response, TRUE);
    if ($response['success'] != true) {
        $message = !is_string($response['response']) ? print_r($response['response'], true) : $response['response'];
        Helper::consoleLog("Fehler bei der Übertragung der Installationsinformationen an KVK: " . $message);
    }
}


/**
 * PHP Github Updater - Copyright 2013 Yosko (www.yosko.net)
 *
 * This file is part of PHP Github Updater.
 *
 * PHP Github Updater is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * PHP Github Updater is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with PHP Github Updater.  If not, see <http://www.gnu.org/licenses/>.
 *
 *
 * @author  yosko (http://www.yosko.net/)
 * @link    https://github.com/yosko/php-github-updater
 * @version v2
 *
 */
class PhpGithubUpdater
{
    protected
        $server,
        $user,
        $repository,
        $releases,
        $archiveExtension,
        $proxy,
        $proxyAuth,
        $token,
        $prereleasesToo;

    /**
     * Init the updater with remote repository information
     *
     * @param string      $user       user name
     * @param string      $repository repository name
     * @param string      $token      Github auth Token
     * @param null|string $proxy
     * @param null|string $proxyAuth
     * @param string      $server     (optional) server name. Default: Github
     *                                useful for Github Enterprise using Github API v3
     *
     * @throws PguRemoteException
     */
    public function __construct($user, $repository, $token, $proxy = null, $proxyAuth = null, $server = 'https://api.github.com/')
    {
        $this->user             = $user;
        $this->repository       = $repository;
        $this->server           = $server;
        $this->archiveExtension = '.zip';
        $this->releases         = false;
        $this->proxy            = $proxy;
        $this->proxyAuth        = $proxyAuth;
        $this->token            = $token;
        if (!$this->checkLogin()) {
            throw new PguRemoteException("Failed to login, check auth-token");
        }
    }

    /**
     * @param $liveVersion
     * @param $defaultVersion
     */
    public static function checkCurrentVersion($liveVersion, $defaultVersion)
    {
        $pcntlInstalled = function_exists('pcntl_async_signals');
        $liveFiles      = self::listFiles($liveVersion);
        $defaultFiles   = self::listFiles($defaultVersion);
        if ($pcntlInstalled) {
            $pb = new PHPTerminalProgressBar(count($defaultFiles));
        }
        foreach ($defaultFiles as $relativePath => $defaultFile) {
            if ($pcntlInstalled) {
                $pb->tick();
            }
            if (isset($liveFiles[$relativePath]) && !self::compareFiles($defaultFile, $liveFiles[$relativePath])) {
                throw new \RuntimeException("File has changes compared to the github Version. Automatic update could overwrite local changes. Please update manually. File: " . $liveFiles[$relativePath]);
            }
        }
        if ($pcntlInstalled) {
            $pb->end();
        }
    }

    protected static function listFiles($path)
    {
        $realPath = realpath($path);
        $files    = [];
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($realPath)) as $filename) {
            /** @var SplFileInfo $filename */
            $baseName = $filename->getFilename();
            if ($baseName !== '.' && $baseName !== '..') {
                $relativePath         = str_replace($realPath . DIRECTORY_SEPARATOR, '', $filename->getPathname());
                $files[$relativePath] = $filename->getPathname();
            }
        }
        return $files;
    }


    /**
     * @return bool
     * @throws PguRemoteException
     */
    public function checkLogin()
    {
        $result = $this->getContentFromGithub($this->server . 'user');
        $result = json_decode($result, true);

        if (!empty($result['message'])) {
            throw new PguRemoteException("Error while trying to authenticate: " . $result['message']);
        }
        if (isset($result['login'])) {
            return true;
        }
        return false;
    }

    /**
     * Define a simple proxy through which all requests to Github
     * will have to go
     *
     * @param string $proxy proxy url (in the format ip:port)
     */
    public function useProxy($proxy)
    {
        $this->proxy = $proxy;
    }

    /**
     * Define a simple proxy through which all requests to Github
     * will have to go
     *
     * @param bool $prereleasesToo
     */
    public function fetchPrereleasesToo($prereleasesToo = true)
    {
        $this->prereleasesToo = $prereleasesToo;
    }

    /**
     * Perform download and installation of the latest version
     * /!\ WARNING: you should do a backup before calling this method
     *
     * @param string $root          path where the version will be installed
     * @param string $tempDirectory path where the version could be downloaded and extracted before install
     *
     * @return string                execution status
     * @throws PguOverwriteException
     * @throws PguRemoteException
     * @throws PguExtractException
     */
    public function installLatestVersion($root, $tempDirectory)
    {
        $version = $this->getLatestVersion();
        return $this->installVersion($version, $root, $tempDirectory);
    }

    /**
     * Return the latest remote version number
     *
     * @return string version number (or false if no result)
     * @throws PguRemoteException
     */
    public function getLatestVersion()
    {
        $this->getReleases();
        $latest = false;
        if (!empty($this->releases)) {
            reset($this->releases);
            $latest = current($this->releases);
        }
        return $latest['tag_name'];
    }

    /**
     * Return the list of releases from the remote (in the Github API v3 format)
     * See: http://developer.github.com/v3/repos/releases/
     *
     * @param boolean $forceFetch force (re)fetching
     *
     * @return array               list of releases and their information
     * @throws PguRemoteException
     */
    public function getReleases($forceFetch = false)
    {
        if ($forceFetch) {
            $this->releases = false;
        }

        //load releases only once
        if ($this->releases === false) {
            $url      = $this->server . 'repos/' . $this->user . '/' . $this->repository . '/releases';
            $releases = json_decode($this->getContentFromGithub($url), true);

            $this->releases = array();
            foreach ($releases as $key => $release) {
                if ($key === 'message') {
                    //This is a message response, something went wrong
                    throw new PguRemoteException("Could not retrieve the releases: $release");
                }
                //keep pre-releases only if asked to
                if ($this->prereleasesToo || $release['prerelease'] === false) {
                    $this->releases[$release['tag_name']] = $release;
                }
            }
        }
        return $this->releases;
    }

    /**
     * Perform a request to GitHub API
     *
     * @param string $url URL to get
     * @param bool   $path
     *
     * @return string      Github's response
     * @throws PguRemoteException
     */
    public function getContentFromGithub($url, $path = false)
    {
        //use curl if possible
        if (function_exists('curl_version')) {
            $ch    = curl_init();
            $headr = array();
//            $headr[] = 'Content-length: 0';
//            $headr[] = 'Content-type: application/json';
            $headr[] = 'Accept: application/vnd.github.v3+json';
            $headr[] = 'Authorization: token ' . $this->token;

            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
            curl_setopt($ch, CURLOPT_USERAGENT, 'php-github-updater');
            curl_setopt($ch, CURLOPT_CAINFO, 'ca-bundle.crt');
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headr);
            if (!empty($this->proxy)) {
                curl_setopt($ch, CURLOPT_PROXY, $this->proxy);
                if (!empty($this->proxyAuth)) {
                    curl_setopt($ch, CURLOPT_PROXYUSERPWD, $this->proxyAuth);
                }
            }
            if ($path !== false) {
                if (!file_exists(dirname($path)) && !mkdir($concurrentDirectory = dirname($path)) && !is_dir($concurrentDirectory)) {
                    throw new RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
                }
                touch($path);
                $file = fopen($path, 'wb+');
                curl_setopt($ch, CURLOPT_FILE, $file);
                curl_setopt($ch, CURLOPT_HEADER, 0);
            }
            $content = curl_exec($ch);
            if ($content === false) {
                throw new PguRemoteException("Error fetching URL $url: " . curl_error($ch));
            }
            curl_close($ch);
            if ($path !== false) {
                fclose($file);
            }

            //fallback - might raise a warning with proxies
        } else {
            $content = file_get_contents($url);
        }

        if (empty($content)) {

            throw new PguRemoteException("Fetch data from Github failed. You might be behind a proxy.");
        }

        return $content;
    }

    /**
     * Perform download and installation of the given version
     * /!\ WARNING: you should do a backup before calling this method
     *
     * @param string $version       version to install
     * @param string $root          path where the version will be installed
     * @param string $tempDirectory path where the version could be downloaded and extracted before install
     *
     * @return boolean                execution status
     * @throws PguOverwriteException
     * @throws PguRemoteException
     * @throws PguExtractException
     */
    public function installVersion($version, $root, $tempDirectory): bool
    {
        $archive    = $this->downloadVersion($version, $tempDirectory);
        $extractDir = $this->extractArchive($archive);
        $result     = $this->moveFilesRecursive(
            $tempDirectory . DIRECTORY_SEPARATOR . $extractDir,
            $root
        );

        if (!$result) {
            throw new PguOverwriteException("Overwriting failed while installing. You might need to restore a backup of your application.");
        }

        return $result;
    }

    /**
     * Download archive for the given version directly from Github
     *
     * @param string $version
     * @param string $destDirectory path to the directory where the archive will be saved
     * @param string $extension     file extension (default: '.zip', other choice : '.tar.gz')
     *
     * @return string|false          FALSE on failure, path to archive on success
     * @throws PguRemoteException
     */
    public function downloadVersion($version, $destDirectory, $extension = '.zip')
    {
        $this->archiveExtension = $extension;
        $archive                = $destDirectory . DIRECTORY_SEPARATOR . $version . $this->archiveExtension;
        $url                    = null;
        if ($this->archiveExtension === '.zip') {
            $url = $this->getZipballUrl($version);
        } elseif ($this->archiveExtension === '.tar.gz') {
            $url = $this->getTarballUrl($version);
        }

        if (!$this->getContentFromGithub($url, $archive)) {
            throw new PguRemoteException("Download failed.");
        }

        return $archive;
    }

    /**
     * @param $fileA
     * @param $fileB
     *
     * @return bool returns true if the files are identical, false otherwise
     */
    public static function compareFiles($fileA, $fileB)
    {
        return filesize($fileA) === filesize($fileB) && md5_file($fileA) === md5_file($fileB);
    }

    /**
     * Get zipball link for the given version
     *
     * @param string $version version number
     *
     * @return string          URL to zipball
     * @throws PguRemoteException
     */
    public function getZipballUrl($version)
    {
        $this->getReleases();
        return isset($this->releases[$version]) ? $this->releases[$version]['zipball_url'] : false;
    }

    /**
     * Get tarball link for the given version
     *
     * @param string $version version number
     *
     * @return string          URL to tarball
     * @throws PguRemoteException
     */
    public function getTarballUrl($version)
    {
        $this->getReleases();
        return isset($this->releases[$version]) ? $this->releases[$version]['tarball_url'] : false;
    }

    /**
     * Extract the content
     *
     * @param string $path archive path
     *
     * @return string       name (not path!) of the subdirectory where files where extracted
     *                      should look like <user>-<repository>-<lastCommitHash>
     * @throws PguExtractException
     */
    public function extractArchive($path)
    {
        // $archive = basename($path);

        $zip = new ZipArchive;
        if ($zip->open($path) === true) {
            $stat      = $zip->statIndex(0);
            $directory = substr($stat['name'], 0, -1);
            $zip->extractTo(dirname($path));
            $zip->close();
        } else {
            throw new PguExtractException("Archive extraction failed. The file might be corrupted and you should download it again.");
        }

        return $directory;
    }

    /**
     * Recursively move all files from $source directory into $destination directory
     *
     * @param string $source      source directory from which files and subdirectories will be taken
     * @param string $destination destination directory where files and subdirectories will be put
     *
     * @return boolean              execution status
     */
    public function moveFilesRecursive($source, $destination)
    {
        $result = true;

        if (file_exists($source) && is_dir($source)) {
            if (!file_exists($destination) && !mkdir($destination) && !is_dir($destination)) {
                throw new RuntimeException(sprintf('Directory "%s" was not created', $destination));
            }

            $files = scandir($source);
            foreach ($files as $file) {
                if (in_array($file, array(".", ".."))) {
                    continue;
                }

                if (is_dir($source . DIRECTORY_SEPARATOR . $file)) {
                    $result = $this->moveFilesRecursive(
                        $source . DIRECTORY_SEPARATOR . $file,
                        $destination . DIRECTORY_SEPARATOR . $file
                    );
                } else {
                    $result = copy(
                        $source . DIRECTORY_SEPARATOR . $file,
                        $destination . DIRECTORY_SEPARATOR . $file
                    );
                    unlink($source . DIRECTORY_SEPARATOR . $file);
                }

                if (!$result) {
                    break;
                }
            }
        }

        rmdir($source);

        return $result;
    }

    /**
     * Get the remote version number following (more recent) the given one
     *
     * @param string $version version number (doesn't have to exist on remote)
     *
     * @return string          next version number (or false if no result)
     * @throws PguRemoteException
     */
    public function getNextVersion($version)
    {
        $this->getReleases();
        $nextVersion = false;
        foreach ($this->releases as $release) {
            if ($this->compareVersions($version, $release['tag_name']) < 0) {
                $nextVersion = $release['tag_name'];
                break;
            }
        }
        return $nextVersion;
    }

    /**
     * Compare two version numbers (based on PHP-standardized version numbers)
     * See http://php.net/manual/en/function.version-compare.php
     *
     * @param string $version1 first version number
     * @param string $version2 second version number
     *
     * @return integer           $version1 < $version2 => -1
     *                           $version1 = $version2 => 0
     *                           $version1 > $version2 => 1
     */
    public function compareVersions($version1, $version2)
    {
        return version_compare($version1, $version2);
    }

    /**
     * Get the title of a release
     *
     * @param string $version release version number
     *
     * @return string          title
     * @throws PguRemoteException
     */
    public function getTitle($version)
    {
        $this->getReleases();
        return $this->releases[$version]['name'] ?? '';
    }

    /**
     * Get the description of a release
     *
     * @param string $version release version number
     *
     * @return string          description (in Markdown syntax format)
     * @throws PguRemoteException
     */
    public function getdescription($version)
    {
        $this->getReleases();
        return $this->releases[$version]['body'] ?? '';
    }

    public function makeBackup($srcDir, $backupDir, $version = null, $excludeDirs = [])
    {
        if (!file_exists($backupDir)) {
            if (!mkdir($backupDir, 0777, true) && !is_dir($backupDir)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $backupDir));
            }
        }
        $backupFile = $backupDir . DIRECTORY_SEPARATOR . 'backup' . (empty($version) ? '' : $version) . '.zip';
        $zip        = new ZipArchive;
        $zip->open($backupFile, ZIPARCHIVE::CREATE);

        $filter = function ($file, $key, $iterator) use ($excludeDirs) {
            if ($iterator->hasChildren() && !in_array($file->getFilename(), $excludeDirs, false)) {
                return true;
            }
            return $file->isFile();
        };

        $innerIterator = new RecursiveDirectoryIterator(
            $srcDir,
            RecursiveDirectoryIterator::SKIP_DOTS
        );
        $iterator      = new RecursiveIteratorIterator(
            new RecursiveCallbackFilterIterator($innerIterator, $filter)
        );
        foreach ($iterator as $name => $file) {
            /** @var $file SplFileInfo */
            // Skip directories (they would be added automatically)
            if (!$file->isDir()) {
                $parentInfo = $file->getPathInfo();
                $parentDir  = $parentInfo->getFilename();
                if (!in_array($parentDir, $excludeDirs, false)) {
                    // Get real and relative path for current file
                    $filePath     = $file->getRealPath();
                    $relativePath = substr($filePath, strlen($srcDir) + 1);

                    // Add current file to archive
                    $zip->addFile($filePath, $relativePath);
                }
            }
        }
        $zip->close();
        return $backupFile;
    }

    /**
     * Check if given version is up-to-date with the remote
     *
     * @param string $version version number
     *
     * @return boolean          true if $version >= latest remote version
     * @throws PguRemoteException
     */
    public function isUpToDate($version)
    {
        $this->getReleases();
        if (!is_array($this->releases)) {
            throw new PguRemoteException("No releases found for this repository");
        }
        reset($this->releases);
        $latest = current($this->releases);
        return ($this->compareVersions($version, $latest['tag_name']) >= 0);
    }
}

class PguRemoteException extends Exception
{
}

class PguExtractException extends Exception
{
}

class PguOverwriteException extends Exception
{
}

if (function_exists('pcntl_async_signals')) {
    pcntl_async_signals(true);
    pcntl_signal(SIGINT, function ($signo) {
        fwrite(STDOUT, "\n\033[?25h");
        fwrite(STDERR, "\n\033[?25h");
        exit;
    });
}

//declare(ticks = 1);


class PHPTerminalProgressBar
{

    const MOVE_START = "\033[1G";
    const HIDE_CURSOR = "\033[?25l";
    const SHOW_CURSOR = "\033[?25h";
    const ERASE_LINE = "\033[2K";

    // Available screen width
    private $width;
    // Ouput stream. Usually STDOUT or STDERR
    private $stream;
    // Output string format
    private $format;
    // Time the progress bar was initialised in seconds (with millisecond precision)
    private $startTime;
    // Time since the last draw
    private $timeSinceLastCall;
    // Pre-defined tokens in the format
    private $ouputFind = array(':current', ':total', ':elapsed', ':percent', ':eta', ':rate');
    // Do not run drawBar more often than this (bypassed by interupt())
    public $throttle = 0.016; // 16 ms
    // The symbol to denote completed parts of the bar
    public $symbolComplete = "=";
    // The symbol to denote incomplete parts of the bar
    public $symbolIncomplete = " ";
    // Current tick number
    public $current = 0;
    // Maximum number of ticks
    public $total = 1;
    // Seconds elapsed
    public $elapsed = 0;
    // Current percentage complete
    public $percent = 0;
    // Estimated time until completion
    public $eta = 0;
    // Current rate
    public $rate = 0;

    public function __construct($total = 1, $format = "Progress: [:bar] - :current/:total - :percent% - Elapsed::elapseds - ETA::etas - Rate::rate/s", $stream = STDERR)
    {
        // Get the terminal width
//        $this->width = exec("tput cols");
        if (!is_numeric($this->width)) {
            // Default to 80 columns, mainly for windows users with no tput
            $this->width = 80;
        }

        $this->total  = $total;
        $this->format = $format;
        $this->stream = $stream;

        // Initialise the display
        fwrite($this->stream, self::HIDE_CURSOR);
        fwrite($this->stream, self::MOVE_START);

        // Set the start time
        $this->startTime         = microtime(true);
        $this->timeSinceLastCall = microtime(true);

        $this->drawBar();
    }

    /**
     * Add $amount of ticks. Usually 1, but maybe different amounts if calling
     * this on a timer or other unstable method, like a file download.
     */
    public function tick($amount = 1)
    {
        $this->update($this->current + $amount);
    }

    public function update($amount)
    {
        $this->current = $amount;
        $this->elapsed = microtime(true) - $this->startTime;
        $this->percent = $this->current / $this->total * 100;
        $this->rate    = $this->current / $this->elapsed;
        $this->eta     = ($this->current) ? ($this->elapsed / $this->current * $this->total - $this->elapsed) : false;
        $drawElapse    = microtime(true) - $this->timeSinceLastCall;
        if ($drawElapse > $this->throttle) {
            $this->drawBar();
        }
    }

    /**
     * Add a message on a newline before the progress bar
     */
    public function interupt($message)
    {
        fwrite($this->stream, self::MOVE_START);
        fwrite($this->stream, self::ERASE_LINE);
        fwrite($this->stream, $message . "\n");
        $this->drawBar();
    }

    /**
     * Does the actual drawing
     */
    private function drawBar()
    {
        $this->timeSinceLastCall = microtime(true);
        fwrite($this->stream, self::MOVE_START);

        $replace = array(
            $this->current,
            $this->total,
            $this->roundAndPadd($this->elapsed),
            $this->roundAndPadd($this->percent),
            $this->roundAndPadd($this->eta),
            $this->roundAndPadd($this->rate),
        );

        $output = str_replace($this->ouputFind, $replace, $this->format);

        if (strpos($output, ':bar') !== false) {
            $availableSpace = $this->width - strlen($output) + 4;
            $done           = $availableSpace * ($this->percent / 100);
            $left           = $availableSpace - $done;
            $output         = str_replace(':bar', str_repeat($this->symbolComplete, $done) . str_repeat($this->symbolIncomplete, $left), $output);
        }

        fwrite($this->stream, $output);
    }

    /**
     * Adds 0 and space padding onto floats to ensure the format is fixed length nnn.nn
     */
    private function roundAndPadd($input)
    {
        return str_pad(number_format($input, 2, '.', ''), 6, " ", STR_PAD_LEFT);
    }

    /**
     * Cleanup
     */
    public function end()
    {
        fwrite($this->stream, "\n" . self::SHOW_CURSOR);
    }

    public function __destruct()
    {
        $this->end();
    }

}
