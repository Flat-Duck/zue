INSERT INTO `approval_flow_steps` (`id`, `flow_id`, `step_order`, `step_key`, `required_role`, `can_fill`, `can_approve`, `depends_on_step_order`, `created_at`, `updated_at`) VALUES
  (1, 1, 1, 'timekeeper', 'timekeeper', 1, 1, NULL, '2026-04-11 13:44:31', '2026-04-11 13:44:31'),
  (2, 1, 2, 'superintendent', 'superintendent', 0, 1, 1, '2026-04-11 13:44:31', '2026-04-11 13:44:31'),
  (3, 2, 1, 'timekeeper', 'timekeeper', 1, 1, NULL, '2026-04-11 13:44:31', '2026-04-11 13:44:31'),
  (4, 2, 2, 'fieldcoordinator', 'fieldcoordinator', 0, 1, 1, '2026-04-11 13:44:31', '2026-04-11 13:44:31'),
  (5, 3, 1, 'timekeeper', 'timekeeper', 1, 1, NULL, '2026-04-11 13:44:31', '2026-04-11 13:44:31'),
  (6, 3, 2, 'supervisor', 'supervisor', 0, 1, 1, '2026-04-11 13:44:31', '2026-04-11 13:44:31'),
  (7, 3, 3, 'fieldcoordinator', 'fieldcoordinator', 0, 1, 2, '2026-04-11 13:44:31', '2026-04-11 13:44:31'),
  (8, 4, 1, 'timekeeper', 'timekeeper', 1, 1, NULL, '2026-04-11 13:44:31', '2026-04-11 13:44:31'),
  (9, 4, 2, 'supervisor', 'supervisor', 0, 1, 1, '2026-04-11 13:44:31', '2026-04-11 13:44:31'),
  (10, 5, 1, 'timekeeper', 'timekeeper', 1, 1, NULL, '2026-04-11 13:44:31', '2026-04-11 13:44:31'),
  (11, 5, 2, 'supervisor', 'supervisor', 0, 1, 1, '2026-04-11 13:44:31', '2026-04-11 13:44:31'),
  (12, 5, 3, 'superintendent', 'superintendent', 0, 1, 2, '2026-04-11 13:44:31', '2026-04-11 13:44:31')
;
