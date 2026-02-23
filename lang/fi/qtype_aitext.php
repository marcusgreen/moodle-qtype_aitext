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
 * Language strings for qtype_aitext.
 *
 * @package    qtype_aitext
 * @category   string
 * @copyright  Created with Plugin Translator by Brickfield https://www.brickfield.ie/brickfield-translator/
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'AI-teksti';
$string['acceptedfiletypes'] = 'Hyväksytyt tiedostotyypit';
$string['addsample'] = 'Lisää vastausesimerkki';
$string['aiprompt'] = 'AI-kehote';
$string['aiprompt_help'] = 'Ai Grader -ohjelman kehote. Tämä on ohje, jota tekoäly käyttää antamaan palautetta opiskelijan vastauksesta.';
$string['aipromptmissing'] = 'AI-kehote puuttuu. Syötä kehote, jonka perusteella palaute luodaan.';
$string['answerfiles'] = 'Vastaustiedostot';
$string['answertext'] = 'Vastausteksti';
$string['attachmentsoptional'] = 'Liitteet ovat valinnaisia.';
$string['automatic_spellcheck'] = 'Automaattinen oikeinkirjoituksen tarkistus';
$string['backends'] = 'AI-taustajärjestelmät';
$string['backends_text'] = 'Core AI -järjestelmä otettiin käyttöön Moodle 4.5:n yhteydessä, Local AI -järjestelmä on peräisin osoitteesta https://github.com/mebis-lp/moodle-local_ai_manager ja Tool AI -järjestelmä osoitteesta https://github.com/marcusgreen/moodle-tool_aiconnect.';
$string['cachedef_stringdata'] = 'Välimuistin merkkijonotiedot';
$string['coreaisubsystem'] = 'Ydin-AI-alijärjestelmä';
$string['defaultmarksscheme'] = 'Arviointijärjestelmä';
$string['defaultmarksscheme_setting'] = 'Tämä on uusien kysymysten oletusarvoinen pisteytysjärjestelmä. Kysymysten laatijoiden tulee muokata tätä kysymyksen mukaan.';
$string['defaultprompt'] = 'AI-kehote';
$string['defaultprompt_setting'] = 'Tämä on uusien kysymysten oletusarvoinen AI-kehote. Se kertoo AI-arvioijalle, miten opiskelijan vastausta tulee analysoida. Se on ohje, jota AI käyttää antaessaan palautetta opiskelijan vastauksesta. Kysymysten laatijoiden tulee muokata tätä kysymyksen mukaan.';
$string['deletesample'] = 'Poista näyte';
$string['disclaimer'] = 'Vastuuvapauslauseke';
$string['disclaimer_setting'] = 'Jokaiseen vastaukseen liitetty teksti, joka osoittaa, että palaute on peräisin suuresta kielimallista eikä ihmiseltä.';
$string['err_invalidbackend'] = 'Virhe: invalidbackend;';
$string['err_maxminmismatch'] = 'Enimmäissanamäärä on oltava suurempi kuin vähimmäissanamäärä.';
$string['err_maxwordlimit'] = 'Maksimisanamäärä on käytössä, mutta sitä ei ole asetettu.';
$string['err_maxwordlimitnegative'] = 'Sanojen enimmäismäärä ei voi olla negatiivinen luku.';
$string['err_minwordlimit'] = 'Vähimmäissanamäärä on käytössä, mutta sitä ei ole asetettu.';
$string['err_minwordlimitnegative'] = 'Minimisanamäärä ei voi olla negatiivinen luku.';
$string['err_parammissing'] = 'Virheelliset parametrit. Varmista, että sinulla on vastausesimerkki ja kehote.';
$string['err_retrievingfeedback'] = 'Virhe palautteen hakemisessa KI-työkalusta: {$a}';
$string['err_retrievingfeedback_checkconfig'] = 'Palautetta ei voitu hakea. AI-järjestelmän kokoonpano saattaa olla väärä, ota yhteyttä järjestelmänvalvojaan.';
$string['err_retrievingtranslation'] = 'Virhe käännöksen hakemisessa: {$a}';
$string['expertmode'] = 'Asiantuntijatila';
$string['expertmode_setting'] = 'Asiantuntijatila, kehotteen on sisällettävä [[expert]] ja [[response]]';
$string['formateditor'] = 'HTML-editori';
$string['formateditorfilepicker'] = 'HTML-editori tiedostojen valitsimella';
$string['formatmonospaced'] = 'Pelkkä teksti, kiinteä kirjasintyyppi';
$string['formatnoinline'] = 'Ei online-tekstiä';
$string['formatplain'] = 'Pelkkä teksti';
$string['get_llmmfeedback'] = 'Hanki LLM-palautetta';
$string['graderinfo'] = 'Tietoa arvioijille';
$string['graderinfoheader'] = 'Arvosanatiedot';
$string['jsonprompt'] = 'JSon-kehote';
$string['jsonprompt_setting'] = 'Ohjeet palautetun arvon muuntamiseksi json-muotoon';
$string['localaimanager'] = 'Paikallinen AI-johtaja';
$string['markprompt_required'] = 'Mark-kehote vaaditaan';
$string['markprompt_required_setting'] = 'Jos tämä asetus on valittuna, kysymystä luotaessa merkitsemistä koskeva kehote on pakollinen kenttä, ja jos se jätetään tyhjäksi, näyttöön tulee virheilmoitus.';
$string['markscheme'] = 'Arviointijärjestelmä';
$string['markscheme_help'] = 'Tämä kertoo tekoälyarvioijalle, kuinka opiskelijan vastaukselle annetaan numeerinen arvosana. Kysymyksen mahdollinen kokonaispistemäärä on tämän kysymyksen "oletuspisteet".';
$string['markschememissing'] = 'Arviointijärjestelmä puuttuu. Anna ohjeet käyttäjien syötteiden arvioimiseksi.';
$string['maxwordlimit'] = 'Enimmäissanamäärä';
$string['maxwordlimit_help'] = 'Jos vastaus edellyttää, että opiskelijat syöttävät tekstiä, tämä on enimmäismäärä sanoja, jonka kukin opiskelija saa lähettää.';
$string['maxwordlimitboundary'] = 'Tämän kysymyksen sanaraja on {$a->limit} sanaa, ja yrität lähettää {$a->count} sanaa. Lyhennä vastaustasi ja yritä uudelleen.';
$string['minwordlimit'] = 'Vähimmäissanamäärä';
$string['minwordlimit_help'] = 'Jos vastaus edellyttää, että opiskelijat syöttävät tekstiä, tämä on vähimmäissanamäärä, jonka kukin opiskelija saa lähettää.';
$string['minwordlimitboundary'] = 'Tämä kysymys vaatii vähintään {$a->limit} sanan vastauksen, mutta yrität lähettää {$a->count} sanan vastauksen. Laajenna vastaustasi ja yritä uudelleen.';
$string['model'] = 'Malli';
$string['nlines'] = '{$a} riviä';
$string['pluginname_help'] = 'Vastaaja kirjoittaa tekstin vastauksena kysymykseen. Vastausmallia voidaan tarjota. Vastaukset arvioidaan alustavasti tekoälyjärjestelmällä (esim. ChatGPT), minkä jälkeen ne voidaan arvioida manuaalisesti.';
$string['pluginname_link'] = 'kysymys/tyyppi/AI-teksti';
$string['pluginname_userfaced'] = 'Kysymystyyppi "AI-teksti" ja AI-tuettu palautteen luominen';
$string['pluginnameadding'] = 'AI-tekstikysymyksen lisääminen';
$string['pluginnameediting'] = 'AI-tekstikysymyksen muokkaaminen';
$string['pluginnamesummary'] = 'Mahdollistaa tiedoston lataamisen ja/tai online-tekstin vastauksen. Opiskelijan vastaus käsitellään konfiguroidulla tekoälyllä/suurella kielimallilla, joka palauttaa palautteen ja valinnaisesti arvosanan.';
$string['privacy::responsefieldlines'] = 'Syöttökentän (tekstikentän) koon osoittavien rivien määrä.';
$string['privacy:metadata'] = 'AI-tekstikysymystyyppinen laajennus antaa kysymysten laatijoille mahdollisuuden asettaa oletusasetukset käyttäjän mieltymyksiksi.';
$string['privacy:preference:attachments'] = 'Sallittujen liitteiden määrä.';
$string['privacy:preference:attachmentsrequired'] = 'Vaadittujen liitteiden määrä.';
$string['privacy:preference:defaultmark'] = 'Tietylle kysymykselle asetettu oletusarvoinen pisteet.';
$string['privacy:preference:disclaimer'] = 'Teksti, joka osoittaa, että palaute ja/tai arviointi on peräisin LLM:ltä';
$string['privacy:preference:maxbytes'] = 'Tiedoston enimmäiskoko.';
$string['privacy:preference:responseformat'] = 'Mikä on vastauksen muoto (HTML-editori, pelkkä teksti jne.)?';
$string['prompt'] = 'Kehote';
$string['prompt_setting'] = 'AI-järjestelmälle asetetun kehotteen kääreteksti, [responsetext], on se, mitä opiskelija on kirjoittanut vastaukseksi. Kysymyksen ai-kehotteen arvo liitetään tähän.';
$string['purposeplacedescription_feedback'] = 'Palautteen antaminen tentin palauttamisen tai uudelleenarvioinnin yhteydessä';
$string['purposeplacedescription_translate'] = 'Vastuuvapauslausekkeen ja tekoälyn tuottaman palautteen käännös käyttäjän kohdekielelle.';
$string['response'] = 'Vastaus';
$string['responsefieldlines'] = 'Syöttökentän koko';
$string['responseformat'] = 'Vastausmuoto';
$string['responseformat_setting'] = 'Opiskelijan vastauksessa käyttämä editori';
$string['responseisrequired'] = 'Pyydä opiskelijaa kirjoittamaan teksti';
$string['responsenotrequired'] = 'Tekstin syöttö on vapaaehtoista.';
$string['responseoptions'] = 'Vastausvaihtoehdot';
$string['responsetemplate'] = 'Vastausmalli';
$string['responsetemplate_help'] = 'Tähän syötetty teksti näkyy vastauksen syöttökentässä, kun uusi yritys kysymykseen alkaa.';
$string['responsetemplateheader'] = 'Vastausmalli';
$string['responsetester'] = 'Vastaustestaaja';
$string['responsetesthelp'] = 'Vastaustestin apu';
$string['responsetesthelp_help'] = 'Kun lomake tallennetaan, vain testivastaus tallennetaan, ei LLM:n palauttama arvo.';
$string['responsetests'] = 'Useiden vastausten testitulokset';
$string['sampleresponse'] = 'Esimerkkivastaus';
$string['sampleresponse_help'] = 'Esimerkkivastausta voidaan käyttää testaamaan, miten tekoälyarvioija reagoi tiettyyn vastaukseen.';
$string['sampleresponseempty'] = 'Varmista, että sinulla on AI-kehote ja vastausesimerkki ennen testausta.';
$string['sampleresponseeval'] = 'Esimerkkivastausten arviointi';
$string['sampleresponseevaluate'] = 'Arvioi vastausnäyte';
$string['showprompt'] = 'Näytä kehote';
$string['spellcheck_editor_desc'] = 'Tämä on teksti, jossa oikeinkirjoitusvirheet on korjattu tekoälyn avulla. Voit muokata tätä ehdotettua korjausta.';
$string['spellcheck_prompt'] = 'Toista teksti 1:1. Älä anna palautetta! Korjaa kuitenkin kaikki seuraavan tekstin oikeinkirjoitusvirheet:';
$string['spellcheck_student_anser_desc'] = 'Tämä on alkuperäinen opiskelijan vastaus.';
$string['spellcheckedit'] = 'Muokkaa oikeinkirjoituksen tarkistusta';
$string['spellcheckeditor'] = 'Muokkaa tekoälypohjaista oikeinkirjoituksen tarkistusta';
$string['testresponses'] = 'Testivastaukset';
$string['thedefaultmarksscheme'] = 'Vähennä pisteitä kokonaispisteistä jokaisesta kielioppi- tai oikeinkirjoitusvirheestä.';
$string['thedefaultprompt'] = 'Selitä, onko tekstissä kieliopillisia tai oikeinkirjoitusvirheitä.';
$string['toolaimanager'] = 'Työkalu AI-johtaja';
$string['translatepostfix'] = 'Käännä postfix';
$string['translatepostfix_text'] = 'Komennon lopussa on liitteenä "käännä palaute kielelle .current_language()".';
$string['use_local_ai_manager'] = 'Käytä local_ai_manager-laajennuksen tarjoamaa AI-taustaohjelmaa.';
$string['use_local_ai_manager_setting'] = 'Käytä local_ai_manager-laajennusta AI-kyselyjen käsittelyyn (täytyy olla asennettuna)';
$string['wordcount'] = 'Sanojen määrä: {$a}';
$string['wordcounttoofew'] = 'Sanojen määrä: {$a->count} , alle vaaditun määrän {$a->limit} sanoja.';
$string['wordcounttoomuch'] = 'Sanojen määrä: {$a->count} , yli {$a->limit} sanan rajan.';
