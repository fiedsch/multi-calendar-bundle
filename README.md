# Multi Calendar Bundle for Contao CMS

Erzeugt mehrere Mini-Kalender auf einer Seite.


## Noch fehlende Funktionalität

* Navigation (zurück, vor) analog dem Contao Calendar
* Optionales Setzen des Datums (Note: ein manueller Aufruf mit `month`-Parameter in der URL -- Bsp.: 
   http://example.com/calendar.html?month=202301 funktioniert als Alternative)


## Known limitations

Beim Aufruf eines Calendars für einen Zeitpunkt, zu oder nach dem es keine Events gibt generiert Contao einen 
Error 404 (page not found). Dies fangen wir zwar ab, generieren für den jeweiligen Monat aber nur einen leeren
Platzhalter (siehe auch unten bei "Anpassungen").

Workaround: einen Termin (weit) in der Zukunft anlegen.


## Anpassungen

Für den oben genannten "leeren Platzhalter" wird ein Template verwendet. Dieses Template kann überschrieben werden, 
indem die Datei `templates/bundles/FiedschMultiCalendarBundle/mc_empty.html.twig` angelegt und an den Bedarf angepasst
wird.
