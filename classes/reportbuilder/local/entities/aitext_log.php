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
 * AI text log entity
 *
 * @package    qtype_aitext
 * @copyright  2024 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace qtype_aitext\reportbuilder\local\entities;

use core_reportbuilder\local\entities\base;
use core_reportbuilder\local\filters\date;
use core_reportbuilder\local\filters\text;
use core_reportbuilder\local\helpers\format;
use core_reportbuilder\local\report\column;
use core_reportbuilder\local\report\filter;
use lang_string;


/**
 * AI text log entity
 */
class aitext_log extends base {
    /**
     * Database tables that this entity uses and their default aliases
     *
     * @return array
     */
    protected function get_default_table_aliases(): array {
        return [
            'qtype_aitext_log' => 'qal',
        ];
    }

    /**
     * The default title for this entity
     *
     * @return lang_string
     */
    protected function get_default_entity_title(): lang_string {
        return new lang_string('aitextlog', 'qtype_aitext');
    }

    /**
     * Returns list of database tables that this entity uses
     *
     * @return string[]
     */
    protected function get_default_tables(): array {
        return [
            'qtype_aitext_log',
        ];
    }
    /**
     * Initialise the entity
     *
     * @return base
     */
    public function initialise(): base {
        $columns = $this->get_all_columns();
        foreach ($columns as $column) {
            $this->add_column($column);
        }

        $filters = $this->get_all_filters();
        foreach ($filters as $filter) {
            $this->add_filter($filter);
        }

        return $this;
    }

    /**
     * Returns list of all available columns
     *
     * @return column[]
     */
    protected function get_all_columns(): array {
        $tablealias = $this->get_table_alias('qtype_aitext_log');

        $columns = [];
        $columns[] = (new column(
            'username',
            new lang_string('username', 'core'),
            $this->get_entity_name()
        ))
            ->add_field("(SELECT username FROM {user} WHERE id = " . $this->get_table_alias('qtype_aitext_log') . ".userid)", 'username')
            ->set_type(column::TYPE_TEXT)
            ->set_is_sortable(true);
            $columns[] = (new column(
                'questionname',
                new lang_string('questionname', 'qtype_aitext'),
                $this->get_entity_name()
            ))
            ->add_field("(SELECT name FROM {question} WHERE id = " . $this->get_table_alias('qtype_aitext_log') . ".aitext)", 'questiontext')
            ->set_type(column::TYPE_TEXT)
            ->set_is_sortable(false);

        // Prompt column.
        $columns[] = (new column(
            'prompt',
            new lang_string('prompt', 'qtype_aitext'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_LONGTEXT)
            ->add_fields("{$tablealias}.prompt")
            ->set_is_sortable(false);

        // Regex column.
        $columns[] = (new column(
            'regex',
            new lang_string('regex', 'qtype_aitext'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_LONGTEXT)
            ->add_fields("{$tablealias}.regex")
            ->set_is_sortable(false);

        // Time created column.
        $columns[] = (new column(
            'timecreated',
            new lang_string('timecreated', 'core'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TIMESTAMP)
            ->add_fields("{$tablealias}.timecreated")
            ->set_is_sortable(true)
            ->set_callback([format::class, 'userdate']);

        return $columns;
    }

    /**
     * Return list of all available filters
     *
     * @return filter[]
     */
    protected function get_all_filters(): array {
        $tablealias = $this->get_table_alias('qtype_aitext_log');

        $filters = [];

        // Prompt filter.
        $filters[] = (new filter(
            text::class,
            'prompt',
            new lang_string('prompt', 'qtype_aitext'),
            $this->get_entity_name(),
            "{$tablealias}.prompt"
        ))
            ->add_joins($this->get_joins());

        // Regex filter.
        $filters[] = (new filter(
            text::class,
            'regex',
            new lang_string('regex', 'qtype_aitext'),
            $this->get_entity_name(),
            "{$tablealias}.regex"
        ))
            ->add_joins($this->get_joins());

        // Time created filter.
        $filters[] = (new filter(
            date::class,
            'timecreated',
            new lang_string('timecreated', 'core'),
            $this->get_entity_name(),
            "{$tablealias}.timecreated"
        ))
            ->add_joins($this->get_joins());

        return $filters;
    }
}
