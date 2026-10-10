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

namespace qtype_aitext\local;

use tool_usertours\helper;
use tool_usertours\tour as usertour;

/**
 * Tests for the bundled question editing user tour.
 *
 * @package    qtype_aitext
 * @copyright  2026 Marcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \qtype_aitext\local\tour
 */
final class tour_test extends \advanced_testcase {
    #[\Override]
    protected function setUp(): void {
        global $DB;
        parent::setUp();
        $this->resetAfterTest();
        // Start from a site where the tour has never been imported.
        $DB->delete_records('tool_usertours_tours', ['name' => tour::NAME]);
        unset_config('tourversion', 'qtype_aitext');
    }

    /**
     * Get the imported tours.
     *
     * @return array tour records keyed by id
     */
    private function get_imported_tours(): array {
        global $DB;
        return $DB->get_records('tool_usertours_tours', ['name' => tour::NAME]);
    }

    public function test_install_imports_tour(): void {
        tour::install();

        $records = $this->get_imported_tours();
        $this->assertCount(1, $records);
        $usertour = usertour::load_from_record(reset($records));

        $this->assertEquals('/question/bank/editquestion/question.php%', $usertour->get_pathmatch());
        $this->assertEquals(['#id_aiprompt'], $usertour->get_filter_values('cssselector'));
        $this->assertCount(9, $usertour->get_steps());
        // Ships disabled so admins opt in.
        $this->assertFalse($usertour->is_enabled());
        $this->assertEquals(tour::VERSION, get_config('qtype_aitext', 'tourversion'));
    }

    public function test_install_is_idempotent(): void {
        tour::install();
        tour::install();
        // Even with the version record gone, an existing tour is not duplicated.
        unset_config('tourversion', 'qtype_aitext');
        tour::install();

        $this->assertCount(1, $this->get_imported_tours());
    }

    public function test_deleted_tour_is_not_recreated(): void {
        tour::install();
        $records = $this->get_imported_tours();
        usertour::load_from_record(reset($records))->remove();

        tour::install();

        $this->assertCount(0, $this->get_imported_tours());
    }

    public function test_tour_strings_exist(): void {
        global $CFG;
        $json = json_decode(file_get_contents($CFG->dirroot . '/question/type/aitext/tours/qtype_aitext_edit.json'));
        $this->assertNotNull($json);
        $this->assertEquals(tour::NAME, $json->name);

        $strings = [$json->name, $json->description];
        foreach ($json->steps as $step) {
            $strings[] = $step->title;
            $strings[] = $step->content;
        }
        $stringmanager = get_string_manager();
        foreach ($strings as $string) {
            $this->assertTrue(helper::is_language_string_from_input($string), $string);
            [$identifier, $component] = explode(',', $string);
            $this->assertTrue($stringmanager->string_exists($identifier, $component), $string);
        }
    }
}
