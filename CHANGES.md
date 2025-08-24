# Minor Version Update Stone – 1 – 0

## Neucore Authentication
- Created a `NeucoreAuthHandler` class in the included `ESI` Python library.
- Added Python and PHP methods for getting Access Tokens directly from Neucore. These tokens are cached in a new `coretokens` database table.
    - `getAccessToken` in Python's `ESI.NeucoreAuthHandler`
    - `getNeucoreAccessToken` in PHP's `Ridley\Core\Authorization\Neucore\AuthHandler`
- Added Python and PHP methods for cleaning up old access tokens in `coretokens`.
    - `cleanupTokens` in Python's `ESI.NeucoreAuthHandler`
    - `cleanupNeucoreAccessTokens` in PHP's `Ridley\Core\Authorization\Neucore\AuthHandler`
- Added Python and PHP methods for getting a list of characters for a particular Neucore login. 
    - `getLoginCharacterIDs` and `getLoginCharacters` in Python's `ESI.NeucoreAuthHandler`
    - `getNeucoreLoginCharacterIDs` and `getNeucoreLoginCharacters` in PHP's `Ridley\Core\Authorization\Neucore\AuthHandler`

## ESI
- Changed versioning scheme to the new `X-Compatibility-Date`.
- Fixed deprecated implicitly nullable argument in `Ridley\Objects\ESI\Base`.

## Database
- Changed a bunch of `TEXT` types to fixed-size types like `ENUM`, `BIGINT`, and `VARCHAR`.
- All default tables now have primary keys.

# Major Version Update Stone – 0 – 0

## Authentication
- Neucore Groups are now gathered and saved with a session. 
- `Core Groups` are now exposed via the Dependency Manager. An empty array will be returned when used with Eve Authentication.

## Database
- The `sessions` table now contains the `TEXT` column `coregroups`.

## ESI
- PHP and Python ESI Handlers now expose Status Code and Response Headers for ESI Requests. 

## Page Handling
- `PageHandler` no longer logs first-time connections to the homepage.
- Fixed deprecated code involving passing null to the subject argument of preg_split.

## Documentation
- Created a `docs` top-level directory and populated it with various (incomplete) pages.

## Page APIs
- Created `Ridley\Core\Exceptions\UserInputException` which is caught, logged by `SiteCore`, and sets the HTTP status code accordingly.

# Minor Version Update Clay-1-0

## Errors and Exceptions
- Converted `trigger_error`s to `throw`s across the app.
- Created a custom `ESIException` for the ESI Object.

## Logging
- Removed extraneous `htmlspecialchars` when logging to DB and handling errors.

## ESI Compliance
- `/search/` endpoint replaced with authenticated `/characters/{character_id}/search/` endpoint.

## Bugfixes
- Removed the unused `MaxTableRows` config variable.
