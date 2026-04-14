INSERT INTO `approval_flows` (`id`, `context`, `name`, `is_active`, `applies_to`, `created_at`, `updated_at`) VALUES
  (1, 'time_sheet', 'A1', 1, '{\"department_keys\": [\"admin\", \"accounting\", \"transportation\", \"transport\", \"transp\"], \"employee_roles_any\": [\"supervisor\"]}', '2026-04-11 13:44:31', '2026-04-11 13:44:31'),
  (2, 'time_sheet', 'A2', 1, '{\"department_keys\": [\"gaspant\", \"gp\", \"production\", \"prod\", \"prodnc163\", \"lab\", \"generalmaintenance\", \"genmaint\", \"esp\", \"campboss\", \"camboss\"], \"employee_roles_any\": [\"supervisor\"]}', '2026-04-11 13:44:31', '2026-04-11 13:44:31'),
  (3, 'time_sheet', 'A3', 1, '{\"department_keys\": [\"gaspant\", \"gp\", \"production\", \"prod\", \"prodnc163\", \"lab\", \"generalmaintenance\", \"genmaint\", \"esp\", \"campboss\", \"camboss\"], \"employee_roles_none\": [\"supervisor\"]}', '2026-04-11 13:44:31', '2026-04-11 13:44:31'),
  (4, 'time_sheet', 'A4', 1, '{\"department_keys\": [\"admin\", \"accounting\", \"transportation\", \"transport\", \"transp\"], \"employee_roles_none\": [\"supervisor\"]}', '2026-04-11 13:44:31', '2026-04-11 13:44:31'),
  (5, 'time_sheet', 'LEGACY', 1, '[]', '2026-04-11 13:44:31', '2026-04-11 13:44:31')
;
