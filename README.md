# plg_system_statistics

Joomla-System-Plugin, das die unsichtbaren CSS-Messpunkte für die Besucher-Statistik des
Weltspiegel Cottbus in jede Seite der öffentlichen Website einbettet.

Pro aktiviertem Merkmal wird ein Sonden-Element plus je eine Media Query pro Ausprägung erzeugt;
der Browser lädt genau das Bild, dessen Query zutrifft. Es wird nichts aus dem Endgerät
ausgelesen.

Setzt die Komponente [`com_statistics`](../com_statistics) voraus (Registry der Merkmale,
Endpunkt, Auswertung).

Verfahren, Datenmodell, Erweiterbarkeit und rechtliche Einordnung: siehe
[`docs/STATISTIK.md`](../docs/STATISTIK.md) im Projekt-Repository.
