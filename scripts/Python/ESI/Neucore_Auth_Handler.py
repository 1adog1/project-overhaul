import base64
import requests
import json
import time

class NeucoreAuthHandler:

    authURL = "https://login.eveonline.com/v2/oauth/token"

    def __init__(self, databaseConnection, app_id, app_secret, app_url, login_type):
        
        self.databaseConnection = databaseConnection
        
        self.login_type = login_type
        
        self.url = app_url
        raw_auth_header = str(app_id) + ":" + app_secret
        self.auth_header = "Bearer " + base64.urlsafe_b64encode(raw_auth_header.encode("utf-8")).decode()
    
    #This method is incompatible with the "core.default" login_type!
    def getLoginCharacters(self, retries = 0):
        
        headers = {"Authorization" : self.auth_header, "accept": "application/json", "Content-Type": "application/json"}

        for retryCounter in range(retries + 1):

            core_request = requests.get(
                self.url + "api/app/v1/esi/eve-login/" + str(self.login_type) + "/token-data", 
                headers=headers
            )
            
            if core_request.status_code == requests.codes.ok:
                
                response = json.loads(core_request.text)

                return response
                
            elif core_request.status_code == 404:
                
                return None
            
            elif retryCounter == retries:
                
                return None
            
    def getLoginCharacterIDs(self, retries = 0):
        
        headers = {"Authorization" : self.auth_header, "accept": "application/json", "Content-Type": "application/json"}

        for retryCounter in range(retries + 1):

            core_request = requests.get(
                self.url + "api/app/v1/esi/eve-login/" + str(self.login_type) + "/characters", 
                headers=headers
            )
            
            if core_request.status_code == requests.codes.ok:
                
                response = json.loads(core_request.text)

                return response
                
            elif core_request.status_code == 404:
                
                return None
            
            elif retryCounter == retries:
                
                return None
    
    def getAccessToken(self, character_id, retries = 0):
        
        tokenData = self.pullToken(character_id)
        
        if tokenData["Status"] == "Success":
        
            return tokenData["Access Token"]
        
        elif tokenData["Status"] == "Out of Date":
        
            return self.refreshToken(character_id, retries)
        
        elif tokenData["Status"] == "Fail":
            
            return self.refreshToken(character_id, retries)
    
    def pullToken(self, character_id):
        
        returnData = {"Status": "Fail", "Access Token": None}
        
        databaseCursor = self.databaseConnection.cursor(buffered=True)
        
        timeToCheck = int(time.time()) + 15
        
        pullStatement = "SELECT DISTINCT accesstoken, recheck FROM coretokens WHERE type=%s AND characterid=%s"
        databaseCursor.execute(pullStatement, (self.login_type, character_id))
        
        for pulledAccessToken, recheckTime in databaseCursor:
            
            if recheckTime <= timeToCheck:
                
                returnData["Status"] = "Out of Date"
                
            else:
                
                returnData["Access Token"] = pulledAccessToken
                returnData["Status"] = "Success"
        
        databaseCursor.close()
        
        return returnData
    
    def UpdateToken(self, character_id, access_token, recheck):
        
        databaseCursor = self.databaseConnection.cursor(buffered=True)
        
        updateStatement = "REPLACE INTO coretokens (type, characterid, accesstoken, recheck) VALUES (%s, %s, %s, %s)"
        databaseCursor.execute(updateStatement, (self.login_type, character_id, access_token, recheck))
        
        self.databaseConnection.commit()
        databaseCursor.close()
    
    def refreshToken(self, character_id, retries = 0):
        
        headers = {"Authorization" : self.auth_header, "accept": "application/json", "Content-Type": "application/json"}

        for retryCounter in range(retries + 1):
            
            core_request = requests.get(
                self.url + "api/app/v1/esi/access-token/" + str(character_id), 
                params={"eveLoginName": self.login_type}, 
                headers=headers
            )
            
            if core_request.status_code == requests.codes.ok:
                
                response = json.loads(core_request.text)
                
                self.UpdateToken(character_id, response["token"], response["expires"])
                    
                return response["token"]
                
            elif core_request.status_code in [204, 404]:
                
                return False
            
            elif retryCounter == retries:
                
                return False
            
    def cleanupTokens(self):
    
        databaseCursor = self.databaseConnection.cursor(buffered=True)
    
        cleanupStatement = "DELETE FROM coretokens WHERE recheck <= %s"
        currentTime = int(time.time())
        
        databaseCursor.execute(cleanupStatement, (currentTime,))
        
        self.databaseConnection.commit()
        databaseCursor.close()
    