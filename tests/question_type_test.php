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

use PHPUnit\Framework\ExpectationFailedException;
use qtype_aitext;
use SebastianBergmann\RecursionContext\InvalidArgumentException;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/question/type/aitext/questiontype.php');


/**
 * Unit tests for the aitext question type class.
 *
 * @package    qtype_aitext
 * @copyright  2013 The Open University
 * @author     Marcus Green 2023
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class question_type_test extends \advanced_testcase {
    /**
     * Always aitext
     *
     * @var mixed
     */
    protected $qtype;

    protected function setUp(): void {
        parent::setUp();
        $this->qtype = new qtype_aitext();
    }

    protected function tearDown(): void {
        parent::tearDown();
        $this->qtype = null;
    }

    /**
     * Get data skeleton
     * @todo consolidate into another earlier function
     *
     * @return \stdClass
     */
    protected function get_test_question_data() {
        $q = new \stdClass();
        $q->id = 1;
        return $q;
    }
    /**
     * Expanded version of name
     * @todo confirm and perhaps put more detail into this comment
     *
     * @covers ::name()
     *
     * @return void
     * @throws InvalidArgumentException
     * @throws ExpectationFailedException
     */
    public function test_name(): void {
        $this->assertEquals($this->qtype->name(), 'aitext');
    }
    /**
     * Does can_analyse_response work (it will always be false for this qtype)
     *
     * @covers ::can_analyse_responses()
     *
     * @return void
     */
    public function test_can_analyse_responses(): void {
        $this->assertFalse($this->qtype->can_analyse_responses());
    }

    /**
     * An estimate of the score a student would get by guessing randomly.
     * Which unlike a multi choice or similar would be zero or very close to.
     * Used by statistics calculation rather than the actual qtype.
     *
     * @covers ::get_radom_guess_score()
     *
     * @return void
     */
    public function test_get_random_guess_score(): void {
        $q = $this->get_test_question_data();
        $this->assertEquals(0, $this->qtype->get_random_guess_score($q));
    }

    /**
     * Test get_possible_responses
     *
     * @return void
     * @covers ::get_possible_responses()
     */
    public function test_get_possible_responses(): void {
        $q = $this->get_test_question_data();
        $this->assertEquals([], $this->qtype->get_possible_responses($q));

    }
}
