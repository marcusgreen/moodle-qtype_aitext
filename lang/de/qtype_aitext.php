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
 * Strings for component 'qtype_aitext', language 'en'
 *
 * @package    qtype_aitext
 * @subpackage aitext
 * @copyright  2024 Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['acceptedfiletypes'] = 'Akzeptierte Dateitypen';

$string['aiprompt'] = 'AI-Eingabeaufforderung';
$string['aiprompt_help'] = 'Eine Eingabeaufforderung für den Ai Grader. Dies ist der Leitfaden, den AI verwendet, um eine Rückmeldung über die Schülerantwort zu geben.';
$string['aipromptmissing'] = 'Der Ai-Prompt fehlt. Bitte geben Sie einen Prompt ein, auf dessen Grundlage das Feedback generiert wird.';
$string['answerfiles'] = 'Antwortdateien';
$string['answertext'] = 'Antworttext';
$string['attachmentsoptional'] = 'Anhänge sind optional';
$string['cachedef_stringdata'] = 'Cachedef stringdata';
$string['defaultmarksscheme'] = 'Markierungsschema';
$string['defaultmarksscheme_setting'] = 'Dies wird das Standard-Bewertungsschema für neue Fragen sein. Fragenautoren sollten dieses Schema an die jeweilige Frage anpassen.';
$string['defaultprompt'] = 'AI-Aufforderung';
$string['defaultprompt_setting'] = 'Dies ist die Standard-KI-Eingabeaufforderung für neue Fragen. Er sagt dem AI-Bewerter, wie er die Antwort des Schülers analysieren soll. Es ist der Leitfaden, den AI verwendet, um eine Rückmeldung über die Schülerantwort zu geben. Fragenautoren sollten dies an die jeweilige Frage anpassen.';
$string['disclaimer'] = 'Haftungsausschluss';
$string['disclaimer_setting'] = 'Text, der an jede Antwort angehängt wird und angibt, dass das Feedback von einem Large Language Model und nicht von einem Menschen stammt';
$string['err_airesponsefailed'] = 'Fehler: {$a}';
$string['err_maxminmismatch'] = 'Die maximale Wortgrenze muss größer sein als die minimale Wortgrenze';
$string['err_maxwordlimit'] = 'Maximales Wortlimit ist aktiviert, aber nicht gesetzt';
$string['err_maxwordlimitnegative'] = 'Maximales Wortlimit kann keine negative Zahl sein';
$string['err_minwordlimit'] = 'Minimum word limit is enabled but is not set';
$string['err_minwordlimitnegative'] = 'Minimales Wortlimit kann keine negative Zahl sein';
$string['err_retrievingfeedback'] = 'Fehler beim Abrufen des Feedbacks vom KI-Tool: {$a}';
$string['err_retrievingtranslation'] = 'Fehler beim Abrufen der Übersetzung: {$a}';
$string['err_parammissing'] = 'Ungültige Parameter. Stellen Sie sicher, dass Sie eine Beispiel-Antwort und einen Prompt eingegeben haben.';
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
$string['pluginname'] = 'AI Text';
$string['pluginname_help'] = 'Als Antwort auf eine Frage gibt der Befragte Text ein. Es kann eine Antwortvorlage bereitgestellt werden. Die Antworten werden von einem KI-System (z. B. ChatGPT) vorbewertet und können dann manuell bewertet werden.';
$string['pluginname_link'] = 'Frage/Typ/AI Text';
$string['pluginnameadding'] = 'Hinzufügen einer KI-Text-Frage';
$string['pluginnameediting'] = 'Bearbeiten einer AI-Text-Frage';
$string['pluginnamesummary'] = 'Ermöglicht die Beantwortung eines Datei-Uploads und/oder eines Online-Textes. Die Antwort des Schülers wird vom konfigurierten AI/Large-Sprachmodell verarbeitet, das ein Feedback und optional eine Note zurückgibt.';
$string['privacy::responsefieldlines'] = 'Anzahl der Zeilen, die die Größe des Eingabefeldes (Textarea) angeben.';
$string['privacy:metadata'] = 'AI Text question type plugin allows question authors to set default options as user preferences.';
$string['privacy:preference:attachments'] = 'Anzahl der erlaubten Anhänge.';
$string['privacy:preference:attachmentsrequired'] = 'Anzahl der erforderlichen Anhänge.';
$string['privacy:preference:defaultmark'] = 'Die Standardmarkierung, die für eine bestimmte Frage gesetzt wurde.';
$string['prompt_setting'] = 'Wrapper text for the prompt set to the AI System, [responsetext] is whatever the student entered as an answer. Der KI-Prompt-Wert aus der Frage wird an diesen Text angehängt';
$string['prompttester'] = 'Prompt-Tester';
$string['responsefieldlines'] = 'Größe des Eingabefeldes';
$string['responseformat'] = 'Antwortformat';
$string['responseformat_setting'] = 'Der Editor, den der Schüler bei der Beantwortung verwendet';
$string['responseoptions'] = 'Antwortmöglichkeiten';
$string['responsenotrequired'] = 'Texteingabe ist optional';
$string['responseisrequired'] = 'Den Schüler zur Texteingabe auffordern';
$string['responsenotrequired'] = 'Die Texteingabe ist optional';
$string['responseoptions'] = 'Antwortmöglichkeiten';
$string['responsetemplate'] = 'Antwortvorlage';
$string['responsetemplate_help'] = 'Jeder Text, der hier eingegeben wird, wird im Antwort-Eingabefeld angezeigt, wenn ein neuer Versuch für die Frage gestartet wird.';
$string['responsetemplateheader'] = 'Antwortvorlage';
$string['sampleanswer'] = 'Beispielantwort';
$string['sampleanswer_help'] = 'Die Beispielantwort kann verwendet werden, um zu testen, wie der KI-Bewerter auf eine bestimmte Antwort reagieren wird.';
$string['sampleanswerempty'] = 'Vergewissern Sie sich, dass Sie eine KI-Eingabeaufforderung und eine Beispielantwort haben, bevor Sie den Test durchführen.';
$string['sampleanswerevaluate'] = 'Beispielantwort auswerten';
$string['showprompt'] = 'Eingabeaufforderung anzeigen';
$string['thedefaultmarksscheme'] = 'Ziehe für jeden Grammatik- oder Rechtschreibfehler einen Punkt von der Gesamtpunktzahl ab.';
$string['thedefaultprompt'] = 'Erklären Sie, ob etwas mit der Grammatik und der Rechtschreibung im Text nicht stimmt.';
$string['untestedquestionbehaviour'] = 'Ungetestetes Frageverhalten';
$string['wordcount'] = 'Wortanzahl: {$a}';
$string['wordcounttoofew'] = 'Wortanzahl: {$a->count}, weniger als die erforderlichen {$a->limit} Wörter.';
$string['wordcounttoomuch'] = 'Wortanzahl: {$a->count}, mehr als das Limit von {$a->limit} Wörtern.';
$string['automatic_spellcheck'] = 'Automatische Rechtschreibprüfung';
$string['spellcheckedit'] = 'Rechtschreibprüfung bearbeiten';
$string['spellcheckeditor'] = 'Rechtschreibprüfung der KI bearbeiten';
$string['spellcheck_editor_desc'] = 'Dies ist der Text, in dem die Rechtschreibfehler von der KI korrigiert wurden. Sie können diesen Korrekturvorschlag bearbeiten.';
$string['spellcheck_student_anser_desc'] = 'Dies ist die ursprüngliche Antwort des Schülers';


$string['spellcheck_prompt'] = 'Gib den Text ohne strukturelle Änderung 1:1 wieder. Verzichte auf ein Feedback. Aber korrigiere alle Rechtschreibfehler im nachfolgenden Text: ';
