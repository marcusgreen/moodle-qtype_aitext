<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Strings for component 'qtype_aitext', language 'de'
 *
 * @package    qtype_aitext
 * @subpackage aitext
 * @copyright  2024 Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['acceptedfiletypes'] = 'Akzeptierte Dateitypen';

$string['aiprompt'] = 'AI-Eingabeaufforderung';
$string['aiprompt_help'] = 'Eine Eingabeaufforderung für den AI Grader. Dies ist der Leitfaden, den AI verwendet, um eine Rückmeldung über die Schülerantwort zu geben.

**Experten-Modus:** Wenn Sie {{response}} in Ihrem Prompt verwenden, wird dieser als vollständige Prompt-Vorlage verwendet und ersetzt die Admin-Vorlage. Verfügbare Platzhalter: {{response}}, {{questiontext}}, {{defaultmark}}, {{markscheme}}, {{jsonprompt}}, {{language}}, {{role}}.';
$string['aipromptmissing'] = 'Der Ai-Prompt fehlt. Bitte geben Sie einen Prompt ein, auf dessen Grundlage das Feedback generiert wird.';
$string['answerfiles'] = 'Antwortdateien';
$string['answertext'] = 'Antworttext';
$string['attachmentsoptional'] = 'Anhänge sind optional';
$string['automatic_spellcheck'] = 'Automatische Rechtschreibprüfung';
$string['cachedef_stringdata'] = 'Cachedef stringdata';
$string['defaultmarksscheme'] = 'Markierungsschema';
$string['defaultmarksscheme_setting'] = 'Dies wird das Standard-Bewertungsschema für neue Fragen sein. Fragenautoren sollten dieses Schema an die jeweilige Frage anpassen.';
$string['defaultprompt'] = 'AI-Aufforderung';
$string['defaultprompt_setting'] = 'Dies ist die Standard-KI-Eingabeaufforderung für neue Fragen. Er sagt dem AI-Bewerter, wie er die Antwort des Schülers analysieren soll. Es ist der Leitfaden, den AI verwendet, um eine Rückmeldung über die Schülerantwort zu geben. Fragenautoren sollten dies an die jeweilige Frage anpassen.';
$string['defaultprompttemplate'] = '=== ROLLE ===
{{role}}

=== AUFGABENSTELLUNG ===
{{questiontext}}

=== BEWERTUNGSKRITERIEN ===
{{aiprompt}}

=== PUNKTEVERGABE ===
Maximale Punktzahl: {{defaultmark}}
{{markscheme}}

=== ZU BEWERTENDE SCHÜLERANTWORT ===
{{response}}

=== AUSGABEFORMAT ===
{{jsonprompt}}

=== SPRACHE ===
Antworte auf {{language}}.';
$string['defaultroleprompt'] = 'Du bist ein erfahrener Lehrer, der Schülerantworten fair und konstruktiv bewertet. Gib hilfreiches Feedback, das dem Schüler beim Lernen hilft.';
$string['deprecated'] = '(Veraltet - verwenden Sie stattdessen die Prompt-Vorlage)';
$string['disclaimer'] = 'Haftungsausschluss';
$string['disclaimer_setting'] = 'Text, der an jede Antwort angehängt wird und angibt, dass das Feedback von einem Large Language Model und nicht von einem Menschen stammt';
$string['err_airesponsefailed'] = 'Fehler: {$a}';
$string['err_maxminmismatch'] = 'Die maximale Wortgrenze muss größer sein als die minimale Wortgrenze';
$string['err_maxwordlimit'] = 'Maximales Wortlimit ist aktiviert, aber nicht gesetzt';
$string['err_maxwordlimitnegative'] = 'Maximales Wortlimit kann keine negative Zahl sein';
$string['err_minwordlimit'] = 'Minimum word limit is enabled but is not set';
$string['err_minwordlimitnegative'] = 'Minimales Wortlimit kann keine negative Zahl sein';
$string['err_parammissing'] = 'Ungültige Parameter. Stellen Sie sicher, dass Sie eine Beispiel-Antwort und einen Prompt eingegeben haben.';
$string['err_retrievingfeedback'] = 'Fehler beim Abrufen des Feedbacks vom KI-Tool: {$a}';
$string['err_retrievingtranslation'] = 'Fehler beim Abrufen der Übersetzung: {$a}';
$string['expertmodeconfirm'] = 'Dies ersetzt den aktuellen Prompt durch die Expertenmodus-Vorlage.<br><br><strong>Was ist der Expertenmodus?</strong><br>Im Expertenmodus haben Sie die volle Kontrolle über den gesamten KI-Prompt. Die Admin-Vorlage wird ignoriert und Ihr Prompt wird direkt an die KI gesendet.<br><br><strong>Verfügbare Platzhalter:</strong><ul><li><code>{{response}}</code> - Die Schülerantwort (erforderlich zur Aktivierung des Expertenmodus)</li><li><code>{{questiontext}}</code> - Der Fragetext</li><li><code>{{markscheme}}</code> - Das Bewertungsschema</li><li><code>{{defaultmark}}</code> - Maximal erreichbare Punktzahl</li><li><code>{{language}}</code> - Die Sprache für die Antwort</li><li><code>{{jsonprompt}}</code> - Anweisungen für das JSON-Ausgabeformat</li><li><code>{{role}}</code> - Die Rollenbeschreibung</li></ul><strong>Hinweis:</strong> Der Platzhalter <code>{{role}}</code> fügt den vom Admin definierten Rollen-Prompt ein. Sie können entweder diesen Platzhalter verwenden oder Ihre eigene Rollenbeschreibung direkt in Ihren Prompt schreiben.<br><br>Fortfahren?';
$string['formateditor'] = 'HTML-Editor';
$string['formateditorfilepicker'] = 'HTML-Editor mit Dateipicker';
$string['formatmonospaced'] = 'Einfacher Text, Schriftart monospaced';
$string['formatnoinline'] = 'Kein Online-Text';
$string['formatplain'] = 'Einfacher Text';
$string['get_llmmfeedback'] = 'LLM-Feedback abrufen';
$string['graderinfo'] = 'Informationen für Beurteiler';
$string['graderinfoheader'] = 'Informationen für Beurteiler';
$string['jsonprompt'] = 'JSon-Prompt';
$string['jsonprompt_setting'] = 'Anweisungen, die gesendet werden, um den zurückgegebenen Wert in json zu konvertieren';
$string['markscheme'] = 'Markierungsschema';
$string['markscheme_help'] = 'Damit wird dem KI-Bewerter mitgeteilt, wie er die Antwort des Schülers mit einer numerischen Note bewerten soll. Die mögliche Gesamtpunktzahl ist die \'Standardnote\' dieser Frage';
$string['markschememissing'] = 'Das Benotungsschema fehlt. Bitte geben Sie eine Aufforderung ein, wie die Benutzereingabe zu markieren ist';
$string['maxwordlimit'] = 'Maximales Wortlimit';
$string['maxwordlimit_help'] = 'Wenn die Antwort die Eingabe von Text durch die Schüler erfordert, ist dies die maximale Anzahl von Wörtern, die jeder Schüler eingeben darf.';
$string['minwordlimitboundary'] = 'Diese Frage erfordert eine Antwort von mindestens {$a->limit} Wörtern, und Sie versuchen, {$a->count} Wörter zu übermitteln. Bitte erweitern Sie Ihre Antwort und versuchen Sie es erneut.';
$string['model'] = 'Model';
$string['nlines'] = '{$a} Zeilen';
$string['nomarkscheme'] = 'Kein Bewertungsschema angegeben. Setze Punkte auf null.';
$string['pluginname'] = 'AI Text';
$string['pluginname_help'] = 'Als Antwort auf eine Frage gibt der Befragte Text ein. Es kann eine Antwortvorlage bereitgestellt werden. Die Antworten werden von einem KI-System (z. B. ChatGPT) vorbewertet und können dann manuell bewertet werden.';
$string['pluginname_link'] = 'Frage/Typ/AI Text';
$string['pluginnameadding'] = 'Hinzufügen einer KI-Text-Frage';
$string['pluginnameediting'] = 'Bearbeiten einer AI-Text-Frage';
$string['pluginnamesummary'] = 'Ermöglicht die Beantwortung eines Datei-Uploads und/oder eines Online-Textes. Die Antwort des Schülers wird vom konfigurierten AI/Large-Sprachmodell verarbeitet, das ein Feedback und optional eine Note zurückgibt.';
$string['privacy::responsefieldlines'] = 'Anzahl der Zeilen, die die Größe des Eingabefeldes (Textarea) angeben.';
$string['privacy:preference:attachments'] = 'Anzahl der erlaubten Anhänge.';
$string['privacy:preference:attachmentsrequired'] = 'Anzahl der erforderlichen Anhänge.';
$string['privacy:preference:defaultmark'] = 'Die Standardmarkierung, die für eine bestimmte Frage gesetzt wurde.';
$string['prompt_setting'] = 'Wrapper text for the prompt set to the AI System, [responsetext] is whatever the student entered as an answer. Der KI-Prompt-Wert aus der Frage wird an diesen Text angehängt';
$string['prompttemplate'] = 'Prompt-Vorlage';
$string['prompttemplate_setting'] = 'Die strukturierte Vorlage für den KI-Prompt. Verwenden Sie Platzhalter: {{role}}, {{questiontext}}, {{aiprompt}}, {{defaultmark}}, {{markscheme}}, {{response}}, {{jsonprompt}}, {{language}}. Lassen Sie einen Abschnitt leer, um ihn wegzulassen.';
$string['prompttester'] = 'Prompt-Tester';
$string['responsefieldlines'] = 'Größe des Eingabefeldes';
$string['responseformat'] = 'Antwortformat';
$string['responseformat_setting'] = 'Der Editor, den der Schüler bei der Beantwortung verwendet';
$string['responseisrequired'] = 'Den Schüler zur Texteingabe auffordern';
$string['responsenotrequired'] = 'Die Texteingabe ist optional';
$string['responseoptions'] = 'Antwortmöglichkeiten';
$string['responsetemplate'] = 'Antwortvorlage';
$string['responsetemplate_help'] = 'Jeder Text, der hier eingegeben wird, wird im Antwort-Eingabefeld angezeigt, wenn ein neuer Versuch für die Frage gestartet wird.';
$string['responsetemplateheader'] = 'Antwortvorlage';
$string['roleprompt'] = 'Rollen-Prompt';
$string['roleprompt_setting'] = 'Die Rollenbeschreibung für die KI. Dies teilt der KI mit, welche Rolle sie bei der Bewertung einnehmen soll.';
$string['sampleanswer'] = 'Beispielantwort';
$string['sampleanswer_help'] = 'Die Beispielantwort kann verwendet werden, um zu testen, wie der KI-Bewerter auf eine bestimmte Antwort reagieren wird.';
$string['sampleanswerempty'] = 'Vergewissern Sie sich, dass Sie eine KI-Eingabeaufforderung und eine Beispielantwort haben, bevor Sie den Test durchführen.';
$string['sampleanswerevaluate'] = 'Beispielantwort auswerten';
$string['showprompt'] = 'Eingabeaufforderung anzeigen';
$string['spellcheck_editor_desc'] = 'Dies ist der Text, in dem die Rechtschreibfehler von der KI korrigiert wurden. Sie können diesen Korrekturvorschlag bearbeiten.';

$string['spellcheck_prompt'] = 'Gib den Text ohne strukturelle Änderung 1:1 wieder. Verzichte auf ein Feedback. Aber korrigiere alle Rechtschreibfehler im nachfolgenden Text: ';
$string['spellcheck_student_anser_desc'] = 'Dies ist die ursprüngliche Antwort des Schülers';
$string['spellcheckedit'] = 'Rechtschreibprüfung bearbeiten';
$string['spellcheckeditor'] = 'Rechtschreibprüfung der KI bearbeiten';
$string['thedefaultmarksscheme'] = 'Ziehe für jeden Grammatik- oder Rechtschreibfehler einen Punkt von der Gesamtpunktzahl ab.';
$string['thedefaultprompt'] = 'Erklären Sie, ob etwas mit der Grammatik und der Rechtschreibung im Text nicht stimmt.';
$string['useexpertmodetemplate'] = 'Expertenmodus-Vorlage verwenden';
$string['wordcount'] = 'Wortanzahl: {$a}';
$string['wordcounttoofew'] = 'Wortanzahl: {$a->count}, weniger als die erforderlichen {$a->limit} Wörter.';
$string['wordcounttoomuch'] = 'Wortanzahl: {$a->count}, mehr als das Limit von {$a->limit} Wörtern.';
