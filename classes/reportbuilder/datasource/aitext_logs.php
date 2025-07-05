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
 * AI text logs datasource
 *
 * @package    qtype_aitext
 * @copyright  2024 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace qtype_aitext\reportbuilder\datasource;

use core_reportbuilder\datasource;
use core_reportbuilder\local\entities\user;
use qtype_aitext\reportbuilder\local\entities\aitext_log;

/**
 * AI text logs datasource
 */
class aitext_logs extends datasource {

    /**
     * Return user friendly name of the report source
     *
     * @return string
     */
    public static function get_name(): string {
        return get_string('aitextlogs', 'qtype_aitext');
    }

    /**
     * Initialise report
     */
    protected function initialise(): void {
        $aitextlogentity = new aitext_log();
        $aitextlogalias = $aitextlogentity->get_table_alias('qtype_aitext_log');

        $this->set_main_table('qtype_aitext_log', $aitextlogalias);
        $this->add_entity($aitextlogentity);

        // Join the user entity.
        $userentity = new user();
        $useralias = $userentity->get_table_alias('user');
        $this->add_entity($userentity
            ->add_join("LEFT JOIN {user} {$useralias} ON {$useralias}.id = {$aitextlogalias}.userid"));

        // Add report elements from each of the entities we added to the report.
        $this->add_all_from_entities();
    }

    /**
     * Return the columns that will be added to the report upon creation
     *
     * @return string[]
     */
    public function get_default_columns(): array {
        return [
            'aitext_log:questionname',
            'aitext_log:prompt',
            'aitext_log:regex',
            'aitext_log:timecreated',
        ];
    }

    /**
     * Return the filters that will be added to the report upon creation
     *
     * @return string[]
     */
    public function get_default_filters(): array {
        return [
            'user:fullname',
            'aitext_log:timecreated',
        ];
    }

    /**
     * Return the conditions that will be added to the report upon creation
     *
     * @return string[]
     */
    public function get_default_conditions(): array {
        // return [
        //     'aitext_log:timecreated',
        // ];
        return [];
    }
}
