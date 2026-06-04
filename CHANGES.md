# Major Version Update Brick - 0 - 0

## Documentation
- Updated various elements of `README.md`.
- Added an `app_name` option to `VERSIONING`.
- No more EM-Dash delimiters in `VERSIONING`, they cause problems with Python requests.

## Config
- Added support for Environment Variables.
- Moved `VERSIONING` to `/config`.
- `config/config.php` now builds version information from `VERSIONING`.
- `Version Variables` are now exposed by the `DependencyManager`.
- Added a `ClientContactInfo` config option which affects the ESI User Agent.
- Created a new `OverhaulConfig` module for Python that loads common config and version data.

## Security
- Added the HttpOnly flag to session cookies.

## ESI Handlers
- `Ridley\Objects\ESI\Handler` and `ESI.Handler` now require a `versionVariables` parameter.
    - Updated all existing objects across the framework.
- The cache now uses a subject rather than access token as part of the key. 
- Added methods to update an access token.
- Added an ESI User Agent header.
- Added the `/characters/{character_id}/location/` endpoint to `Ridley\Objects\ESI\Methods`.
    - The framework does not require it, however it is helpful for testing that the PHP and Python ESI Handlers are consistent with each other.

## Bugfixes
- `config/config.php` now correctly parses into a multidimensional array.
- Fixed several variable and method typos.

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
