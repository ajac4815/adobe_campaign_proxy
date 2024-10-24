# Adobe Campaign Proxy

The purpose of this module is to provide integration points with the API for an Adobe Campaign instance to perform common operations (creating/getting profiles, creating subscriptions, sending messages, etc.).

## IMPORTANT: Set Up

The module expects the following environment variables to be available:

- ADOBE_API_KEY: Also referred to as the client id in the developer console.
- ADOBE_CLIENT_SECRET: The API secret key associated with a project.
- ADOBE_ORG: The org ID name (this will be different for each environment). This is NOT the ID number.

See https://experienceleague.adobe.com/en/docs/campaign-standard/using/working-with-apis/about-campaign-standard-apis/setting-up-api-access for more information. This module expects an OAuth 2 project type.

## Webform Handler

The module includes a Webform submission handler to allow for Webform based subscriptions.

### IMPORTANT: Required configuration

To be able to use the Webform handler, the following is required:

- A field (ideally hidden) with key `service` that has the service ID string that users will subscribe to.
- A field with key `email` that is required.
