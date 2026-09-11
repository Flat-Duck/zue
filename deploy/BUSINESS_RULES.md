# Current business contracts

These describe preserved application behavior. Permission grants must be managed
through the existing administration workflows, not inferred from job titles.

| Surface | Access contract |
| --- | --- |
| Employee CRUD and directory | Action-specific employee permissions are global; these are not manager-scoped permissions. |
| Timesheets | Action permission plus the authoritative scope/workflow resolver; account IDs and employee IDs are distinct. |
| Clinic and injury records | `manage-clinic` gate and independent Livewire authorization. |
| Appraisal configuration | `manage-appraisals` gate. |
| Official appraisal management | HR, admin, super-admin; viewing additionally permits self and authorized management scope. Approval stages retain their existing role and scope checks. |
| Appraisal review | Existing appraiser/employee scope and lifecycle checks. |
| Flights | Flight action permissions and independent manifest/dispatch authorization. |
| Employee profile imports | Both create and update employee permissions. |
| Maintenance, backups and system status | `maintenance` gate. |
| Impersonation | Existing super-admin restriction; both acting user and original operator remain auditable. |

## Appraisal calculations

Grade boundaries remain: >=90 excellent, >=80 very good, >=70 good, >=60
acceptable, otherwise weak. A null percentage has no grade.

Yearly percentage averages available quarterly official percentages. Missing
quarters do not contribute zero: 80 and 90 produce 85 even when only two quarters
exist. Item averages remain keyed by form-version item ID. Items from different
versions are not merged merely because they describe the same underlying item.
A representative version does not imply that every yearly item belongs to it.
Historical scores must not be rewritten as part of structural refactoring.
