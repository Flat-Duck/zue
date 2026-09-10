/**
 * A yearly appraisal period has no quarter, so the quarter field is hidden when
 * the type says yearly — and cleared, so a quarter left over from an earlier
 * choice is not submitted with it.
 *
 * The create and edit forms each carried their own copy of this, and they had
 * already drifted: only one of them cleared the field.
 */
function bindQuarterVisibility() {
    const type = document.getElementById('typeSelect');
    const quarter = document.getElementById('quarterField');

    if (!type || !quarter) {
        return;
    }

    const apply = () => {
        const yearly = type.value === 'yearly';

        quarter.hidden = yearly;

        if (yearly) {
            const select = quarter.querySelector('select');

            if (select) {
                select.value = '';
            }
        }
    };

    type.addEventListener('change', apply);
    apply();
}

document.addEventListener('DOMContentLoaded', bindQuarterVisibility);
document.addEventListener('livewire:navigated', bindQuarterVisibility);
