# Idea

-   require Sessions, Devices and Login-Link
-   Fix the translation
-   Make it all work together

## Firewall

Soft Firewall

Das Routing muss auch die Aufgabe einer Soft-Firewall übernehmen.

-   Eine Middleware übernimmt die IP-Überprüfung und schaltet erst dann das echte Login frei
-   Ohne IP-Überprüfung wird ein optional ein Honeypot-Formular angezeigt, dessen Aktivitäten geloggt werden können
-   Ist das echte Login aktiv wird in Phase 1 einfach an WordPress weitergeroutet, dabei wird der /wordpress-Teil entfernt, so dass externe Links weiterhin funktionieren.
-   Blacklist

Config:

-   Enable Honeypot
-   Enable Logging
-   Enable Blacklist
-   Enable Bypass

CRUDs:

-   Blacklist
-   Whitelist
-   Login Log
-   Honeypot Log
-   Bypass-Tokens
