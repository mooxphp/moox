# Idea

See https://github.com/rappasoft/laravel-authentication-log

-   Stores or updates the Known Device for Users when a user logs in
-   Stores the device into the session, if the session-table exists
-   Users can be Moox Users, Moox Press or any other model
-   Uses [GeoLite-DB](Uses https://github.com/P3TERX/GeoLite.mmdb) to geolocate users by their IP-address
-   Multiple models are supported by config
-   Allows to show and edit User Devices in Filament

Next:

-   Drop a device and ->delete ->cascade to all sessions
-   Sends the user a notification about the login on an unknown device
-   Sends the user a request (Magic Link) to authenticate the new device
-   Can set the expiry of a session to a extremely high value (like years) on known devices
-   Can give the users opportunity to log in with a simple PIN on known devices
-   Can be audited - e. g. send a mail depending on a specific thing ... admin logs in with new device from outside germany
-   Cleans up orphaned devices
-   Notification, Slack etc?
