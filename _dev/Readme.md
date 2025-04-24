# Onlineshop-Module.de

## Neues Modul aus dem Modul Template erstellen

### 1. Kopieren und Dateinamen ändern

1. Modultemplate Verzeichnis kopieren
2. Kopiertes Verzeichnis umbenennen (z.B. gc_neuesmodul)
3. Modul Haupt-Datei den selben Namen geben (z.B. gc_neuesmodul.php)
4. .git Verzeichnis löschen
5. /translations/de-DE/ModulesGcmoduletemplateAdmin.de-DE.xml umbenennen zu /translations/de-DE/ModulesGcneuesmodulAdmin.de-DE.xml
6. /translations/de-DE/ModulesGcmoduletemplateShop.de-DE.xml umbenennen zu /translations/de-DE/ModulesGcneuesmodulShop.de-DE.xml
7. Einträge in changelog.txt entfernen
8. /vendor/autoload.php löschen
9. In Modulhauptdatei die Modulbeschreibung anpassen und den ExampleTrait entfernen
10. In /src/Settings.php alle Einstellungen anpassen

### 2. Suchen / Ersetzen

Diese Ersetzungen müssen *Case Sensitive* in genau dieser Reihenfolge gemacht werden:

1. GC_ModuleTemplate  -> Modulname mit GC z.B. 'GC_NeuesModul'
2. GcModuleTemplate   -> Modulname mit GC z.B. 'GcNeuesModul'
3. ModuleTemplate     -> Modulname ohne GC z.B. 'NeuwsModul'
4. gc_moduletemplate  -> Modulname technisch lowercase z.B. 'gc_neuesmodul'.
5. GC Module Template -> Modulname ausgeschrieben mit Prefix z.B. 'GC Neues Modul'.
6. Module Template    -> Modulname ausgeschrieben z.B. 'Neues Modul'.
7. Gcmoduletemplate   -> Modulname für Übersetzungen z.B. 'Gcneuesmodul'.
8. moduletemplate     -> Modulname ohne GC z.B. 'neuesmodul' für symfony services

### 3. Composer installieren

Generiert Autoload Datei

```
composer install
```

### 4. Übersetungen

Alle Übersetzungen im Backoffice durchführen.
Export der Übersetzungsdateien und diese in das Modul kopieren /translations/de-DE

Wenn es nur ein Modulupgrade ist anstatt eines komplett neuen Moduls:
1. Die Dateien /translations/de-DE/ModulesGcmoduletemplateAdmin.de-DE.xml und /translations/de-DE/ModulesGcmoduletemplateShop.de-DE.xml kopieren
2. die Datei /modules/gc_moduletemplate/_dev/xlf.php einmal durchlaufen lassen

### 5. Modul fertig machen:

Wenn das Modul fertig programmiert ist folgende Schritte ausführen:

#### Installiert in jedes Verzeichnis eine index.php

```
composer run autoindex
```

#### Setzt in jeder Datei den Lizenz-Header

```
composer run set-license-header
```

#### Führt Coding Standards Script aus

```
composer run cs-fixer
```

#### Führt PHPStan Script aus

```
composer run phpstan
```


#### Entfernt alle Dev-Dependencies

```
composer install --no-dev
```
