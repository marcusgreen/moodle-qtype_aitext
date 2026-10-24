# 🤖 Moodle AI Text Fragetyp

[![Moodle Plugin CI](https://github.com/marcusgreen/moodle-qtype_aitext/actions/workflows/moodle-ci.yml/badge.svg)](https://github.com/marcusgreen/moodle-qtype_aitext/actions/workflows/moodle-ci.yml)
[![GitHub Release](https://img.shields.io/github/release/marcusgreen/moodle-qtype_aitext.svg)](https://github.com/marcusgreen/moodle-qtype_aitext/releases)
[![Moodle Support](https://img.shields.io/badge/Moodle-%3E%3D%204.5-blue)](https://marketplace.moodle.com/plugins/qtype_aitext)

*von Marcus Green*

🇬🇧 **[English](./README.md)** · 🇩🇪 **Deutsch**

**AI Text** ist ein Fragetyp für Moodle, der Freitextantworten entgegennimmt und sie mit einem externen Large Language Model (LLM) bewertet, z. B. ChatGPT oder einem selbst gehosteten Ollama-Modell.

Jede Frage definiert ihren eigenen Bewertungsprompt und optional ein Bewertungsschema. Die KI beurteilt die Antworten der Lernenden also anhand der Kriterien, die Sie als Lehrkraft festlegen – und liefert sowohl eine Punktzahl als auch ein schriftliches Feedback.

Für individuelle Entwicklung und Beratung wenden Sie sich bitte an den Moodle-Partner [Catalyst EU](https://www.catalyst-eu.net/).

- **Moodle-Plugin-Verzeichnis**: <https://marketplace.moodle.com/plugins/qtype_aitext>
- **Changelog**: <https://github.com/marcusgreen/moodle-qtype_aitext/blob/main/changelog.md>
- **Weitere Dokumentation (Wiki)**: <https://github.com/marcusgreen/moodle-qtype_aitext/wiki>

## ✨ Hauptfunktionen

- **KI-gestützte Bewertung** von Freitextantworten mit Punktzahl und individuellem Feedback für die Lernenden.
- **Prompt und Bewertungsschema pro Frage**: Jede Frage legt selbst fest, wie die KI die Antwort beurteilen und Punkte vergeben soll.
- **Prompt-Tester (Response Tester)**: Prompts lassen sich direkt im Bearbeitungsformular per AJAX ausprobieren, ohne den Umweg über die Fragenvorschau.
- **Rechtschreibprüfung**: Die KI erstellt eine korrigierte Fassung der Antwort, die als Vergleich zur Originalantwort angezeigt und von der bewertenden Person nachbearbeitet werden kann.
- **Manuelle Bewertung mit KI-Unterstützung**: Das von der KI erzeugte Feedback kann einer bewertenden Person angezeigt und als Referenz genutzt werden.
- **Expertenmodus**: Stellt im Bearbeitungsformular eine Expertenmodus-Prompt-Vorlage bereit, mit der Lehrkräfte den Prompt detaillierter steuern können.
- **Mehrere KI-Backends**: Moodle Core AI Subsystem, `local_ai_manager` oder `tool_aimanager`.
- **KI-Hinweis (Disclaimer)**: Konfigurierbarer Hinweis, dass das Feedback von einer KI stammt – inklusive Platzhalter `{{model}}` für das verwendete Modell, übersetzt in die Sprache der Nutzerin bzw. des Nutzers.
- **Antwortformate**: Texteditor, reiner Text oder Festbreitenschrift (Monospace).
- **Robuste Fehlerbehandlung**: Ist der KI-Dienst nicht erreichbar, schlägt die Bewertung nicht fehl – der Versuch geht nicht verloren.
- **Moodle-App-Unterstützung**, Backup/Restore sowie XML-Import/-Export.
- **Mehrsprachig**: Oberfläche verfügbar auf Englisch und Deutsch.

## 🧠 Funktionsweise

Pro Frage geben Sie zwei Dinge vor:

1. Einen **Prompt**, der der KI erklärt, wie die Antwort zu bewerten ist.
2. Ein optionales **Bewertungsschema**, das beschreibt, wie Punkte vergeben werden.

Für die Frage *„Schreiben Sie einen englischen Satz in der Vergangenheitsform“* könnte der Prompt lauten:

> Erkläre, ob die Grammatik in diesem Text fehlerhaft ist.

Und ein Bewertungsschema könnte so aussehen:

> Vergib 10 Punkte, wenn keine Fehler vorhanden sind, die Rechtschreibung korrekt ist und der Satz in der Vergangenheitsform steht. Vergib 0 Punkte, wenn die Grammatik falsch ist. Ziehe für jedes falsch geschriebene Wort einen Punkt ab.

Beispielfragen (u. a. zur Bildinterpretation) finden Sie im Ordner [`examples/`](./examples).

## 📋 Voraussetzungen

- **Moodle 4.5** oder neuer (getestet bis Moodle 5.3).
- Zugang zur API eines externen LLM (z. B. OpenAI oder Ollama, je nach KI-Backend).
- Eines der unterstützten KI-Backends:
  - **Moodle Core AI Subsystem** (Standard, ab Moodle 4.5 enthalten)
  - [`local_ai_manager`](https://github.com/bycs-lp/moodle-local_ai_manager) (ByCS/mebis)
  - `tool_aimanager`
- Die beiden Begleit-Fragenverhalten (siehe unten).

## 🚀 Installation

### 1. Fragetyp installieren

Über das Moodle-Plugin-Verzeichnis oder per Git:

```bash
cd /pfad/zu/moodle
git clone https://github.com/marcusgreen/moodle-qtype_aitext.git question/type/aitext
```

### 2. Begleit-Fragenverhalten installieren (Pflicht)

Die Bewertung läuft über zwei eigene Fragenverhalten, statt die Kernverhalten von Moodle zu überschreiben. Ohne diese Plugins können AI-Text-Fragen **nicht bewertet** werden.

```bash
git clone https://github.com/marcusgreen/moodle-qbehaviour_immediate_for_aitext.git question/behaviour/immediate_for_aitext
git clone https://github.com/marcusgreen/moodle-qbehaviour_deferred_for_aitext.git question/behaviour/deferred_for_aitext
```

### 3. Upgrade ausführen

Als Administrator/in *Website-Administration → Mitteilungen* aufrufen oder per CLI:

```bash
php admin/cli/upgrade.php
```

Bestehende Testversuche, die das alte Verhalten `interactivecountback` verwendet haben, werden beim Upgrade automatisch migriert. Versuche anderer Fragetypen bleiben unverändert.

## ⚙️ Konfiguration

Unter *Website-Administration → Plugins → Fragetypen → AI Text* lassen sich u. a. folgende Einstellungen vornehmen:

- **KI-Backend**: Core AI Subsystem, `local_ai_manager` oder `tool_aimanager`.
- **Standard-Prompt** und **Standard-Bewertungsschema** für neue Fragen.
- **Prompt-Vorlage** und **Rollen-Prompt**, mit denen die Anfrage an die KI aufgebaut wird.
- **JSON-Prompt**, der die KI anweist, Feedback und Punktzahl in strukturierter Form zurückzugeben.
- **KI-Hinweis (Disclaimer)**, der an das Feedback angehängt wird.
- **Expertenmodus** aktivieren/deaktivieren.
- **Standard-Antwortformat** sowie die Option, ein Bewertungsschema verpflichtend zu machen.

## 🏗️ Code-Architektur (für Mitwirkende)

- **`question.php`**: Fragelogik und Anbindung an die KI-Backends.
- **`questiontype.php`** / **`edit_aitext_form.php`**: Speicherung und Bearbeitungsformular der Frage.
- **`classes/external.php`**: Webservices für Prompt-Tester und Rechtschreibprüfung.
- **`classes/form/edit_spellcheck.php`**: Formular zur Bearbeitung der Rechtschreibprüfung.
- **`classes/output/mobile.php`** und **`mobile/`**: Unterstützung der Moodle-App.
- **`amd/src/`**: JavaScript-Module (AMD) für das Bearbeitungsformular.
- **`tests/`**: PHPUnit- und Behat-Tests.

## 🙏 Credits

Vielen Dank an das ByCS-Team für das umfangreiche Feedback, die Unterstützung, Ermutigung, Tests und beigesteuerten Code seit etwa Juli 2024.

## 📜 Lizenz

Dieses Plugin steht unter der [GNU GPL v3 oder neuer](https://www.gnu.org/licenses/gpl-3.0.html).
