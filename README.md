# Multi Calendar Bundle for Contao CMS

Erzeugt mehrere Mini-Kalender auf einer Seite.


## Known limitations

Beim Aufruf eines Calendars für einen Zeitpunkt, zu oder nach dem es keine Events gibt generiert Contao einen 
Error 404 (page not found). Dies fangen wir zwar ab, generieren für den jeweiligen Monat aber nur einen "leeren
Platzhalter" (siehe auch unten bei Anpassungen).

Workaround: Termine (weit) in der Vergangenheit und/oder Zukunft anlegen.


## Anpassungen

Für den oben genannten "leeren Platzhalter" wird ein Template verwendet. Dieses Template kann überschrieben werden, 
indem die Datei `templates/bundles/FiedschMultiCalendarBundle/mc_empty.html.twig` angelegt und an den Bedarf angepasst
wird.


## Styling mit Bootstrap (Beispiel)

(Erstellen und) Anpassen des Templates `templates/frontend_module/multi_calendar.html.twig` 
```twig
{% extends "@Contao/frontend_module/multi_calendar.html.twig" %}
{# add bootstrap classes #}

{% block global_nav %}
    <div class="row justify-content-center">
        <div class="col-12 col-md-4">
            {% set global_nav_attributes = attrs(global_nav_attributes|default).addClass(['table', 'table-sm', 'table-borderless']) %}
            {{ parent() }}
        </div>
    </div>
{% endblock %}
    
{% block calendars %} 
    {% set calendars_attributes = attrs().addClass(['row']) %}
    {{ parent() }}
{% endblock %}
    
{% block calendar %} 
    {% set single_calendar_attributes = attrs().addClass(['col-sm-12', 'col-md-4']) %}
    {{ parent() }}
{% endblock %}
```