<?php
/**
 * Services definition.
 *
 * @package qtype_minispeak
 * @author  Justin Hunt - poodll.com
 */

$functions = array(
        'qtype_aitext_fetch_ai_grade' => array(
                'classname'   => 'qtype_aitext_external',
                'methodname'  => 'fetch_ai_grade',
                'description' => 'checks a response with the AI grader' ,
                'capabilities'=> 'mod/quiz:grade',
                'type'        => 'read',
                'ajax'        => true,
        ),
);