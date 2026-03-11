<?php

class user
{
    public const TOKEN_NAME = 'ScanHUB_user';
    public const TABLE_NAME = 'KT_USERS';
    public static $currentUser = null;
    /** @var DbConnector */
    protected static $dbConnection;
    protected static $dbChecked = false;

    public        $name;
    public        $mandant;
    public        $filiale;
    public        $active;
    public static $mandant_name;
    public static $filiale_name;
    public static $hersteller_name;
    public static $hersteller_description;
    public static $hersteller_css;
    public static $hersteller_logo;
    public static $hersteller_color;

    protected $id;
    protected $password;
    protected $userLogin;
    protected $userPassword;
    protected $userFid;
    protected $userFirstname;
    protected $userLastname;
    protected $userPosition;
    protected $userGroups;
    protected $userPwTag;
    protected $userPwKey;
    protected $user_path;
    protected $userEmail;
    protected $userSource;
    protected $created_at;
    protected $created_by;
    protected $updated_at;
    protected $updated_by;

    /**
     * User constructor.
     *
     * @param      $name
     * @param null $mandant
     * @param null $filiale
     * @param null $active
     * @param null $id
     * @param null $password
     * @param null $userLogin
     * @param null $userPassword
     * @param null $userFid
     * @param null $userFirstname
     * @param null $userLastname
     * @param null $userPosition
     * @param null $userGroups
     * @param null $userPwTag
     * @param null $userPwKey
     * @param null $created_at
     * @param null $created_by
     * @param null $updated_at
     * @param null $updated_by
     * @param null $user_path
     * @param null $userSource
     */
    public function __construct($name,
                                $mandant = null,
                                $filiale = null,
                                $active = null,
                                $id = null,
                                $password = null,
                                $userLogin = null,
                                $userPassword = null,
                                $userFid = null,
                                $userFirstname = null,
                                $userLastname = null,
                                $userPosition = null,
                                $userGroups = null,
                                $userPwTag = null,
                                $userPwKey = null,
                                $created_at = null,
                                $created_by = null,
                                $updated_at = null,
                                $updated_by = null,
                                $user_path = null,
                                $userSource = null,
                                $userEmail = null)
    {
        $this->name          = $name;
        $this->mandant       = $mandant;
        $this->filiale       = $filiale;
        $this->active        = $active;
        $this->id            = $id;
        $this->password      = $password;
        $this->userLogin     = $userLogin;
        $this->userPassword  = $userPassword;
        $this->userFid       = $userFid;
        $this->userFirstname = $userFirstname;
        $this->userLastname  = $userLastname;
        $this->userPosition  = $userPosition;
        $this->userGroups    = $userGroups;
        $this->userPwTag     = $userPwTag;
        $this->userPwKey     = $userPwKey;
        $this->created_at    = $created_at;
        $this->created_by    = $created_by;
        $this->updated_at    = $updated_at;
        $this->updated_by    = $updated_by;
        $this->user_path     = $user_path;
        $this->userSource    = $userSource;
        $this->userEmail     = $userEmail;
    }


    /**
     * @param string      $name
     * @param string      $password
     * @param null|string $mandant
     * @param null|string $filiale
     *
     * @return User
     * @throws DatabaseException
     */
    public static function createUser($name, $password, $mandant = null, $filiale = null)
    {
        self::checkDatabaseTable();
        $exists = self::$dbConnection->rowExists(self::TABLE_NAME, ['name' => $name, 'mandant' => $mandant, 'filiale' => $filiale]);
        if ($exists) {
            // throw new RuntimeException("Benutzer mit dieser Kombination aus name, mandant und filiale existiert bereits.");
            error_log("Benutzer mit dieser Kombination aus name, mandant und filiale existiert bereits.");
            return false;
        } else {
            $user = new self($name, $mandant, $filiale, 1);
            $user->setPassword($password);
            $user->saveToDb();
            return $user;
        }

    }

    /**
     * @throws DatabaseException
     */
    protected static function checkDatabaseTable()
    {
        if (!self::$dbChecked) {
            if (empty(self::$dbConnection)) {
                global $connection;
                if (empty($connection)) {
                    throw new RuntimeException("No Database connection available");
                }
                self::$dbConnection = $connection;
            }
            if (!defined('USER_DB_CREATED') || !USER_DB_CREATED) {
                $tableExists = self::$dbConnection->tableExists(self::TABLE_NAME);
                if (!$tableExists) {
                    self::createDatabaseTable();
                }
                file_put_contents(ROOT . DIRECTORY_SEPARATOR . 'sicher' . DIRECTORY_SEPARATOR . 'ini' . DIRECTORY_SEPARATOR . 'config.ini', "\r\nUSER_DB_CREATED = true", FILE_APPEND);
            }

            // Prüfen ob die userEmail Spalte existiert (Migration für Bestandssysteme)
            self::checkUserEmailColumn();

            self::$dbChecked = true;
        }
    }

    /**
     * Prüft ob die Spalte userEmail in der Tabelle existiert und fügt sie ggf. hinzu.
     */
    protected static function checkUserEmailColumn()
    {
        try {
            if (!self::$dbConnection->columnExists(self::TABLE_NAME, 'userEmail')) {
                $columnType = self::$dbConnection->string(255);
                self::$dbConnection->addTableColumn(self::TABLE_NAME, 'userEmail', $columnType);
                error_log("Spalte 'userEmail' wurde erfolgreich zur Tabelle '" . self::TABLE_NAME . "' hinzugefügt.");
            }
        } catch (Exception $e) {
            error_log("Fehler bei der Migration der Tabelle '" . self::TABLE_NAME . "': " . $e->getMessage());
        }
    }

    protected static function createDatabaseTable()
    {
        $fields = [
            'id'            => self::$dbConnection->primaryKey(),
            'name'          => self::$dbConnection->string(100) . ' NOT NULL',
            'password'      => self::$dbConnection->string(255),
            'mandant'       => self::$dbConnection->string(50),
            'filiale'       => self::$dbConnection->string(50),
            'active'        => self::$dbConnection->int(),
            'userLogin'     => self::$dbConnection->string(255),
            'userPassword'  => self::$dbConnection->string(255),
            'userFid'       => self::$dbConnection->string(50),
            'userFirstname' => self::$dbConnection->string(255),
            'userLastname'  => self::$dbConnection->string(255),
            'userPosition'  => self::$dbConnection->string(255),
            'userGroups'    => self::$dbConnection->text(),
            'userPwTag'     => self::$dbConnection->string(255),
            'userPwKey'     => self::$dbConnection->string(255),
            'created_at'    => self::$dbConnection->dateTime(),
            'updated_at'    => self::$dbConnection->dateTime(),
            'created_by'    => self::$dbConnection->int(),
            'updated_by'    => self::$dbConnection->int(),
            'user_path'     => self::$dbConnection->string(255),
            'userSource'    => self::$dbConnection->string(100),
            'userEmail'     => self::$dbConnection->string(255),
        ];
        self::$dbConnection->createTable(self::TABLE_NAME, $fields);
    }

    /**
     * @param string $password
     */
    public function setPassword($password)
    {
        $this->password = password_hash($password, PASSWORD_ARGON2I);
    }

    /**
     * @throws DatabaseException
     */
    public function saveToDb()
    {
        $currentUser = null;
        if (self::getLoggedInUser() !== false) {
            $currentUser = self::getLoggedInUser()->id;
        }
        if ($this->id === null) {
            $this->created_at = date('Y-m-d H:i:s');
            $this->created_by = $currentUser;
        }
        $this->updated_at = date('Y-m-d H:i:s');
        $this->updated_by = $currentUser;
        $data             = get_object_vars($this);
        unset($data['id']);
        if ($this->id === null) {
            $result   = self::$dbConnection->insertRow(self::TABLE_NAME, $data);
            $this->id = $result;
        } else {
            self::$dbConnection->update(self::TABLE_NAME, $data, ['id' => $this->id]);
        }
    }

    /**
     * @return false|User
     * @throws DatabaseException
     */
    public static function getLoggedInUser()
    {
        if (self::$currentUser === null) {
            if (!isset($_SESSION[self::TOKEN_NAME])) {
                self::$currentUser = false;
                return false;
            }
            self::$currentUser = self::getUser($_SESSION[self::TOKEN_NAME]);
            // self::$currentUser = true;
        }
        return self::$currentUser;
    }

    /**
     * @param int $id
     *
     * @return User|false
     * @throws DatabaseException
     */
    public static function getUser($id)
    {
        self::checkDatabaseTable();
        $dbResult = self::$dbConnection->getOne('KT_USERS', ['id' => $id]);
        if (!empty($dbResult)) {
            return self::dbVarsToUser($dbResult);
        }

        return false;
    }

    public static function getUsers($arr_where = null)
    {
        self::checkDatabaseTable();
        $dbResult = self::$dbConnection->getAll('KT_USERS', '*', $arr_where);
        if (!empty($dbResult)) {
            return $dbResult;
        }
        return false;
    }

    /**
     * @param array $dbResult
     *
     * @return User
     */
    protected static function dbVarsToUser($dbResult)
    {
        return new self(
            $dbResult['name'],
            $dbResult['mandant'],
            $dbResult['filiale'],
            $dbResult['active'],
            $dbResult['id'],
            $dbResult['password'],
            $dbResult['userLogin'],
            $dbResult['userPassword'],
            $dbResult['userFid'],
            $dbResult['userFirstname'],
            $dbResult['userLastname'],
            $dbResult['userPosition'],
            $dbResult['userGroups'],
            $dbResult['userPwTag'],
            $dbResult['userPwKey'],
            $dbResult['created_at'],
            $dbResult['created_by'],
            $dbResult['updated_at'],
            $dbResult['updated_by'],
            $dbResult['user_path'],
            $dbResult['userSource'],
            $dbResult['userEmail'],
        );
    }

    public static function setValuesToUser($arrValues)
    {
        foreach ($arrValues as $key => $val) {
            self::$$key = $val;
        }
    }

    public function setVars($arrValues)
    {
        foreach ($arrValues as $key => $val) {
            $this->$key = $val;
        }
    }

    /**
     * @param string $userName
     * @param string $password
     *
     * @return false|User
     * @throws DatabaseException
     */
    public static function logIn($userName, $password)
    {
        self::checkDatabaseTable();
        $user = self::$dbConnection->getOne(self::TABLE_NAME, ['name' => $userName, 'active' => 1]);
        if (empty($user)) {
            return false;
        }

        if (password_verify($password, $user['password'])) {
            $user                       = self::dbVarsToUser($user);
            $_SESSION[self::TOKEN_NAME] = $user->id;
            return $user;
        }

        return false;
    }

    public static function logInByIP($userName)
    {
        self::checkDatabaseTable();
        $user = self::$dbConnection->getOne(self::TABLE_NAME, ['name' => $userName, 'active' => 1]);
        if (empty($user)) {
            return false;
        }

        $user                       = self::dbVarsToUser($user);
        $_SESSION[self::TOKEN_NAME] = $user->id;
        return $user;

    }

    /**
     * @return void
     */
    public static function logOut()
    {
        unset($_SESSION[self::TOKEN_NAME]);
        session_destroy();
    }

    /**
     * @return bool
     */
    public static function isLoggedIn()
    {
        return isset($_SESSION[self::TOKEN_NAME]);
    }

    /**
     * @param string $name
     *
     * @return User|false
     * @throws DatabaseException
     */
    public static function getUserbyName($name)
    {
        self::checkDatabaseTable();
        $dbResult = self::$dbConnection->getOne('KT_USERS', ['name' => $name]);
        if (!empty($dbResult)) {
            return self::dbVarsToUser($dbResult);
        }

        return false;
    }

    /**
     * @param int $userFid
     *
     * @return false|user
     * @throws DatabaseException
     */
    public static function getUserbyFid($userFid)
    {
        self::checkDatabaseTable();
        $dbResult = self::$dbConnection->getOne('KT_USERS', ['userFid' => $userFid]);
        if (!empty($dbResult)) {
            return self::dbVarsToUser($dbResult);
        }

        return false;
    }

    /**
     * @param string $user
     * @param string $password
     *
     * @throws DatabaseException
     */
    public function storeJobrouterLogin($user, $password)
    {
        $cipher = "aes-128-gcm";
        $appKey = self::getAppKey();

        if (in_array($cipher, openssl_get_cipher_methods(), false)) {
            $ivlen = openssl_cipher_iv_length($cipher);
            try {
                $iv = random_bytes($ivlen);
            } catch (Exception $e) {
                throw new RuntimeException("Fehler beim generieren des JR-Passwort Schlüssels: " . $e->getMessage());
            }
            $cipherPw = openssl_encrypt($password, $cipher, $appKey, $options = 0, $iv, $tag);
            //store $cipher, $iv, and $tag for decryption later
            $this->userPassword = $cipherPw;
            $this->userPwKey    = bin2hex($iv);
            $this->userPwTag    = bin2hex($tag);
            $this->userLogin    = $user;
            $this->saveToDb();
            return;
        }
        throw new RuntimeException("Verschlüsselungsmethode $cipher nicht verfügbar");
    }

    /**
     * Get the application key.
     *
     * @return string The application key.
     * @throws RuntimeException If there is an error generating the JR password key.
     */
    protected static function getAppKey()
    {
        $keyFile = ROOT . 'sicher' . DIRECTORY_SEPARATOR . '.jrKey';
        if (!is_file($keyFile)) {
            $keyLength = openssl_cipher_iv_length("aes-128-gcm");
            try {
                $generatedKey = random_bytes($keyLength);
            } catch (Exception $e) {
                throw new RuntimeException("Fehler beim generieren des JR-Passwort Schlüssels: " . $e->getMessage());
            }

            $savedKey = base64_encode($generatedKey);
            file_put_contents($keyFile, $savedKey);
            $appKey = $generatedKey;
        } else {
            $appKey = base64_decode(file_get_contents($keyFile));
        }
        return $appKey;
    }

    /**
     * Get the ID of the user.
     *
     * @return mixed The ID of the user.
     */
    public function getId()
    {
        return $this->id;
    }

    public function getUserFid()
    {
        return $this->userFid;
    }

    public function getUserName()
    {
        return $this->userFirstname . ' ' . $this->userLastname;
    }

    public function getUserLogin()
    {
        return !empty($this->userLogin) ? $this->userLogin : $this->name;
    }

    public function getUserSource()
    {
        return $this->userSource ?? null;
    }

    public function getUserGroups()
    {
        return $this->userGroups;
    }

    public function getUserEmail()
    {
        return $this->userEmail ?? null;
    }

    /**
     * Retrieves the Jobrouter login information.
     *
     * Decrypts the password using the specified cipher and app key.
     * If the decryption is successful, returns an associative array with the Jobrouter user and the decrypted password.
     *
     * @return array|false An associative array with the Jobrouter user and the decrypted password,
     *                     or false if the userPassword is empty.
     * @throws RuntimeException if the specified cipher is not available for encryption.
     */
    public function getJobrouterLogin()
    {
        if (empty($this->userPassword)) {
            return false;
        }
        $cipher = "aes-128-gcm";
        $appKey = self::getAppKey();

        if (in_array($cipher, openssl_get_cipher_methods(), false)) {
            $password = openssl_decrypt($this->userPassword, $cipher, $appKey, $options = 0, hex2bin($this->userPwKey), hex2bin($this->userPwTag));
            return [
                'user'     => $this->userLogin,
                'password' => $password
            ];
        }
        throw new RuntimeException("Verschlüsselungsmethode $cipher nicht verfügbar");
    }

    /**
     * Statische Admin-Prüfung auf Basis der aktuellen Klasse user.
     * Optional kann ein Benutzerobjekt oder Benutzername übergeben werden,
     * sonst wird user::$currentUser verwendet.
     */
    public static function isAdmin($user = null): bool
    {
        // Nutzerquelle bestimmen
        if ($user === null) {
            $user = self::$currentUser ?? null;
        } elseif (is_string($user)) {
            $u = self::getUserbyName($user);
            if ($u) $user = $u;
        }

        if (!$user) {
            return false;
        }

        // 1) Rolle direkt am Objekt (falls vorhanden)
        if (property_exists($user, 'role') && !empty($user->role) && strtolower((string)$user->role) === 'admin') {
            return true;
        }

        // 2) userGroups durchsuchen (JSON/Text), enthält z. B. "admin"
        if (!empty($user->userGroups)) {
            $groups = is_array($user->userGroups) ? $user->userGroups : @json_decode($user->userGroups, true);
            if (is_array($groups) && in_array('admin', array_map('strtolower', $groups), true)) {
                return true;
            }
            // Fallback: einfacher String enthält "admin"
            if (is_string($user->userGroups) && stripos($user->userGroups, 'admin') !== false) {
                return true;
            }
        }

        // 3) Fallback über Konstante ADMIN_USERS (Komma/Leerzeichen/Strichpunkt getrennt)
        if (defined('ADMIN_USERS') && ADMIN_USERS) {
            $list = array_filter(array_map('trim', preg_split('/[,;\s]+/', ADMIN_USERS)));
            $name = !empty($user->getUserLogin()) ? $user->getUserLogin() : $user->name;
            if ($name && in_array($name, $list, true)) {
                return true;
            }
        }

        return false;
    }


// ... existing code ...

    /**
     * Gibt alle Benutzergruppen mit den zugehörigen Benutzern zurück.
     *
     * @return array Assoziatives Array mit Gruppennamen als Keys und Arrays von User-Objekten als Values
     * @throws DatabaseException
     */
    public static function getUserGroupsWithUsers(): array
    {
        self::checkDatabaseTable();

        $allUsers = self::$dbConnection->getAll(self::TABLE_NAME, '*', ['active' => 1]);

        if (empty($allUsers)) {
            return [];
        }

        $groupedUsers = [];

        foreach ($allUsers as $userData) {
            $userObj = self::dbVarsToUser($userData);

            // Benutzergruppen aus userGroups-Feld extrahieren
            $userGroups = [];

            if (!empty($userData['userGroups'])) {
                // Versuche JSON zu dekodieren
                $decoded = @json_decode($userData['userGroups'], true);

                if (is_array($decoded)) {
                    $userGroups = $decoded;
                } else {
                    // Fallback: Semikolon-getrennte Liste
                    $userGroups = array_filter(array_map('trim', explode(';', $userData['userGroups'])));
                }
            }

            // Wenn keine Gruppe gefunden wurde, in "Ohne Gruppe" einsortieren
            if (empty($userGroups)) {
                $userGroups = ['Ohne Gruppe'];
            }

            // Benutzer zu jeder seiner Gruppen hinzufügen
            foreach ($userGroups as $groupName) {
                if (!empty($groupName)) {
                    if (!isset($groupedUsers[$groupName])) {
                        $groupedUsers[$groupName] = [];
                    }
                    $groupedUsers[$groupName][] = $userObj;
                }
            }
        }

        // Alphabetische Sortierung der Gruppennamen
        ksort($groupedUsers);

        return $groupedUsers;
    }

    /**
     * Gibt Benutzergruppen mit Benutzern zurück, die dem übergebenen User gemäß
     * der USERGROUPVISIBILITY_JSON Konfiguration zugeordnet sind.
     *
     * @param User $user Der Benutzer, für den die sichtbaren Gruppen ermittelt werden sollen
     *
     * @return array Assoziatives Array mit Gruppennamen als Keys und Arrays von User-Login-Namen als Values
     * @throws DatabaseException
     */
    public static function getUserGroupsWithUsersForUser($user): array
    {
        if (!$user) {
            return [];
        }

        // Benutzergruppen des übergebenen Users abrufen
        $userGroupsString = $user->getUserGroups();
        if (empty($userGroupsString)) {
            return [];
        }

        // Benutzergruppen parsen
        $myUserGroups = [];
        $decoded      = @json_decode($userGroupsString, true);
        if (is_array($decoded)) {
            $myUserGroups = $decoded;
        } else {
            $myUserGroups = array_filter(array_map('trim', explode(';', $userGroupsString)));
        }

        if (empty($myUserGroups)) {
            return [];
        }

        // Alle gruppierten Benutzer abrufen
        $allGroupedUsers = self::getUserGroupsWithUsers();

        // Visibility-Konfiguration laden
        $userGroupVisibility = json_decode(
            defined('USERGROUPVISIBILITY_JSON') ? USERGROUPVISIBILITY_JSON : '[]',
            true
        );

        $allowedUsers = [];

        // Durch die Visibility-Regeln iterieren
        foreach ($userGroupVisibility as $visibility) {
            if (!isset($visibility['parent']) || !isset($visibility['child'])) {
                continue;
            }

            $parentGroup = $visibility['parent'];
            $childGroup  = $visibility['child'];

            // Prüfen, ob der aktuelle User die Parent-Gruppe hat
            if (!in_array($parentGroup, $myUserGroups, true)) {
                continue;
            }

            // Wildcard: Alle Gruppen
            if ($childGroup === '*') {
                foreach ($allGroupedUsers as $groupName => $users) {
                    if (!isset($allowedUsers[$groupName])) {
                        $allowedUsers[$groupName] = [];
                    }
                    foreach ($users as $userObj) {
                        $login = $userObj->getUserLogin();
                        if (!in_array($login, $allowedUsers[$groupName], true)) {
                            $allowedUsers[$groupName][] = $login;
                        }
                    }
                }
            } else {
                // Spezifische Gruppe
                if (isset($allGroupedUsers[$childGroup])) {
                    if (!isset($allowedUsers[$childGroup])) {
                        $allowedUsers[$childGroup] = [];
                    }
                    foreach ($allGroupedUsers[$childGroup] as $userObj) {
                        $login = $userObj->getUserLogin();
                        if (!in_array($login, $allowedUsers[$childGroup], true)) {
                            $allowedUsers[$childGroup][] = $login;
                        }
                    }
                }
            }
        }

        // Alphabetische Sortierung
        ksort($allowedUsers);

        return $allowedUsers;
    }

    /**
     * Gibt eine flache, alphabetisch sortierte Liste aller sichtbaren Benutzer für einen Benutzer zurück.
     * Filtert Duplikate heraus, falls Benutzer in mehreren sichtbaren Gruppen sind.
     *
     * @param User $user Der Kontext-Benutzer
     *
     * @return array Array von User-Objekten, sortiert nach Name (case-insensitive)
     * @throws DatabaseException
     */
    public static function getFlatUserListForUser($user): array
    {
        // 1. Gruppierte Sichtbarkeit holen (enthält Login-Strings)
        $groupedVisibility = self::getUserGroupsWithUsersForUser($user);

        if (empty($groupedVisibility)) {
            return [];
        }

        // 2. Alle eindeutigen Login-Namen sammeln
        $uniqueLogins = [];
        foreach ($groupedVisibility as $group => $users) {
            foreach ($users as $login) {
                if (!empty($login)) {
                    $uniqueLogins[$login] = $login; // Key=Value verhindert Duplikate automatisch
                }
            }
        }

        if (empty($uniqueLogins)) {
            return [];
        }

        // 3. Alphabetisch sortieren (Case-Insensitive)
        natcasesort($uniqueLogins);

        // 4. Als Array unter dem Key 'Benutzer' zurückgeben
        // array_values sorgt dafür, dass die Keys (Logins) entfernt werden und es ein reines Array von Strings ist
        return ['Benutzer' => array_values($uniqueLogins)];
    }
}