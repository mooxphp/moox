Moox Firewall muss wieder eine Middleware sein, die vor allem anderen läuft und damit auch keine Sessions unterstützt.

Sie müsste aber DB verwenden können .... damit speichern wir die IPs in eine Whitelist table mit definiertem Ablauf nach ... hmm, sagen wir 24 Stunden per config

Die festen IPs können in derselben Tabelle sein, aber eben als permanent markiert ... 

Es sollte eine delete all temp geben, bzw. die permanent IPs müssen nochmal über eine Extra-Abfrage geschützt werden.

Alternative wären mehrere Tabellen ...

Die IPs können dann auch einem User bzw. einer Session zugeordnet werden, weil wir sie ja später in die Session packen könnten ... 

Alles noch ein wenig fragil.







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