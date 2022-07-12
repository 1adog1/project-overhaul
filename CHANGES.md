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
