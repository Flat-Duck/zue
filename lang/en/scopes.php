<?php

/*
 * The management scope screens: coverage, context and the people on them.
 *
 * Both files carry the same keys; TranslationCoverageTest fails if they drift.
 */

return [
    'title' => 'Management scopes',
    'subtitle' => 'Who each person may see, and for which business process',
    'create' => 'New scope',
    'edit' => 'Edit scope',
    'name' => 'Scope name',
    'name_placeholder' => 'e.g. Gas Plant 103A',
    'context' => 'Context',
    'context_hint' => 'Why this scope exists. The same person can hold a different scope for each context.',
    'managers' => 'Managers',
    'managers_hint' => 'Everyone listed here sees the whole scope. They do not divide it between them.',
    'coverage' => 'Coverage',
    'coverage_hint' => 'Values inside a dimension are alternatives; dimensions narrow each other. Field in (103A, 103D) is both fields, while Field 103A with Department PROD is production staff at 103A only.',
    'fields' => 'Fields',
    'departments' => 'Departments',
    'centers' => 'Cost centres',
    'named_employees' => 'Named individuals',
    'named_employees_hint' => 'Added on top of the filters above, wherever they work.',
    'covers_everyone' => 'Covers every employee',
    'covers_everyone_hint' => 'Ignores the filters below and covers the whole company.',
    'carves_out_managers' => 'Exclude managers and people signed for elsewhere',
    'carves_out_managers_hint' => 'For time sheets: a supervisor drops out of the pool they manage and is signed for by whoever names them. A dispatcher wants this off, because supervisors fly too.',
    'job_title' => 'Job title filter',
    'job_title_placeholder' => 'e.g. Nurse',
    'job_title_hint' => 'Only employees whose job title matches exactly.',
    'print_header' => 'Printed sheet heading',
    'print_header_hint' => 'Titles the printed sheet. It never changes who the scope covers.',
    'print_field' => 'Heading field',
    'print_department' => 'Heading department',
    'print_center' => 'Heading cost centre',
    'priority' => 'Priority',
    'is_active' => 'Active',
    'covers' => 'Covers',
    'everyone' => 'Everyone',
    'nobody' => 'Nobody',
    'all_managers' => 'All managers',
    'all_contexts' => 'All contexts',
    'none_selected' => 'None',
    'employee_count' => ':count employees',
    'no_scopes' => 'No management scopes yet.',
    'confirm_delete' => 'Delete this scope? The people on it will stop seeing the employees it covers.',
    'errors_empty' => 'A scope must cover something: choose at least one field, department, cost centre or person — or tick "covers every employee".',
    'errors_self_managed' => 'A manager cannot be named as someone they manage in the same scope.',
];
