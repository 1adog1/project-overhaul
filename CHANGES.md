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
