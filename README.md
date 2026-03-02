# directory-listing

## Konfiguracja w kontenerze GHCR

Po uruchomieniu obrazu z GHCR konfigurujesz ikony tylko przez folder `./ikony`.

- SVG wrzucasz do `./ikony`
- Plik konfiguracji trzymasz jako `./ikony/icons.json`

W kontenerze folder jest montowany do `assets/icons/custom`, a aplikacja domyślnie ładuje `assets/icons/custom/icons.json` (jeśli istnieje), w przeciwnym razie używa `config/icons.json`.
Przy starcie kontenera brakujący `./ikony/icons.json` jest automatycznie tworzony z domyślnego `config/icons.json` i ustawiany jako edytowalny z hosta (logika w entrypoint obrazu).

## Customowe ikony po ścieżce

W pliku `icons.json` (docelowo `./ikony/icons.json`) możesz ustawić własne ikony dla konkretnych folderów i plików przez sekcję `paths`.

- Klucz: ścieżka względna (bez `content/`, np. `folder1`, `README.md`, `folder1/restauracja.php`)
- Wartość: ikona (np. `bi-folder2-open`, `my-icon.svg`, `/assets/icons/moj-folder.svg`)

Uwaga: prefiks `content/` jest opcjonalny (stare wpisy nadal działają).
Jeśli używasz Docker Compose i montujesz własne ikony z `./ikony`, trafiają one do `assets/icons/custom`.
Możesz podać `custom/moj-folder.svg` albo samą nazwę `moj-folder.svg` (najpierw sprawdzany jest katalog `custom`, potem domyślny `assets/icons`).

Przykład:

```json
{
  "paths": {
    "folder1": "bi-folder2-open",
    "folder1/test.php": "bi-filetype-php",
    "folder1/zdjecia": "custom/folder-zdjecia.svg"
  }
}
```
