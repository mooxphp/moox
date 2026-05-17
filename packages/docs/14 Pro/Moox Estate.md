# Moox Branchenlösungen

Moox Branchenlösungen werden als fertige Pakete zusammengestellt, der sichtbare Teil ist das Theme mit entsprechenden Grafiken und Texten. Das ist zum einen eine Super Werbung und zum anderen ein Schnellstart.

Die Branchenlösungen kombinieren eine sinnvolle Basisfunktionalität mit speziellen Branchenlösungen (z. B. Schnittstellen). Die Branchenlösungen sind gemeinsam mit den Themes die Basis für unser Marketing.

Es gibt mehrere Arten von Paketen

1) FOSS: Eigene Implementation notwendig, sinnvolle Basis für Entwickler oder Agenturen
2) Pro: Kann von Web-affinen Nerds selbst umgesetzt werden, spricht aber auch Agenturen an, insbesondere mit Theme attraktiv weil quasi "fertig"
3) Premium: Service inklusive, die Komplett-Lösung (eventuell mit Partneragenturen)

## Moox für Ärzte, Heilpraktiker, Heilberufe

- Terminvergabe
- Software? Schnittstellen?

## Moox für andere Termin-Kunden

- Fitness-Studios
- Friseure, Kosmetik, Wellness
- Kalender, Terminplanung, Online-Bezahlung, Abo-Verwaltung, Kurskalender

## Moox für Gästehäuser, Hotels, Fewos

- Booking, Airbnb, Expedia, ...
- Software? Schnittstellen?

## Moox für Autohäuser, Werkstätten

- Cars, HSN, TSN
- Mobile, Autoscout
- Spezielles wie Marine, Camping, Nutzfahrzeuge, etc.

## Moox für Immobilien

MVP:

- Moox CMS, Moox Media, 
- Moox Shop, specially Moox Product, Moox Wishlist and Moox Inquiry
- Moox Frontend, Moox Theme Base with Property and Inquiry
- Moox Property - Product mit Immo-Feldern
- Moox OpenImmo - https://packagist.org/packages/innobrain/laravel-openimmo - http://www.openimmo.de/, damit Basiskompatibilität mit Software wie auf https://www.wp-immomakler.de/ zu sehen, denke ich jedenfalls, wie z. B. https://flowfact.de/ 
- Moox Estate, Habitat or Realty - Bundles?
- Expose ....

More - Pro:

- Moox Immoscout - https://packagist.org/packages/fehrlich/immoscout24-api-php
- Moox Idealista - https://developers.idealista.com/access-request
- https://heyflow.com/de/lp/immobilien - Funnels ... Hot Lead Generation
- Home-Staging-Galerie - direkte AI Lösung, da gibt es Plattformen und APIs
- 360 Grad mit iPhone und Co. Lösung, Embed Matterport, Ogulo und EyeSpy360?
  - https://github.com/google/marzipano - not maintained
  - https://github.com/mpetroff/pannellum - looks a bit basic
  - https://github.com/mistic100/Photo-Sphere-Viewer - looks much better! Use this!

- Demo Images Equirectangular
  - https://www.flickr.com/search/?q=equirectangular&l=4 OR https://www.flickr.com/groups/equirectangular/
  - https://commons.wikimedia.org/wiki/Category:360%C2%B0_panoramas_with_equirectangular_projection
  - https://www.freepik.com/free-photos-vectors/equirectangular-360 - AI generated
  - https://polyhaven.com/hdris/indoor

- Verbreitete Lösungen
  - **Insta360 X3**
  - **Ricoh Theta Z1**

- Video Tour oder Virtuelle Tour - Live-Video-Tour
- Termine -> Richtiges CRM

Marketing:

- Kaltaquise: Kostenfrei für die ersten Makler, die was hermachen, Frage dort nach Marketing
- Case Study erstellen: „Wie Makler X 5h pro Woche spart mit Moox Estate“
- https://www.immoxxl.de/blog/immobilienmakler-software - guter Blog, Webseitenpakete
- https://frymo.de/ - war in Google Ads beworben
- https://go.mindbox.de/immobilien - sieht top aus, ich benötige eine Agentur an meiner Seite, wenn die Basis steht

1. **Exposés (digital & PDF)**
   - Automatisch generierte PDFs mit Logo, Branding, Bildern, Grundrissen.
   - DSGVO-Hinweise, Pflichtangaben (z. B. Energieausweis).
   - Idealerweise „One-Click“-Export.
2. **Schnittstellen / Synchronisation**
   - **OpenImmo** (Standard in DE → Flowfact, onOffice, WP-ImmoMakler usw.).
   - **Immoscout24 API**, Idealista (ES), Subito (IT), Seloger (FR).
   - Multichannel: „1x einpflegen → überall online“.
3. **Kontakt & Leads**
   - Anfrageformulare mit DSGVO-Checkboxen.
   - Lead-Scoring: „heiß“ vs. „kalt“ (basierend auf Anfragen, Website-Interaktion, Tour-Aufrufen).
   - Automatisierte Bestätigungsmails.
4. **Termine & Besichtigungen**
   - Online-Terminbuchung für Interessenten.
   - Kalender-Sync (Google/Outlook/iCal).
   - Automatische Erinnerungen / Absagen.
5. **Medienmanagement**
   - Bilder (optimiert, Reihenfolge, Wasserzeichen).
   - 360°-Touren (z. B. Photo Sphere Viewer).
   - Videos (Upload oder YouTube/Vimeo-Embed).
   - Home Staging mit AI → Vorher/Nachher-Bilder.
     - **Virtual Staging AI** (US) – KI basiert, gut, aber teilweise noch ungleichmäßig.
     - **RoOomy** (NL/US) – schon länger im Markt, sehr stark in realistischer Darstellung, wird von Möbelhäusern genutzt.
     - **PadStyler** – fertige Staging-Bilder, recht etabliert, aber teuer.
     - **Styldod** – AI + menschliches Nachbearbeiten → Mischung für bessere Qualität.
     - **Interior AI** - Pieter Levels - https://interiorai.com/
6. **Monster Features**
   - 360 Tour mit Floor plan Mini Map im CI-Style
   - Umschaltbares 360 mit Home Staging
   - Own Style ... Home Staging mit eigenen Ideen
   - Flythrough images
   - Create a top-notch website for something like Dubai Properties (Fake or not) 
   - Win any award!
7. **Breakthrough**
   - https://opencv.org/ or https://hugin.sourceforge.io/ (besser aber Achtung GPL) for Stitching
   - Mobile App for Photo Upload, 360 and Video
   - https://bifrost.nativephp.com/pricing build that shit!
