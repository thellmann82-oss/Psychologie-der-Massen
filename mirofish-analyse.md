# MiroFish – Quellcode-Analyse

**Repository:** https://github.com/666ghj/MiroFish  
**Analysedatum:** 2026-05-04  
**Lizenz:** AGPL-3.0  
**GitHub-Sterne:** ~59.100 | **Forks:** ~9.200

---

## 1. Projektübersicht

MiroFish ist eine KI-gestützte Multi-Agenten-Simulationsplattform, die darauf ausgelegt ist, eine hochdetaillierte digitale Parallelwelt zu konstruieren. Auf Basis von Seed-Informationen (Dokumente, Texte) generiert das System automatisch Tausende von KI-Agenten mit eigenen Persönlichkeiten, die miteinander interagieren und eine Vorhersage über zukünftige gesellschaftliche Entwicklungen liefern.

Das Projekt wird von der Shanda Group inkubiert und baut auf dem OASIS-Framework von CAMEL-AI auf.

### Anwendungsfälle

| Bereich | Beschreibung |
|---------|-------------|
| Politiktest | Simulation von Gesetzesfolgen vor der Einführung |
| Meinungsforschung | Modellierung gesellschaftlicher Reaktionen |
| Finanzprognose | Vorhersage von Marktbewegungen |
| Literaturanalyse | Vorhersage fehlender Romanenden |
| Kreativ-Sandbox | Szenario-Exploration für Einzelnutzer |

### Tech-Stack-Übersicht

```
MiroFish
├── Backend:  Python 57.6%  (Flask, CAMEL-OASIS, Zep Cloud)
└── Frontend: Vue.js 41.2%  (Vue 3, Vite, D3.js)
```

---

## 2. Gesamtarchitektur

MiroFish implementiert einen **fünfstufigen Workflow**:

```
[Dokument-Upload]
       │
       ▼
┌─────────────────┐
│ Schritt 1       │  Ontologie generieren + Wissensgraph bauen (Zep)
│ Graph-Aufbau    │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ Schritt 2       │  Entitäten extrahieren, OASIS-Agenten-Profile
│ Umgebungs-Setup │  + Simulationskonfiguration generieren (LLM)
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ Schritt 3       │  Multi-Plattform-Simulation starten
│ Simulation      │  (Twitter / Reddit / Parallel)
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ Schritt 4       │  ReACT-basierte Berichtsgenerierung
│ Bericht         │  mit Tool-gestützter Graph-Analyse
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ Schritt 5       │  Interaktive Agenten-Interviews
│ Interaktion     │
└─────────────────┘
```

---

## 3. Backend-Analyse

### 3.1 Projektstruktur

```
backend/
├── run.py                        # Einstiegspunkt
├── pyproject.toml / uv.lock      # Abhängigkeitsverwaltung (uv)
├── requirements.txt
├── app/
│   ├── __init__.py               # Flask-App-Fabrik
│   ├── config.py                 # Konfiguration & Konstanten
│   ├── api/
│   │   ├── graph.py              # Graph/Ontologie/Projekt-Endpunkte
│   │   ├── simulation.py         # Simulations-Endpunkte
│   │   └── report.py             # Bericht-Endpunkte
│   ├── models/
│   │   ├── project.py            # Projekt-Datenmodell
│   │   └── task.py               # Aufgaben-Datenmodell
│   ├── services/
│   │   ├── graph_builder.py      # Zep-Graphkonstruktion
│   │   ├── ontology_generator.py # Entitäts-/Relationsschema (LLM)
│   │   ├── oasis_profile_generator.py    # Agenten-Personas (LLM)
│   │   ├── simulation_config_generator.py # Sim-Parameter (LLM)
│   │   ├── simulation_manager.py         # Lebenszyklusverwaltung
│   │   ├── simulation_runner.py          # Prozess-Spawning
│   │   ├── simulation_ipc.py             # Dateibasierte IPC
│   │   ├── report_agent.py               # ReACT-Berichtsgenerator
│   │   ├── text_processor.py             # Textnormalisierung
│   │   ├── zep_tools.py                  # Zep-Suchutilitys
│   │   ├── zep_entity_reader.py          # Entitätsleser
│   │   └── zep_graph_memory_updater.py   # Graph-Updater
│   └── utils/
│       ├── llm_client.py         # OpenAI-kompatibler Wrapper
│       ├── file_parser.py        # Dokumentenextraktion
│       ├── logger.py             # Logging
│       ├── retry.py              # Wiederholungslogik
│       ├── locale.py             # Internationalisierung
│       └── zep_paging.py         # Zep-Paginierung
└── scripts/
    ├── run_twitter_simulation.py
    ├── run_reddit_simulation.py
    ├── run_parallel_simulation.py
    └── action_logger.py
```

### 3.2 Flask-App-Fabrik (`app/__init__.py`)

Die Anwendung verwendet das Factory-Pattern:

- **JSON-Kodierung:** `JSON_AS_ASCII = False` (chinesische Zeichen korrekt ausgeben)
- **CORS:** Aktiviert für alle `/api/*`-Routen (Ursprung: `*` – permissiv für Entwicklung)
- **Middleware:** Request/Response-Logging für alle API-Anfragen
- **Blueprints:** `graph`, `simulation`, `report` als getrennte Module registriert
- **Health-Check:** `GET /health` – Statusendpunkt
- **Cleanup:** Simulationsprozesse werden beim App-Shutdown terminiert

### 3.3 API-Endpunkte

**Graph-Management (`/api/graph/`):**

| Methode | Pfad | Funktion |
|---------|------|----------|
| POST | `/ontology/generate` | Dokument hochladen, Ontologie generieren |
| POST | `/build` | Wissensgraph in Zep aufbauen |
| GET | `/task/<task_id>` | Aufgabenstatus abfragen |
| GET | `/data/<graph_id>` | Graphdaten abrufen |
| GET/DELETE | `/project/<project_id>` | Projekt lesen/löschen |
| POST | `/project/<project_id>/reset` | Projekt zurücksetzen |

**Simulations-Management (`/api/simulation/`):**

| Methode | Pfad | Funktion |
|---------|------|----------|
| POST | `/create` | Simulation erstellen |
| POST | `/prepare` | Profile & Konfiguration generieren |
| POST | `/start` / `/stop` | Simulation starten/stoppen |
| GET | `/<id>/run-status` | Echtzeit-Metriken |
| POST | `/interview` | Agenten befragen |

**Berichtsgenerierung (`/api/report/`):**

| Methode | Pfad | Funktion |
|---------|------|----------|
| POST | `/generate` | Bericht starten |
| GET | `/<id>/progress` | Generierungsfortschritt |
| GET | `/<id>/sections` | Fertige Kapitel |
| GET | `/<id>/download` | Markdown herunterladen |
| POST | `/chat` | Berichts-Agenten befragen |

### 3.4 Services – Kernkomponenten

#### Dokumentenverarbeitung (`file_parser.py`, `text_processor.py`)

- **Unterstützte Formate:** PDF (via PyMuPDF), Markdown, TXT
- **Zeichensatzerkennung:** UTF-8 mit `charset_normalizer`/`chardet` als Fallback
- **Textsegmentierung:** Satzgrenzenerkennung, Standard 500 Zeichen mit 50 Zeichen Überlappung
- **Normalisierung:** Zeilenende-Vereinheitlichung, Leerzeichen-Kollaps

#### Ontologie-Generierung (`ontology_generator.py`)

Das System analysiert Dokumente (max. 50.000 Zeichen) und lässt ein LLM ein Schema erzeugen:
- **10 Entitätstypen** (PascalCase, z. B. `Person`, `Organization`)
- **6–10 Relationstypen** (UPPER_SNAKE_CASE, z. B. `WORKS_FOR`)
- Ausgabe: JSON-Schema + Pydantic-Modell-Python-Code
- JSON-Validierung und Deduplizierung inklusive

#### Wissensgraph-Aufbau (`graph_builder.py`)

- Erstellt Zep-Graphen mit dynamischen Entitätsklassen
- Stapelverarbeitung in Blöcken (Standard: 3 Chunks pro Batch)
- Timeout: 600 Sekunden für vollständige Graphkonstruktion
- Asynchrone Ausführung mit Fortschrittsrückmeldung

#### OASIS-Agenten-Profil-Generierung (`oasis_profile_generator.py`)

Generiert detaillierte Personas für jeden Graphen-Knoten:
- **Biografie:** 200 Zeichen (Kurzbeschreibung)
- **Ausführliche Beschreibung:** 2.000+ Zeichen
- **Demografien:** Alter, Geschlecht, MBTI, Land, Beruf
- Unterscheidung: Einzelperson vs. Gruppe
- Ausgabe: CSV (Twitter), JSON (Reddit)

#### Simulationskonfigurations-Generierung (`simulation_config_generator.py`)

Vierstufige sequenzielle LLM-Generierung:
1. Zeitkonfiguration (Dauer, Stoßzeiten, Aktivitätsmultiplikatoren)
2. Ereigniskonfiguration (Trending Topics, Startbeiträge)
3. Agenten-Batch-Konfiguration (15 Agenten pro Batch)
4. Plattformkonfiguration (Twitter/Reddit-Gewichtungen)

**Hinweis:** Standard-Stoßzeiten sind auf chinesische Arbeitszeiten ausgelegt (Peak: 19–22 Uhr, minimal: 0–5 Uhr).

#### Simulations-Ausführung (`simulation_runner.py`)

- Spawnt Unterprozesse pro Plattform (Twitter, Reddit, Parallel)
- Zeitbasierte Agenten-Aktivierung mit konfigurierbaren Peak-Stunden
- Gleichzeitige LLM-Anfragen: max. 30
- Echtzeit-Aktionsprotokoll im JSONL-Format (2-Sekunden-Polling)
- Zustandspersistenz in `run_state.json`
- Prozessgruppen-Terminierung (taskkill /T Windows / killpg Unix)

#### Berichtsgenerierung (`report_agent.py`)

Implementiert den **ReACT-Zyklus** (Thought → Action → Observation):

```
Planung: Gliederung generieren (2–5 Kapitel)
    │
    ▼
Für jedes Kapitel:
  ┌─ InsightForge    (tiefe multidimensionale Analyse)
  ├─ PanoramaSearch  (vollständiger Graph-Abruf)
  ├─ QuickSearch     (semantische Direktsuche)
  └─ InterviewAgents (Live-Agenten-Befragung)
    │
    ▼ (min. 3, max. 5 Tool-Aufrufe pro Kapitel)
Zusammenführung mit Deduplizierung
```

- Echtzeit-Kapitel-Streaming
- Strukturiertes JSON-Logging aller Agentenaktionen

#### Zep-Integration (`zep_tools.py`)

- **Hybridsuche:** BM25 (Schlüsselwort) + Semantische Vektorindizierung
- **Temporale Verfolgung:** `created_at`, `valid_at`, `invalid_at`, `expired_at`
- **Graceful Degradation:** Fallback auf lokalen Keyword-Abgleich bei API-Ausfall
- **Retry:** Exponentieller Backoff (Start: 2s, verdoppelt sich pro Versuch)

#### Inter-Prozess-Kommunikation (`simulation_ipc.py`)

- **Mechanismus:** Dateibasierte IPC via JSON-Befehls-/Antwortdateien
- **Befehlstypen:** `INTERVIEW`, `BATCH_INTERVIEW`, `CLOSE_ENV`
- UUID-identifizierte Befehle mit Polling-Mechanismus
- Timeout: 60 Sekunden
- Statusübergänge: `pending → processing → completed/failed`
- Automatische Bereinigung verarbeiteter Dateien

#### LLM-Client (`llm_client.py`)

- OpenAI-kompatibler API-Wrapper (unterstützt Alibaba Qwen, etc.)
- Standardtemperatur: 0,7 (Chat), 0,3 (strukturierte Ausgabe)
- Automatische Bereinigung von `<thinking>`-Tags (z. B. MiniMax M2.5)
- Konfigurierbare Token-Limits

### 3.5 Datenmodelle

**`Project`** (`project.py`):
```
project_id, name
status: created | ontology_generated | graph_building | graph_completed | failed
files, total_text_length, ontology, analysis_summary
graph_id, graph_build_task_id
simulation_requirement
chunk_size=500, chunk_overlap=50
error, created_at, updated_at
```

**`Task`** (`task.py`):
```
task_id, task_type
status: TaskStatus (Enum)
progress (0–100), message, result, error, metadata
progress_detail, created_at, updated_at
```

---

## 4. Frontend-Analyse

### 4.1 Tech-Stack

| Paket | Version | Zweck |
|-------|---------|-------|
| Vue | 3.5.24 | Reaktives UI-Framework |
| Vue Router | 4.6.3 | Client-seitiges Routing |
| vue-i18n | 11.3.0 | Mehrsprachigkeit |
| Axios | 1.14.0 | HTTP-Client |
| D3.js | 7.9.0 | Graphvisualisierung |
| Vite | 7.2.4 | Build-Tool & Dev-Server |

### 4.2 Routing (6 Routen)

| Route | Komponente | Funktion |
|-------|-----------|---------|
| `/` | `Home.vue` | Startseite |
| `/process/:projectId` | `MainView.vue` | Haupt-Workflow |
| `/simulation/:simulationId` | `SimulationView.vue` | Simulationsverwaltung |
| `/simulation/:simulationId/start` | `SimulationRunView.vue` | Simulationsausführung |
| `/report/:reportId` | `ReportView.vue` | Berichtsanzeige |
| `/interaction/:reportId` | `InteractionView.vue` | Agenten-Interviews |

### 4.3 Workflow-Komponenten

Die fünf `Step*.vue`-Komponenten implementieren den gesamten Workflow:

- **`Step1GraphBuild.vue`** – Dokumenten-Upload, Ontologie-Vorschau, Graph-Aufbau starten
- **`Step2EnvSetup.vue`** – Agenten-Profile und Simulationsparameter konfigurieren
- **`Step3Simulation.vue`** – Simulation starten, Echtzeit-Monitoring, Stop-Funktion
- **`Step4Report.vue`** – Berichtsgenerierung auslösen, Kapitel-Streaming verfolgen
- **`Step5Interaction.vue`** – Einzelne Agenten interaktiv befragen

### 4.4 Weitere Komponenten

- **`GraphPanel.vue`** – D3.js-basierte interaktive Wissensgraph-Visualisierung
- **`HistoryDatabase.vue`** – Projekthistorie und gespeicherte Simulationen
- **`LanguageSwitcher.vue`** – Sprachumschaltung (i18n)

---

## 5. Kernalgorithmen & Muster

### 5.1 ReACT-Pattern (Berichtsgenerierung)

```
Thought:  Was muss ich für Kapitel X herausfinden?
Action:   InsightForge("Thema X")
Observe:  Ergebnisse aus dem Wissensgraph
Thought:  Brauche weitere Perspektiven
Action:   InterviewAgents(["Agent_A", "Agent_B"])
Observe:  Agenten-Antworten
→ Kapitel schreiben
```

Erzwingt evidenzbasierte Berichte durch obligatorische Tool-Aufrufe (min. 3, max. 5 pro Kapitel).

### 5.2 Exponentieller Backoff-Retry

```python
# Zep-Integration: 2s → 4s → 8s → 16s → ...
delay = 2
while not success:
    try_request()
    if failed: sleep(delay); delay *= 2
# Fallback: lokaler Keyword-Abgleich
```

### 5.3 Hybrid-Suche (Zep)

```
Anfrage
  ├─ BM25-Index       (exakte Schlüsselwort-Treffer)
  └─ Semantic-Index   (Vektorähnlichkeit)
       │
       ▼
  Fusioniertes Ranking + Temporalfilter (only active entities)
```

### 5.4 Dateibasierte IPC

```
Flask-API                    Simulationsprozess
    │                              │
    │─── command_{uuid}.json ──►   │
    │                              │  (verarbeitet Befehl)
    │◄── response_{uuid}.json ───  │
    │                              │
    │  (Polling alle X Sekunden)   │
    │  (Timeout: 60s)              │
    │  (Dateibereinigung auto.)    │
```

Entkoppelt langläufige Simulationsprozesse vom HTTP-Server.

### 5.5 Zeitbasierte Agenten-Aktivierung

```
Stunde 0–5:   Aktivitätsmultiplikator 0,1  (nächtliche Ruhe)
Stunde 9–18:  Aktivitätsmultiplikator 1,0  (Normalbetrieb)
Stunde 19–22: Aktivitätsmultiplikator 2,5  (Abend-Peak)
```

Simuliert realistische Nutzungsmuster sozialer Medien.

---

## 6. Konfiguration & Deployment

### 6.1 Umgebungsvariablen

| Variable | Pflicht | Standard | Beschreibung |
|----------|---------|---------|--------------|
| `LLM_API_KEY` | Ja | – | LLM-Authentifizierungsschlüssel |
| `LLM_BASE_URL` | Nein | Alibaba Qwen | OpenAI-kompatibler Endpunkt |
| `LLM_MODEL_NAME` | Nein | `gpt-4o-mini` | Modellname |
| `ZEP_API_KEY` | Ja | – | Zep-Cloud-Schlüssel |
| `LLM_BOOST_API_KEY` | Nein | – | Optionaler Beschleunigungsservice |
| `FLASK_DEBUG` | Nein | `False` | Debug-Modus |

### 6.2 Konfigurationskonstanten (`config.py`)

| Konstante | Wert | Bedeutung |
|-----------|------|-----------|
| Max. Dateigröße | 50 MB | Upload-Limit |
| Chunk-Größe | 500 Zeichen | Textsegmentierung |
| Chunk-Überlappung | 50 Zeichen | Kontextkontinuität |
| OASIS-Runden | 10 | Simulationsrunden |
| Report-Tool-Calls | max. 5 | Pro Kapitel |
| Reflexionsrunden | 2 | Berichtsnachbesserung |
| Batch-Größe | 15 Agenten | Pro Generierungsschritt |

### 6.3 Deployment

**Quellcode:**
```bash
npm run setup:all   # Alle Abhängigkeiten installieren
npm run dev         # Frontend (Port 3000) + Backend (Port 5001)
```

**Docker:**
```bash
docker compose up -d
# Image: ghcr.io/666ghj/mirofish:latest
# Volume: ./backend/uploads → /app/backend/uploads
# Auto-Neustart bei Fehler
```

---

## 7. Sicherheitsbetrachtungen

| Aspekt | Umsetzung | Bewertung |
|--------|-----------|-----------|
| API-Key-Management | Nur Umgebungsvariablen, keine Hardcodierung | ✓ Gut |
| Dateivalidierung | Nur PDF, Markdown, TXT erlaubt | ✓ Gut |
| CORS | Wildcard `*` für alle Ursprünge | ⚠ Für Produktion anpassen |
| Prozessisolation | Simulationen in separaten Subprozessen | ✓ Gut |
| IPC-Bereinigung | Befehls-/Antwortdateien auto. gelöscht | ✓ Gut |
| Zeichenkodierung | UTF-8 + Fallback-Erkennung | ✓ Gut |
| Lizenz (AGPL-3.0) | Quelloffenlegungspflicht bei Netzwerkeinsatz | ⚠ Beachten |

**Empfehlungen:**
- CORS-Ursprungsliste in Produktionsumgebungen einschränken
- Rate-Limiting für API-Endpunkte ergänzen
- Authentifizierung/Autorisierung für API-Zugriff hinzufügen (aktuell nicht vorhanden)

---

## 8. Fazit & Bewertung

### Stärken

- **Modulare Architektur:** Klare Trennung von API, Services und Utilities erleichtert Wartung
- **LLM-Agnostik:** OpenAI-kompatibler Wrapper ermöglicht einfachen Modellwechsel (Qwen, GPT-4, etc.)
- **Robustheit:** Exponentieller Backoff, Graceful Degradation und Prozessisolation
- **Skalierbarkeit:** Batch-Verarbeitung, parallele Simulationsplattformen, Zep-Cloudspeicher
- **Benutzeroberfläche:** Intuitiver 5-Stufen-Workflow mit Echtzeit-Fortschritt

### Schwächen / Verbesserungspotenzial

- **Keine Authentifizierung:** Alle API-Endpunkte sind öffentlich erreichbar
- **CORS Wildcard:** Für Produktionseinsatz unsicher
- **Chinesische Zeitzone hardcodiert:** Stoßzeiten sind nicht konfigurierbar ohne Codeanpassung
- **Kein vollständiges Testpaket:** Nur vereinzelte Testskripte sichtbar
- **Dateibasierte IPC:** Für hohe Last besser durch Message-Queue ersetzen (z. B. Redis)

### Gesamtbewertung

MiroFish ist ein ambitioniertes, technisch ausgereiftes Forschungsprojekt, das modernste KI-Technologien (LLM, Wissensgraph, Multi-Agenten-Simulation) in einer kohärenten Plattform vereint. Die Architektur ist solide und erweiterbar. Für einen Produktionseinsatz müssten vor allem Authentifizierung, CORS-Härtung und eine robustere IPC-Lösung ergänzt werden.
