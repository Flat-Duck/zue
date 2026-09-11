<?php

/*
 * The Arabic interface.
 *
 * Field names follow the wording of the company's own personnel export, which
 * ProfileDefinition already carries, so a clerk reads the same word on the screen
 * as on the form they are typing from.
 */

return [
    'common' => [
        'language' => 'اللغة',
        'dark_mode' => 'تفعيل الوضع الداكن',
        'light_mode' => 'تفعيل الوضع الفاتح',
        'notifications' => 'الإشعارات',
        'no_notifications' => 'لا يوجد جديد',
        'mark_all_read' => 'تعليم الكل كمقروء',
        'actions' => 'الإجراءات',
        'create' => 'إنشاء',
        'edit' => 'تعديل',
        'update' => 'تحديث',
        'new' => 'جديد',
        'cancel' => 'إلغاء',
        'attach' => 'إرفاق',
        'detach' => 'إلغاء الإرفاق',
        'save' => 'حفظ',
        'delete' => 'حذف',
        'delete_selected' => 'حذف المحدد',
        'search' => 'بحث...',
        'back' => 'العودة إلى القائمة',
        'are_you_sure' => 'هل أنت متأكد؟',
        'no_items_found' => 'لا توجد عناصر',
        'created' => 'تم الإنشاء بنجاح',
        'saved' => 'تم الحفظ بنجاح',
        'deleted' => 'تم الحذف بنجاح',
        'refresh' => 'تحديث',
        'removed' => 'تم الحذف بنجاح',
        'print_preview' => 'طباعة',
        'time_sheet_approve' => 'اعتماد بطاقات ضبط الوقت',
        'none' => 'لا شيء',
        'please_select' => 'الرجاء الاختيار',
    ],

    'administrations' => [
        'name' => 'الإدارات',
        'index_title' => 'قائمة الإدارات',
        'new_title' => 'إدارة جديد',
        'create_title' => 'إنشاء إدارة',
        'edit_title' => 'تعديل إدارة',
        'show_title' => 'عرض إدارة',
        'inputs' => [
            'name' => 'الاسم',
        ],
    ],

    'centers' => [
        'name' => 'مراكز التكلفة',
        'index_title' => 'قائمة مراكز التكلفة',
        'new_title' => 'مركز تكلفة جديد',
        'create_title' => 'إنشاء مركز تكلفة',
        'edit_title' => 'تعديل مركز تكلفة',
        'show_title' => 'عرض مركز تكلفة',
        'inputs' => [
            'name' => 'الاسم',
        ],
    ],

    'departments' => [
        'name' => 'الأقسام',
        'index_title' => 'قائمة الأقسام',
        'new_title' => 'قسم جديد',
        'create_title' => 'إنشاء قسم',
        'edit_title' => 'تعديل قسم',
        'show_title' => 'عرض قسم',
        'inputs' => [
            'name' => 'الاسم',
            'administration_id' => 'الإدارة',
        ],
    ],

    'flights' => [
        'name' => 'الرحلات',
        'index_title' => 'قائمة الرحلات',
        'new_title' => 'رحلة جديد',
        'create_title' => 'إنشاء رحلة',
        'edit_title' => 'تعديل رحلة',
        'show_title' => 'عرض رحلة',
        'inputs' => [
            'type' => 'النوع',
            'date' => 'التاريخ',
            'time' => 'الوقت',
            'plane_id' => 'الطائرة',
        ],
    ],

    'locations' => [
        'name' => 'المواقع',
        'index_title' => 'قائمة المواقع',
        'new_title' => 'موقع جديد',
        'create_title' => 'إنشاء موقع',
        'edit_title' => 'تعديل موقع',
        'show_title' => 'عرض موقع',
        'inputs' => [
            'name' => 'الاسم',
            'description' => 'الوصف',
        ],
    ],

    'passengers' => [
        'name' => 'الركاب',
        'index_title' => 'قائمة الركاب',
        'new_title' => 'راكب جديد',
        'create_title' => 'إنشاء راكب',
        'edit_title' => 'تعديل راكب',
        'show_title' => 'عرض راكب',
        'inputs' => [
            'name' => 'الاسم',
            'company' => 'الشركة',
            'number' => 'الرقم',
            'nationality' => 'الجنسية',
        ],
    ],

    'residences' => [
        'name' => 'المساكن',
        'index_title' => 'قائمة المساكن',
        'new_title' => 'سكن جديد',
        'create_title' => 'إنشاء سكن',
        'edit_title' => 'تعديل سكن',
        'show_title' => 'عرض سكن',
        'inputs' => [
            'name' => 'الاسم',
            'type' => 'النوع',
        ],
    ],

    'rooms' => [
        'name' => 'الغرف',
        'index_title' => 'قائمة الغرف',
        'new_title' => 'غرفة جديد',
        'create_title' => 'إنشاء غرفة',
        'edit_title' => 'تعديل غرفة',
        'show_title' => 'عرض غرفة',
        'inputs' => [
            'number' => 'الرقم',
            'beds' => 'الأسرّة',
            'status' => 'الحالة',
            'emplyees' => 'الموظفون',
            'residence_id' => 'السكن',
        ],
    ],

    'stocks' => [
        'name' => 'المخزون',
        'index_title' => 'قائمة المخزون',
        'new_title' => 'صنف مخزون جديد',
        'create_title' => 'إنشاء صنف مخزون',
        'edit_title' => 'تعديل صنف مخزون',
        'show_title' => 'عرض صنف مخزون',
        'inputs' => [],
    ],

    'time_sheets' => [
        'name' => 'بطاقات ضبط الوقت',
        'index_title' => 'قائمة بطاقات ضبط الوقت',
        'new_title' => 'بطاقة ضبط وقت جديد',
        'create_title' => 'إنشاء بطاقة ضبط وقت',
        'edit_title' => 'تعديل بطاقة ضبط وقت',
        'show_title' => 'عرض بطاقة ضبط وقت',
        'time_keeper_approve' => 'اعتماد حافظ الوقت',
        'supervisor_approve' => 'اعتماد مشرف القسم',
        'superintendent_approve' => 'اعتماد مراقب الحقول',
        'inputs' => [
            'value' => 'القيمة',
            'day' => 'اليوم',
            'employee_id' => 'الموظف',
            'revised_at' => 'تاريخ المراجعة',
            'old_value' => 'القيمة السابقة',
            'user_id' => 'روجعت بواسطة',
        ],
    ],

    'users' => [
        'name' => 'المستخدمون',
        'index_title' => 'قائمة المستخدمين',
        'new_title' => 'مستخدم جديد',
        'create_title' => 'إنشاء مستخدم',
        'edit_title' => 'تعديل مستخدم',
        'show_title' => 'عرض مستخدم',
        'inputs' => [
            'name' => 'الاسم',
            'email' => 'البريد الإلكتروني',
            'password' => 'كلمة المرور',
        ],
    ],

    'employees' => [
        'name' => 'الموظفون',
        'index_title' => 'قائمة الموظفين',
        'new_title' => 'موظف جديد',
        'create_title' => 'إنشاء موظف',
        'edit_title' => 'تعديل موظف',
        'show_title' => 'عرض موظف',
        'inputs' => [
            'number' => 'الرقم',
            'job' => 'الوظيفة',
            'english_name' => 'الاسم بالإنجليزية',
            'id_card' => 'رقم البطاقة الشخصية',
            'id_card_issue_date' => 'تاريخ إصدار البطاقة',
            'passport' => 'رقم الجواز',
            'passport_issue_date' => 'تاريخ إصدار الجواز',
            'address' => 'العنوان',
            'phone' => 'الهاتف',
            'email' => 'البريد الإلكتروني',
            'user_id' => 'المستخدم',
            'location_id' => 'الموقع',
            'department_id' => 'القسم',
            'center_id' => 'مركز التكلفة',
            'transfered_balance' => 'الرصيد المرحل',
            'schedule' => 'الدورية',
            'start_date' => 'تاريخ البدء',
            'last_date' => 'تاريخ الانتهاء',
            'total_balance' => 'إجمالي الرصيد',
            'balance' => 'الرصيد',
            'archived_at' => 'تاريخ الأرشفة',
        ],
    ],

    'flight_passengers' => [
        'name' => 'ركاب الرحلة',
        'index_title' => 'قائمة',
        'new_title' => 'راكب على رحلة جديد',
        'create_title' => 'إنشاء راكب على رحلة',
        'edit_title' => 'تعديل راكب على رحلة',
        'show_title' => 'عرض راكب على رحلة',
        'inputs' => [
            'passenger_id' => 'الراكب',
        ],
    ],

    'flight_employees' => [
        'name' => 'موظفو الرحلة',
        'index_title' => 'قائمة',
        'new_title' => 'موظف على رحلة جديد',
        'create_title' => 'إنشاء موظف على رحلة',
        'edit_title' => 'تعديل موظف على رحلة',
        'show_title' => 'عرض موظف على رحلة',
        'inputs' => [
            'name' => 'الاسم',
            'employee_id' => 'الرقم',
        ],
    ],

    'room_employees' => [
        'name' => 'موظفو الغرفة',
        'index_title' => 'قائمة',
        'new_title' => 'موظف في غرفة جديد',
        'create_title' => 'إنشاء موظف في غرفة',
        'edit_title' => 'تعديل موظف في غرفة',
        'show_title' => 'عرض موظف في غرفة',
        'inputs' => [
            'employee_id' => 'الموظف',
            'is_here' => 'موجود',
        ],
    ],

    'residence_rooms' => [
        'name' => 'غرف السكن',
        'index_title' => 'قائمة الغرف',
        'new_title' => 'غرفة جديد',
        'create_title' => 'إنشاء غرفة',
        'edit_title' => 'تعديل غرفة',
        'show_title' => 'عرض غرفة',
        'inputs' => [
            'name' => 'الاسم',
            'user_id' => 'المستخدم',
        ],
    ],

    'planes' => [
        'name' => 'الطائرات',
        'index_title' => 'قائمة الطائرات',
        'new_title' => 'طائرة جديد',
        'create_title' => 'إنشاء طائرة',
        'edit_title' => 'تعديل طائرة',
        'show_title' => 'عرض طائرة',
        'inputs' => [
            'name' => 'الاسم',
            'capacity' => 'السعة',
            'lines' => 'الخطوط',
        ],
    ],

    'roles' => [
        'name' => 'الأدوار',
        'index_title' => 'قائمة الأدوار',
        'create_title' => 'إنشاء دور',
        'edit_title' => 'تعديل دور',
        'show_title' => 'عرض دور',
        'inputs' => [
            'name' => 'الاسم',
        ],
    ],

    'permissions' => [
        'name' => 'الصلاحيات',
        'index_title' => 'قائمة الصلاحيات',
        'create_title' => 'إنشاء صلاحية',
        'edit_title' => 'تعديل صلاحية',
        'show_title' => 'عرض صلاحية',
        'inputs' => [
            'name' => 'الاسم',
        ],
    ],

    'management_scopes' => [
        'name' => 'نطاقات الإشراف',
        'index_title' => 'قائمة نطاقات الإشراف',
        'new_title' => 'نطاق إشراف جديد',
        'create_title' => 'إنشاء نطاق إشراف',
        'edit_title' => 'تعديل نطاق إشراف',
        'show_title' => 'عرض نطاق إشراف',

        'filters' => [
            'all_managers' => 'كل المديرين',
        ],

        'columns' => [
            'manager' => 'المدير',
            'scope_type' => 'نوع النطاق',
            'scope_detail' => 'تفاصيل النطاق',
        ],

        'scope_labels' => [
            'global' => 'شامل — كامل الشركة',
        ],

        'inputs' => [
            'manager_id' => 'المدير',
            'scope_type' => 'نوع النطاق',
            'location_id' => 'الموقع',
            'department_id' => 'القسم',
            'center_id' => 'مركز التكلفة',
            'subordinate_employee_id' => 'موظف محدد',
        ],
    ],
];

// return [
//     'common' => [
//         'actions' => 'الإجراءات',
//         'create' => 'إنشاء',
//         'edit' => 'تعديل',
//         'update' => 'تحديث',
//         'new' => 'جديد',
//         'cancel' => 'إلغاء',
//         'attach' => 'إرفاق',
//         'detach' => 'إلغاء الإرفاق',
//         'save' => 'حفظ',
//         'delete' => 'حذف',
//         'delete_selected' => 'حذف المحدد',
//         'search' => 'بحث...',
//         'back' => 'العودة إلى القائمة',
//         'are_you_sure' => 'هل أنت متأكد؟',
//         'no_items_found' => 'لا توجد عناصر',
//         'created' => 'تم الإنشاء بنجاح',
//         'saved' => 'تم الحفظ بنجاح',
//         'removed' => 'تم الحذف بنجاح',
//         'print_preview' => 'طباعة',
//     ],

//     'administrations' => [
//         'name' => 'الإدارات',
//         'index_title' => 'قائمة الإدارات',
//         'new_title' => 'إدارة جديد',
//         'create_title' => 'إنشاء إدارة',
//         'edit_title' => 'تعديل إدارة',
//         'show_title' => 'عرض إدارة',
//         'inputs' => [
//             'name' => 'الاسم',
//         ],
//     ],

//     'centers' => [
//         'name' => 'مراكز التكلفة',
//         'index_title' => 'قائمة مراكز التكلفة',
//         'new_title' => 'مركز تكلفة جديد',
//         'create_title' => 'إنشاء مركز تكلفة',
//         'edit_title' => 'تعديل مركز تكلفة',
//         'show_title' => 'عرض مركز تكلفة',
//         'inputs' => [
//             'name' => 'الاسم',
//         ],
//     ],

//     'departments' => [
//         'name' => 'الأقسام',
//         'index_title' => 'قائمة الأقسام',
//         'new_title' => 'قسم جديد',
//         'create_title' => 'إنشاء قسم',
//         'edit_title' => 'تعديل قسم',
//         'show_title' => 'عرض قسم',
//         'inputs' => [
//             'name' => 'الاسم',
//             'administration_id' => 'الإدارة',
//         ],
//     ],

//     'flights' => [
//         'name' => 'الرحلات',
//         'index_title' => 'قائمة الرحلات',
//         'new_title' => 'رحلة جديد',
//         'create_title' => 'إنشاء رحلة',
//         'edit_title' => 'تعديل رحلة',
//         'show_title' => 'عرض رحلة',
//         'inputs' => [
//             'type' => 'النوع',
//             'date' => 'التاريخ',
//             'time' => 'الوقت',
//             'plane_id' => 'الطائرة',
//         ],
//     ],

//     'locations' => [
//         'name' => 'المواقع',
//         'index_title' => 'قائمة المواقع',
//         'new_title' => 'موقع جديد',
//         'create_title' => 'إنشاء موقع',
//         'edit_title' => 'تعديل موقع',
//         'show_title' => 'عرض موقع',
//         'inputs' => [
//             'name' => 'الاسم',
//             'description' => 'الوصف',
//         ],
//     ],

//     'passengers' => [
//         'name' => 'الركاب',
//         'index_title' => 'قائمة الركاب',
//         'new_title' => 'راكب جديد',
//         'create_title' => 'إنشاء راكب',
//         'edit_title' => 'تعديل راكب',
//         'show_title' => 'عرض راكب',
//         'inputs' => [
//             'name' => 'الاسم',
//             'company' => 'الشركة',
//             'number' => 'الرقم',
//             'nationality' => 'الجنسية',
//         ],
//     ],

//     'residences' => [
//         'name' => 'المساكن',
//         'index_title' => 'قائمة المساكن',
//         'new_title' => 'سكن جديد',
//         'create_title' => 'إنشاء سكن',
//         'edit_title' => 'تعديل سكن',
//         'show_title' => 'عرض سكن',
//         'inputs' => [
//             'name' => 'الاسم',
//             'type' => 'النوع',
//         ],
//     ],

//     'rooms' => [
//         'name' => 'الغرف',
//         'index_title' => 'قائمة الغرف',
//         'new_title' => 'غرفة جديد',
//         'create_title' => 'إنشاء غرفة',
//         'edit_title' => 'تعديل غرفة',
//         'show_title' => 'عرض غرفة',
//         'inputs' => [
//             'number' => 'الرقم',
//             'beds' => 'الأسرّة',
//             'status' => 'الحالة',
//             'emplyees' => 'الموظفون',
//             'residence_id' => 'السكن',
//         ],
//     ],

//     'stocks' => [
//         'name' => 'المخزون',
//         'index_title' => 'قائمة المخزون',
//         'new_title' => 'صنف مخزون جديد',
//         'create_title' => 'إنشاء صنف مخزون',
//         'edit_title' => 'تعديل صنف مخزون',
//         'show_title' => 'عرض صنف مخزون',
//         'inputs' => [],
//     ],

//     'time_sheets' => [
//         'name' => 'بطاقات ضبط الوقت',
//         'index_title' => 'قائمة بطاقات ضبط الوقت',
//         'new_title' => 'بطاقة ضبط وقت جديد',
//         'create_title' => 'إنشاء بطاقة ضبط وقت',
//         'edit_title' => 'تعديل بطاقة ضبط وقت',
//         'show_title' => 'عرض بطاقة ضبط وقت',
//         'time_keeper_approve' => 'اعتماد حافظ الوقت',
//         'supervisor_approve' => 'اعتماد مشرف القسم',
//         'superintendent_approve' => 'اعتماد مراقب الحقول',
//         'inputs' => [
//             'value' => 'القيمة',
//             'day' => 'اليوم',
//             'employee_id' => 'الموظف',
//             'revised_at' => 'تاريخ المراجعة',
//             'old_value' => 'القيمة السابقة',
//             'user_id' => 'روجعت بواسطة',
//         ],
//     ],

//     'users' => [
//         'name' => 'المستخدمون',
//         'index_title' => 'قائمة المستخدمين',
//         'new_title' => 'مستخدم جديد',
//         'create_title' => 'إنشاء مستخدم',
//         'edit_title' => 'تعديل مستخدم',
//         'show_title' => 'عرض مستخدم',
//         'inputs' => [
//             'name' => 'الاسم',
//             'email' => 'البريد الإلكتروني',
//             'password' => 'كلمة المرور',
//         ],
//     ],

//     'employees' => [
//         'name' => 'الموظفون',
//         'index_title' => 'قائمة الموظفين',
//         'new_title' => 'موظف جديد',
//         'create_title' => 'إنشاء موظف',
//         'edit_title' => 'تعديل موظف',
//         'show_title' => 'عرض موظف',
//         'inputs' => [
//             'number' => 'الرقم',
//             'job' => 'الوظيفة',
//             'english_name' => 'الاسم بالإنجليزية',
//             'id_card' => 'رقم البطاقة الشخصية',
//             'id_card_issue_date' => 'تاريخ إصدار البطاقة',
//             'passport' => 'رقم الجواز',
//             'passport_issue_date' => 'تاريخ إصدار الجواز',
//             'address' => 'العنوان',
//             'phone' => 'الهاتف',
//             'email' => 'البريد الإلكتروني',
//             'user_id' => 'المستخدم',
//             'location_id' => 'الموقع',
//             'department_id' => 'القسم',
//             'center_id' => 'مركز التكلفة',
//             'transfered_balance' => 'الرصيد المرحل',
//             'schedule' => 'الدورية',
//             'start_date' => 'تاريخ البدء',
//             'last_date' => 'تاريخ الانتهاء',
//             'total_balance' => 'إجمالي الرصيد',
//             'balance' => 'الرصيد',
//             'archived_at' => 'تاريخ الأرشفة',
//         ],
//     ],

//     'flight_passengers' => [
//         'name' => 'ركاب الرحلة',
//         'index_title' => 'قائمة',
//         'new_title' => 'راكب على رحلة جديد',
//         'create_title' => 'إنشاء راكب على رحلة',
//         'edit_title' => 'تعديل راكب على رحلة',
//         'show_title' => 'عرض راكب على رحلة',
//         'inputs' => [
//             'passenger_id' => 'الراكب',
//         ],
//     ],

//     'flight_employees' => [
//         'name' => 'موظفو الرحلة',
//         'index_title' => 'قائمة',
//         'new_title' => 'موظف على رحلة جديد',
//         'create_title' => 'إنشاء موظف على رحلة',
//         'edit_title' => 'تعديل موظف على رحلة',
//         'show_title' => 'عرض موظف على رحلة',
//         'inputs' => [
//             'name' => 'الاسم',
//             'employee_id' => 'الرقم',
//         ],
//     ],

//     'room_employees' => [
//         'name' => 'موظفو الغرفة',
//         'index_title' => 'قائمة',
//         'new_title' => 'موظف في غرفة جديد',
//         'create_title' => 'إنشاء موظف في غرفة',
//         'edit_title' => 'تعديل موظف في غرفة',
//         'show_title' => 'عرض موظف في غرفة',
//         'inputs' => [
//             'employee_id' => 'الموظف',
//             'is_here' => 'موجود',
//         ],
//     ],

//     'residence_rooms' => [
//         'name' => 'غرف السكن',
//         'index_title' => 'قائمة الغرف',
//         'new_title' => 'غرفة جديد',
//         'create_title' => 'إنشاء غرفة',
//         'edit_title' => 'تعديل غرفة',
//         'show_title' => 'عرض غرفة',
//         'inputs' => [
//             'name' => 'الاسم',
//             'user_id' => 'المستخدم',
//         ],
//     ],

//     'planes' => [
//         'name' => 'الطائرات',
//         'index_title' => 'قائمة الطائرات',
//         'new_title' => 'طائرة جديد',
//         'create_title' => 'إنشاء طائرة',
//         'edit_title' => 'تعديل طائرة',
//         'show_title' => 'عرض طائرة',
//         'inputs' => [
//             'name' => 'الاسم',
//             'capacity' => 'السعة',
//             'lines' => 'الخطوط',
//         ],
//     ],

//     'roles' => [
//         'name' => 'الأدوار',
//         'index_title' => 'قائمة الأدوار',
//         'create_title' => 'إنشاء دور',
//         'edit_title' => 'تعديل دور',
//         'show_title' => 'عرض دور',
//         'inputs' => [
//             'name' => 'الاسم',
//         ],
//     ],

//     'permissions' => [
//         'name' => 'الصلاحيات',
//         'index_title' => 'قائمة الصلاحيات',
//         'create_title' => 'إنشاء صلاحية',
//         'edit_title' => 'تعديل صلاحية',
//         'show_title' => 'عرض صلاحية',
//         'inputs' => [
//             'name' => 'الاسم',
//         ],
//     ],
// ];
