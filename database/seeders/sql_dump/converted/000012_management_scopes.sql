INSERT INTO `management_scopes` (`id`, `manager_id`, `name`, `template`, `scope_type`, `context`, `location_id`, `department_id`, `center_id`, `subordinate_employee_id`, `settings`, `created_at`, `updated_at`) VALUES
  (7, 10729, '103A Admin SUP', 'general', 'department', 'time_sheet', 3, 6, 6, NULL, '{\"job_title\": null}', '2026-01-09 14:26:49', '2026-04-10 08:00:32'),
  (9, 8881, '103A Production SUP', 'general', 'department', 'time_sheet', 2, 2, NULL, NULL, '{\"job_title\": null}', '2026-02-02 14:33:24', '2026-04-10 08:01:15'),
  (10, 9094, 'GasPlant SUP', 'general', 'department', 'time_sheet', 2, 4, 10, NULL, '{\"job_title\": null}', '2026-03-30 08:50:03', '2026-04-05 08:57:28'),
  (11, 8872, '103A General Maintenance SUP', 'general', 'department', 'time_sheet', 2, 8, NULL, NULL, '{\"job_title\": null}', '2026-04-05 08:59:06', '2026-04-10 08:01:38'),
  (12, 8620, '103A Lap SUP', 'general', 'department', 'time_sheet', 2, 18, NULL, NULL, '{\"job_title\": null}', '2026-04-05 09:01:25', '2026-04-10 07:56:52'),
  (13, 5460, '103A CampBoss SUP', 'general', 'department', 'time_sheet', 2, 10, NULL, NULL, '{\"job_title\": null}', '2026-04-05 09:03:15', '2026-04-10 07:55:34'),
  (14, 9094, 'Superintendent SUP', 'general', 'employee', 'time_sheet', 3, 6, 6, NULL, '{\"job_title\": null, \"target_employee_ids\": [\"6410\", \"8823\", \"8410\", \"7050\", \"7824\", \"7102\", \"7393\", \"5416\", \"6849\", \"8071\", \"7621\"]}', '2026-04-05 09:07:26', '2026-04-11 15:57:25'),
  (15, 8823, '103A Coordinator SUP', 'general', 'employee', 'time_sheet', NULL, NULL, NULL, NULL, '{\"job_title\": null, \"target_employee_ids\": [\"8620\", \"6669\", \"5460\", \"6718\", \"8872\", \"9020\", \"9038\", \"8056\", \"8881\", \"10172\", \"6502\", \"10158\", \"9142\", \"8596\", \"8307\", \"9732\", \"8471\"]}', '2026-04-05 09:12:46', '2026-04-10 08:00:38'),
  (16, 8410, '103A Accountant SUP', 'general', 'department', 'time_sheet', 3, 11, NULL, NULL, '{\"job_title\": null}', '2026-04-05 09:15:38', '2026-04-10 08:00:21'),
  (17, 7050, '103A Transportation SUP', 'general', 'department', 'time_sheet', 2, 5, NULL, NULL, '{\"job_title\": null}', '2026-04-05 15:02:50', '2026-04-10 07:56:01'),
  (18, 9179, '103D PRODUCTION SUP', 'general', 'department', 'time_sheet', 1, 2, NULL, NULL, '{\"job_title\": null}', '2026-04-09 15:06:44', '2026-04-11 15:27:55'),
  (19, 8873, '103D GAS PALNT SUP', 'general', 'department', 'time_sheet', 1, 4, NULL, NULL, '{\"job_title\": null}', '2026-04-09 15:11:51', '2026-04-09 15:12:21'),
  (20, 9448, '103D General Maintenance SUP', 'general', 'department', 'time_sheet', 1, 8, NULL, NULL, '{\"job_title\": null}', '2026-04-09 15:13:42', '2026-04-10 08:01:50'),
  (21, 6372, '103D FIELD COOR SUP', 'general', 'employee', 'time_sheet', NULL, NULL, NULL, NULL, '{\"job_title\": null, \"target_employee_ids\": [\"9179\", \"9103\", \"9676\", \"10835\", \"8063\", \"8979\", \"8873\", \"8127\", \"5792\", \"9448\"]}', '2026-04-09 15:17:33', '2026-04-09 15:17:33')
;
