<?php

    /*
        
        This authorization method determines access based on Player, Corporation, and Alliance.
        
    */
    
    namespace Ridley\Core\Authorization\Eve;

    class AuthHandler extends \Ridley\Core\Authorization\Base\AuthBase {
        
        private $cookieName;
        
        private $accessRoles = [];
        private $isLoggedIn = false;
        private $characterStats = [];
        
        public function __construct(
            protected $authorizationLogger, 
            protected $authorizationConnection, 
            protected $authorizationVariables
        ) {
            
            $this->cookieName = $authorizationVariables["Auth Cookie Name"];
            
            $this->cleanupLogins();
            $this->cleanupSessions();
            $this->getSession();
            
            if (isset($_GET["core_action"]) and $_GET["core_action"] == "login") {
                
                $this->login("Default", $authorizationVariables["Default Scopes"]);
                
            }
            if (isset($_GET["core_action"]) and $_GET["core_action"] == "callback") {
                
                $this->receiveCallback();
                
            }
            if (isset($_GET["core_action"]) and $_GET["core_action"] == "logout") {
                
                $this->logout();
                
            }
            
        }
        
        private function determineAccessRoles() {
            
            if (in_array($this->characterStats["Character ID"], $this->authorizationVariables["Super Admins"])) {
                
                $this->accessRoles[] = "Super Admin";
                
            }
            
            $accessRequest = $this->authorizationConnection->prepare("SELECT * FROM access WHERE (type=:alliance_type AND id=:alliance_id) OR (type=:corp_type AND id=:corp_id) OR (type=:character_type AND id=:character_id)");
            $accessRequest->bindValue(":alliance_type", "Alliance");
            $accessRequest->bindParam(":alliance_id", $this->characterStats["Alliance ID"]);
            $accessRequest->bindValue(":corp_type", "Corporation");
            $accessRequest->bindParam(":corp_id", $this->characterStats["Corporation ID"]);
            $accessRequest->bindValue(":character_type", "Character");
            $accessRequest->bindParam(":character_id", $this->characterStats["Character ID"]);
            
            if ($accessRequest->execute()) {
                
                while ($accessData = $accessRequest->fetch(\PDO::FETCH_ASSOC)) {
                    
                    $foundRoles = json_decode($accessData["roles"]);
                    
                    $this->accessRoles = array_unique(array_merge($this->accessRoles, $foundRoles));
                    
                }
                
            }
            else {
                
                trigger_error("Failed to query database for roles.", E_USER_ERROR);
                
            }
            
        }
        
        private function setCharacterStats($characterID) {
            
            $idsToConvert = [];
            
            $affiliationSuccess = false;
            $affiliationCounter = 0;
            
            while (!$affiliationSuccess and $affiliationCounter < 5) {
            
                $affiliationURL = "https://esi.evetech.net/latest/characters/affiliation/?datasource=tranquility";
                
                $affiliationOptions = [
                    "http" => [
                        "ignore_errors" => true, 
                        "header" => [
                            "Content-Type: application/json",
                            "accept: application/json"
                        ],
                        "method" => "POST",
                        "content" => json_encode([$characterID])
                    ]
                ];
                
                $affiliationContext = stream_context_create($affiliationOptions);
                
                $affiliationResponse = file_get_contents($affiliationURL, false, $affiliationContext);
                
                $affiliationStatusData = $http_response_header[0];
                
                if (str_contains($affiliationStatusData, "200")) {
                
                    $affiliationResponseData = json_decode($affiliationResponse, true);
                    
                    foreach ($affiliationResponseData as $eachAffiliation) {
                        
                        foreach ($eachAffiliation as $entityType => $entityID) {
                            
                            if ($entityType == "character_id") {
                                
                                $this->characterStats["Character ID"] = $entityID;
                                $idsToConvert[] = $entityID;
                            }
                            if ($entityType == "corporation_id") {
                                
                                $this->characterStats["Corporation ID"] = $entityID;
                                $idsToConvert[] = $entityID;
                            }
                            if ($entityType == "alliance_id") {
                                
                                $this->characterStats["Alliance ID"] = $entityID;
                                $idsToConvert[] = $entityID;
                            }
                            
                        }
                        
                    }
                    
                    $affiliationSuccess = true;
                
                }
                else {
                    
                    $affiliationSuccess = false;
                    
                }
                
                $affiliationCounter++;
            
            }
            
            if (!$affiliationSuccess) {
                
                trigger_error("Affiliations call failed while building character stats.", E_USER_ERROR);
                
            }
            
            $nameSuccess = false;
            $nameCounter = 0;
            
            while (!$nameSuccess and $nameCounter < 5) {
            
                $namesURL = "https://esi.evetech.net/latest/universe/names/?datasource=tranquility";
                
                $namesOptions = [
                    "http" => [
                        "ignore_errors" => true, 
                        "header" => [
                            "Content-Type: application/json",
                            "accept: application/json"
                        ],
                        "method" => "POST",
                        "content" => json_encode($idsToConvert)
                    ]
                ];
                
                $namesContext = stream_context_create($namesOptions);
                
                $namesResponse = file_get_contents($namesURL, false, $namesContext);
                
                $namesStatusData = $http_response_header[0];
                
                if (str_contains($namesStatusData, "200")) {
                
                    $namesResponseData = json_decode($namesResponse, true);
                    
                    foreach ($namesResponseData as $eachName) {
                        
                        if ($eachName["category"] == "character") {
                            
                            $this->characterStats["Character Name"] = htmlspecialchars($eachName["name"]);
                            
                        }
                        if ($eachName["category"] == "corporation") {
                            
                            $this->characterStats["Corporation Name"] = htmlspecialchars($eachName["name"]);
                            
                        }
                        if ($eachName["category"] == "alliance") {
                            
                            $this->characterStats["Alliance Name"] = htmlspecialchars($eachName["name"]);
                            
                        }
                        
                    }
                    
                    $nameSuccess = true;
                
                }
                else {
                    
                    $nameSuccess = false;
                    
                }
                
                $nameCounter++;
            
            }
            
            if (!$nameSuccess) {
                
                trigger_error("Names call failed while building character stats.", E_USER_ERROR);
                
            }
            
        }
        
        private function cleanupSessions() {
            
            $currentTime = time();
            
            $deleteSessions = $this->authorizationConnection->prepare("DELETE FROM sessions WHERE expiration < :current_time");
            $deleteSessions->bindParam(":current_time", $currentTime);
            $deleteSessions->execute();
            
        }
        
        private function getSession() {
            
            if (isset($_COOKIE[$this->cookieName])) {
                
                $pullSession = $this->authorizationConnection->prepare("SELECT * FROM sessions WHERE id=:id");
                $pullSession->bindParam(":id", $_COOKIE[$this->cookieName]);
                $pullSession->execute();
                $sessionData = $pullSession->fetchAll();
                
                if (!empty($sessionData)) {
                    
                    $sessionData = $sessionData[0];
                    
                    if (time() < $sessionData["expiration"]) {
                    
                        $this->isLoggedIn = boolval($sessionData["isloggedin"]);
                        
                        if ($this->isLoggedIn) {
                            
                            $this->setCharacterStats($sessionData["characterid"]);
                            
                            if (time() < $sessionData["recheck"]) {
                                
                                $this->accessRoles = json_decode($sessionData["accessroles"], true);
                                
                            }
                            else {
                                
                                $this->determineAccessRoles();
                                $this->updateSession();
                                
                            }
                            
                        }
                    
                    }
                    else {
                        
                        $this->setSession();
                        
                    }
                    
                }
                else {
                    
                    $this->setSession();
                    
                }
                
            }
            else {
                
                $this->setSession();
                
            }
            
        }
        
        private function setSession() {
            
            $nullValue = null;
            
			$sessionBytes = random_bytes(64);
			$SessionID = bin2hex($sessionBytes);
			$sessionExpiration = time() + $this->authorizationVariables["Session Time"];
			setcookie($this->cookieName, $SessionID, ["expires" => $sessionExpiration, "path"=> "/", "samesite" => "Lax"]);
            
            $convertedLoginStatus = (int)$this->isLoggedIn;
            $convertedAccessRoles = json_encode($this->accessRoles);
            
            if ($this->isLoggedIn) {
                
                $convertedCharacterID = $this->characterStats["Character ID"];
                
                if (isset($this->characterStats["Character Name"])) {
                    
                    $convertedCharacterName = $this->characterStats["Character Name"];
                    
                }
                else {
                    
                    $convertedCharacterName = null;
                    
                }
                
            }
            else {
                
                $convertedCharacterID = 0;
                $convertedCharacterName = null;
                
            }
            
            $sessionRecheck = time() + $this->authorizationVariables["Auth Cache Time"];
			
			$insertSession = $this->authorizationConnection->prepare("INSERT INTO sessions (id, isloggedin, accessroles, characterid, charactername, currentpage, expiration, recheck) VALUES (:id, :isloggedin, :accessroles, :characterid, :charactername, :currentpage, :expiration, :recheck)");
			$insertSession->bindParam(":id", $SessionID);
			$insertSession->bindParam(":isloggedin", $convertedLoginStatus);
			$insertSession->bindParam(":accessroles", $convertedAccessRoles);
			$insertSession->bindParam(":characterid", $convertedCharacterID);
			$insertSession->bindParam(":charactername", $convertedCharacterName);
			$insertSession->bindParam(":currentpage", $nullValue);
			$insertSession->bindParam(":expiration", $sessionExpiration);
			$insertSession->bindParam(":recheck", $sessionRecheck);

			$insertSession->execute();
            
        }
        
        private function updateSession() {
            
            if (isset($_COOKIE[$this->cookieName])) {
            
                $convertedLoginStatus = (int)$this->isLoggedIn;
                $convertedAccessRoles = json_encode($this->accessRoles);
                
                if ($this->isLoggedIn) {
                    
                    $convertedCharacterID = $this->characterStats["Character ID"];
                    
                }
                else {
                    
                    $convertedCharacterID = 0;
                    
                }
                
                $sessionRecheck = time() + $this->authorizationVariables["Auth Cache Time"];
                
                $changeSession = $this->authorizationConnection->prepare("UPDATE sessions SET isloggedin=:isloggedin, accessroles=:accessroles, characterid=:characterid, recheck=:recheck WHERE id=:id");
                $changeSession->bindParam(":isloggedin", $convertedLoginStatus);
                $changeSession->bindParam(":accessroles", $convertedAccessRoles);
                $changeSession->bindParam(":characterid", $convertedCharacterID);
                $changeSession->bindParam(":recheck", $sessionRecheck);
                $changeSession->bindParam(":id", $_COOKIE[$this->cookieName]);

                $changeSession->execute();
            
            }
            
        }
        
        public function getLoginStatus() {
            
            return $this->isLoggedIn; 
            
        }
        
        public function getAccessRoles() {
            
            return $this->accessRoles;
            
        }
        
        public function getCharacterStats() {
            
            return $this->characterStats;
            
        }
        
        protected function loginSuccess($characterID) {
            
            if (!$this->isLoggedIn) {
            
                $this->setCharacterStats($characterID);
                $this->determineAccessRoles();
                $this->isLoggedIn = true;
                $this->setSession();
                
                $logString = "Character: " . $this->characterStats["Character Name"] . " (" . $this->characterStats["Corporation Name"] . ") " . ((isset($this->characterStats["Alliance Name"])) ? "[" . $this->characterStats["Alliance Name"] . "]" : "") . "\nAccess Roles: (" . implode(", ", $this->accessRoles) . ")";
                
                $this->authorizationLogger->make_log_entry("Login Success", "Authorization Handler", $this->characterStats["Character Name"], $logString);
                
                $returnURL = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
                
                header("Location: " . $returnURL);
                die();
            
            }
            
        }
        
        private function logout() {
            
            $this->accessRoles = [];
            $this->isLoggedIn = false;
            $this->characterStats = [];
            
            $this->setSession();
            
            $returnURL = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
            
            header("Location: " . $returnURL);
            die();
            
        }
    }

?>