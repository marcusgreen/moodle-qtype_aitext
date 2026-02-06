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

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/question/type/aitext/db/upgradelib.php');
require_once($CFG->dirroot . '/question/engine/tests/helpers.php');

/**
 * Unit tests for the legacy expert mode prompt migration.
 *
 * @package    qtype_aitext
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     ::qtype_aitext_migrate_legacy_expert_prompt
 * @covers     ::qtype_aitext_upgrade_migrate_legacy_prompts
 */
final class upgradelib_test extends \advanced_testcase {
    /**
     * Data provider for prompt migration testing.
     *
     * @return array Test cases with input, expected output, and description.
     */
    public static function migration_provider(): array {
        return [
            'empty string' => ['', ''],
            'only expert flag' => ['[[expert]]', ''],
            'only response' => ['[[response]]', '{{response}}'],
            'simple expert prompt' => [
                '[[expert]] Prüfe diese Antwort: [[response]]',
                'Prüfe diese Antwort: {{response}}',
            ],
            'with questiontext' => [
                '[[expert]] Bewerte [[response]] zur Frage [[questiontext]]',
                'Bewerte {{response}} zur Frage {{questiontext}}',
            ],
            'with userlang becomes language' => [
                '[[expert]] Check: [[response]] [[userlang]]',
                'Check: {{response}} {{language}}',
            ],
            'all placeholders' => [
                '[[expert]] Q: [[questiontext]] A: [[response]] L: [[userlang]]',
                'Q: {{questiontext}} A: {{response}} L: {{language}}',
            ],
            'multiple response placeholders' => [
                '[[expert]] First: [[response]] Second: [[response]]',
                'First: {{response}} Second: {{response}}',
            ],
            'new syntax unchanged' => [
                '{{response}} for {{questiontext}}',
                '{{response}} for {{questiontext}}',
            ],
            'normal prompt unchanged' => [
                'Normal prompt without placeholders',
                'Normal prompt without placeholders',
            ],
            'whitespace cleanup' => [
                '[[expert]]   Check   [[response]]   errors',
                'Check {{response}} errors',
            ],
            'with newlines' => [
                "[[expert]]\nCheck:\n[[response]]",
                'Check: {{response}}',
            ],
            'preserves language override' => [
                '[[expert]] [[response]] [[language=fr]]',
                '{{response}} [[language=fr]]',
            ],
        ];
    }

    /**
     * Test prompt migration with various input patterns.
     *
     * @dataProvider migration_provider
     * @covers ::qtype_aitext_migrate_legacy_expert_prompt
     * @param string $input The legacy prompt.
     * @param string $expected The expected migrated prompt.
     */
    public function test_migrate_legacy_prompt(string $input, string $expected): void {
        $this->assertEquals($expected, qtype_aitext_migrate_legacy_expert_prompt($input));
    }

    /**
     * Test the full database migration including idempotency.
     *
     * @covers ::qtype_aitext_upgrade_migrate_legacy_prompts
     */
    public function test_upgrade_migrate_legacy_prompts(): void {
        global $DB;
        $this->resetAfterTest();

        // Create test questions.
        $generator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $category = $generator->create_question_category();

        $generator->create_question('aitext', null, ['category' => $category->id]);
        $generator->create_question('aitext', null, ['category' => $category->id]);
        $generator->create_question('aitext', null, ['category' => $category->id]);

        // Set up legacy and modern prompts.
        $ids = array_keys($DB->get_records('qtype_aitext', [], '', 'id'));
        $DB->set_field('qtype_aitext', 'aiprompt', '[[expert]] Grade [[response]] for [[questiontext]]', ['id' => $ids[0]]);
        $DB->set_field('qtype_aitext', 'aiprompt', '[[expert]] Check: [[response]] [[userlang]]', ['id' => $ids[1]]);
        $DB->set_field('qtype_aitext', 'aiprompt', 'Modern prompt without legacy syntax', ['id' => $ids[2]]);

        // First migration should update 2 records.
        $this->assertEquals(2, qtype_aitext_upgrade_migrate_legacy_prompts());

        // Verify correct migration.
        $expected1 = 'Grade {{response}} for {{questiontext}}';
        $expected2 = 'Check: {{response}} {{language}}';
        $expected3 = 'Modern prompt without legacy syntax';
        $this->assertEquals($expected1, $DB->get_field('qtype_aitext', 'aiprompt', ['id' => $ids[0]]));
        $this->assertEquals($expected2, $DB->get_field('qtype_aitext', 'aiprompt', ['id' => $ids[1]]));
        $this->assertEquals($expected3, $DB->get_field('qtype_aitext', 'aiprompt', ['id' => $ids[2]]));

        // Second migration should be idempotent (0 records).
        $this->assertEquals(0, qtype_aitext_upgrade_migrate_legacy_prompts());
    }
}
