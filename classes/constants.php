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

namespace qtype_aitext;

/**
 * Constants class for the aitext question type.
 *
 * @package    qtype
 * @subpackage aitext
 * @copyright  2024 Justin Hunt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class constants {

    const RELEVANCE_NONE=0;
    const RELEVANCE_QTEXT=1;
    const RELEVANCE_COMPARISON=2;
    const LANGUAGES =  ['ar-ae', 'ar-sa', 'eu-es', 'bg-bg', 'hr-hr', 'zh-cn', 'cs-cz', 'da-dk', 'nl-nl', 'nl-be', 'en-us', 'en-gb',
        'en-au', 'en-nz', 'en-za', 'en-in', 'en-ie', 'en-wl', 'en-ab', 'fa-ir', 'fil-ph', 'fi-fi', 'fr-ca', 'fr-fr', 'de-de',
        'de-at', 'de-ch', 'hi-in', 'el-gr', 'he-il', 'hu-hu', 'id-id', 'is-is', 'it-it', 'ja-jp', 'ko-kr', 'lt-lt', 'lv-lv',
        'mi-nz', 'ms-my', 'mk-mk', 'no-no', 'pl-pl', 'pt-br', 'pt-pt', 'ro-ro', 'ru-ru', 'es-us', 'es-es', 'sk-sk', 'sl-si',
        'sr-rs', 'sv-se', 'ta-in', 'te-in', 'tr-tr', 'uk-ua', 'vi-vn'];

    const RESPONSE_FORMATS = ['plain','editor','monospaced','audio'];
    const EXTRA_FIELDS = ['responseformat',
        'responsefieldlines',
        'minwordlimit',
        'maxwordlimit',
        'graderinfo',
        'graderinfoformat',
        'responsetemplate',
        'responsetemplateformat',
        'maxbytes',
        'aiprompt',
        'markscheme',
        'sampleanswer',
        'model',
        'maxtime',
        'responselanguage',
        'feedbacklanguage',
        'relevance',
        'relevanceanswer'
        ];

    /**
     * The different response languages that the question type supports.
     * internal name => human-readable name.
     *
     * @return array
     */
    public static function get_languages($includeauto=false) {
        $responselanguages=[];
        foreach (self::LANGUAGES as $langcode) {
            $responselanguages[$langcode] = get_string($langcode,"qtype_aitext");
        }
        if($includeauto){
            $responselanguages['currentlanguage'] = get_string('currentlanguage',"qtype_aitext");
        }
        return $responselanguages;
    }

    /**
     * The different response formats that the question type supports.
     * internal name => human-readable name.
     *
     * @return array
     */
    public static function get_response_formats() {
        $responseformats=[];
        foreach (self::RESPONSE_FORMATS as $theformat) {
            $responseformats[$theformat] = get_string('format' . $theformat,"qtype_aitext");
        }
        return $responseformats;
    }

    /**
     * The time limits for audio recording
     * no. of seconds => human-readable name (min /secs)
     *
     * @return array
     */
    public static function get_time_limits() {
        $opts = array(
            0 => get_string("notimelimit", "qtype_aitext"),
            30 => get_string("xsecs", "qtype_aitext", '30'),
            45 => get_string("xsecs", "qtype_aitext", '45'),
            60 => get_string("onemin", "qtype_aitext"),
            90 => get_string("oneminxsecs", "qtype_aitext", '30'),
        );
        for($x=2;$x<=30;$x++){
            $opts[$x*60]=get_string("xmins", "qtype_aitext", $x);
            $opts[($x*60)+30]=get_string("xminsecs", "qtype_aitext", array('minutes' => $x, 'seconds' => 30));
        }
        return $opts;
    }

    public static function get_relevance_opts() {
        $opts = array(
            self::RELEVANCE_NONE => get_string("relevance_none", "qtype_aitext"),
            self::RELEVANCE_QTEXT => get_string("relevancetoqtext", "qtype_aitext"),
            self::RELEVANCE_COMPARISON => get_string("relevancetocomparison", "qtype_aitext")
        );
        return $opts;
    }

}