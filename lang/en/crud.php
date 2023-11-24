<?php

return [
    'common' => [
        'actions' => 'Actions',
        'create' => 'Create',
        'edit' => 'Edit',
        'update' => 'Update',
        'new' => 'New',
        'cancel' => 'Cancel',
        'attach' => 'Attach',
        'detach' => 'Detach',
        'save' => 'Save',
        'delete' => 'Delete',
        'delete_selected' => 'Delete selected',
        'search' => 'Search...',
        'back' => 'Back to Index',
        'are_you_sure' => 'Are you sure?',
        'no_items_found' => 'No items found',
        'created' => 'Successfully created',
        'saved' => 'Saved successfully',
        'removed' => 'Successfully removed',
    ],

    'administrations' => [
        'name' => 'Administrations',
        'index_title' => 'Administrations List',
        'new_title' => 'New Administration',
        'create_title' => 'Create Administration',
        'edit_title' => 'Edit Administration',
        'show_title' => 'Show Administration',
        'inputs' => [
            'name' => 'Name',
        ],
    ],

    'centers' => [
        'name' => 'Centers',
        'index_title' => 'Centers List',
        'new_title' => 'New Center',
        'create_title' => 'Create Center',
        'edit_title' => 'Edit Center',
        'show_title' => 'Show Center',
        'inputs' => [
            'name' => 'Name',
        ],
    ],

    'departments' => [
        'name' => 'Departments',
        'index_title' => 'Departments List',
        'new_title' => 'New Department',
        'create_title' => 'Create Department',
        'edit_title' => 'Edit Department',
        'show_title' => 'Show Department',
        'inputs' => [
            'name' => 'Name',
            'administration_id' => 'Administration',
        ],
    ],

    'flights' => [
        'name' => 'Flights',
        'index_title' => 'Flights List',
        'new_title' => 'New Flight',
        'create_title' => 'Create Flight',
        'edit_title' => 'Edit Flight',
        'show_title' => 'Show Flight',
        'inputs' => [
            'type' => 'Type',
            'date' => 'Date',
            'time' => 'Time',
        ],
    ],

    'locations' => [
        'name' => 'Locations',
        'index_title' => 'Locations List',
        'new_title' => 'New Location',
        'create_title' => 'Create Location',
        'edit_title' => 'Edit Location',
        'show_title' => 'Show Location',
        'inputs' => [
            'name' => 'Name',
            'description' => 'Description',
        ],
    ],

    'passengers' => [
        'name' => 'Passengers',
        'index_title' => 'Passengers List',
        'new_title' => 'New Passenger',
        'create_title' => 'Create Passenger',
        'edit_title' => 'Edit Passenger',
        'show_title' => 'Show Passenger',
        'inputs' => [
            'name' => 'Name',
            'company' => 'Company',
            'number' => 'Number',
            'nationality' => 'Nationality',
        ],
    ],

    'residences' => [
        'name' => 'Residences',
        'index_title' => 'Residences List',
        'new_title' => 'New Residence',
        'create_title' => 'Create Residence',
        'edit_title' => 'Edit Residence',
        'show_title' => 'Show Residence',
        'inputs' => [
            'name' => 'Name',
            'type' => 'Type',
        ],
    ],

    'rooms' => [
        'name' => 'Rooms',
        'index_title' => 'Rooms List',
        'new_title' => 'New Room',
        'create_title' => 'Create Room',
        'edit_title' => 'Edit Room',
        'show_title' => 'Show Room',
        'inputs' => [
            'number' => 'Number',
            'beds' => 'Beds',
            'residence_id' => 'Residence',
        ],
    ],

    'stocks' => [
        'name' => 'Stocks',
        'index_title' => 'Stocks List',
        'new_title' => 'New Stock',
        'create_title' => 'Create Stock',
        'edit_title' => 'Edit Stock',
        'show_title' => 'Show Stock',
        'inputs' => [],
    ],

    'time_sheets' => [
        'name' => 'Time Sheets',
        'index_title' => 'TimeSheets List',
        'new_title' => 'New Time sheet',
        'create_title' => 'Create TimeSheet',
        'edit_title' => 'Edit TimeSheet',
        'show_title' => 'Show TimeSheet',
        'inputs' => [
            'value' => 'Value',
            'day' => 'Day',
            'employee_id' => 'Employee',
            'revised_at' => 'Revised At',
            'old_value' => 'Old Value',
            'user_id' => 'Revised By',
        ],
    ],

    'users' => [
        'name' => 'Users',
        'index_title' => 'Users List',
        'new_title' => 'New User',
        'create_title' => 'Create User',
        'edit_title' => 'Edit User',
        'show_title' => 'Show User',
        'inputs' => [
            'name' => 'Name',
            'email' => 'Email',
            'password' => 'Password',
        ],
    ],

    'employees' => [
        'name' => 'Employees',
        'index_title' => 'Employees List',
        'new_title' => 'New Employee',
        'create_title' => 'Create Employee',
        'edit_title' => 'Edit Employee',
        'show_title' => 'Show Employee',
        'inputs' => [
            'number' => 'Number',
            'job' => 'Job',
            'english_name' => 'English Name',
            'id_card' => 'Id Card',
            'id_card_issue_date' => 'Id Card Issue Date',
            'passport' => 'Passport',
            'passport_issue_date' => 'Passport Issue Date',
            'address' => 'Address',
            'phone' => 'Phone',
            'email' => 'Email',
            'user_id' => 'User',
            'location_id' => 'Location',
            'department_id' => 'Department',
            'center_id' => 'Center',
            'transfered_balance' => 'Transfered Balance',
            'schedule' => 'Schedule',
            'start_date' => 'Start Date',
            'last_date' => 'Last Date',
            'total_balance' => 'Total Balance',
            'balance' => 'Balance',
            'archived_at' => 'Archived At',
        ],
    ],

    'flight_passengers' => [
        'name' => 'Flight Passengers',
        'index_title' => ' List',
        'new_title' => 'New Flight passenger',
        'create_title' => 'Create flight_passenger',
        'edit_title' => 'Edit flight_passenger',
        'show_title' => 'Show flight_passenger',
        'inputs' => [
            'passenger_id' => 'Passenger',
        ],
    ],

    'flight_employees' => [
        'name' => 'Flight Employees',
        'index_title' => ' List',
        'new_title' => 'New Employee flight',
        'create_title' => 'Create employee_flight',
        'edit_title' => 'Edit employee_flight',
        'show_title' => 'Show employee_flight',
        'inputs' => [
            'employee_id' => 'Employee',
        ],
    ],

    'roles' => [
        'name' => 'Roles',
        'index_title' => 'Roles List',
        'create_title' => 'Create Role',
        'edit_title' => 'Edit Role',
        'show_title' => 'Show Role',
        'inputs' => [
            'name' => 'Name',
        ],
    ],

    'permissions' => [
        'name' => 'Permissions',
        'index_title' => 'Permissions List',
        'create_title' => 'Create Permission',
        'edit_title' => 'Edit Permission',
        'show_title' => 'Show Permission',
        'inputs' => [
            'name' => 'Name',
        ],
    ],
];
