import {get_string as getString} from 'core/str';
import Notification from 'core/notification';

/** Restore-dialog selection behavior for sections and their descendants. */
export default class RestoreSelection {
    /**
     * Binds cascading selection, partial-state and placement feedback to a restore form.
     *
     * @param {HTMLElement} form
     */
    static bind(form) {
        form.addEventListener('change', (event) => {
            const checkbox = event.target;
            checkbox.setCustomValidity('');

            if (checkbox.dataset.container === 'true') {
                checkbox.closest('.form-check')
                    ?.querySelectorAll('.item-children input[type="checkbox"][data-id]')
                    .forEach((child) => {
                        if (!child.disabled) {
                            child.checked = checkbox.checked;
                            child.indeterminate = false;
                        }
                    });
            }

            RestoreSelection.update(form).catch(Notification.exception);
        });

        RestoreSelection.update(form).catch(Notification.exception);
    }

    /**
     * Updates container partial states and tells users where an individually selected child will land.
     *
     * @param {HTMLElement} form
     * @return {Promise<void>}
     */
    static async update(form) {
        const containers = [...form.querySelectorAll('input[type="checkbox"][data-container="true"]')].reverse();
        containers.forEach((containerCheckbox) => {
            const container = containerCheckbox.closest('.form-check');
            const descendants = [...(container?.querySelectorAll('.item-children input[type="checkbox"][data-id]') ?? [])];
            const hasSelected = descendants.some((checkbox) => checkbox.checked || checkbox.indeterminate);
            const hasUnselected = descendants.some((checkbox) => !checkbox.checked || checkbox.indeterminate);
            containerCheckbox.indeterminate = hasSelected && (!containerCheckbox.checked || hasUnselected);
        });

        const root = form.querySelector('.import_form');
        const replaceDetails = root?.querySelector('input[data-action="replace_section_details"]')?.checked;
        const defaultDestination = replaceDetails
            ? (root?.dataset.restoreSourceName || root?.dataset.restoreTargetName || '')
            : (root?.dataset.restoreTargetName ?? '');
        const hints = [...form.querySelectorAll('[data-region="restore-placement-hint"]')];

        await Promise.all(hints.map(async (hint) => {
            const item = hint.closest('.form-check')?.querySelector(
                ':scope > label > input[type="checkbox"][data-id]'
            );
            let parent = item?.closest('.form-check')?.parentElement?.closest('.form-check');
            let nearestContainer = null;
            let destination = defaultDestination;

            while (parent) {
                const parentCheckbox = parent.querySelector(':scope > label > input[type="checkbox"]');
                if (parentCheckbox?.dataset.container === 'true') {
                    if (!nearestContainer) {
                        nearestContainer = parentCheckbox;
                        if (parentCheckbox.checked) {
                            break;
                        }
                    } else if (parentCheckbox.checked) {
                        destination = parentCheckbox.dataset.title || destination;
                        break;
                    }
                }
                parent = parent.parentElement?.closest('.form-check');
            }

            if (!item?.checked || !nearestContainer || nearestContainer.checked || !destination) {
                hint.classList.add('d-none');
                hint.replaceChildren();
                delete hint.dataset.destination;
                return;
            }

            hint.classList.remove('d-none');
            hint.dataset.destination = destination;
            const placeholder = '@@RESTORE_DESTINATION@@';
            const message = await getString('restore_selected_item_in_section', 'block_sharing_cart', placeholder);

            if (hint.dataset.destination !== destination || hint.classList.contains('d-none')) {
                return;
            }

            const placeholderindex = message.indexOf(placeholder);
            if (placeholderindex < 0) {
                hint.textContent = message;
                return;
            }

            const destinationtext = document.createElement('strong');
            destinationtext.className = 'text-body fw-bold';
            destinationtext.textContent = destination;
            hint.replaceChildren(
                document.createTextNode(message.slice(0, placeholderindex)),
                destinationtext,
                document.createTextNode(message.slice(placeholderindex + placeholder.length))
            );
        }));
    }

    /**
     * Reads checked content IDs for the restore service.
     *
     * @param {HTMLElement} form
     * @return {{courseModuleIds: number[], sectionIds: number[], replaceSectionDetails: boolean}}
     */
    static read(form) {
        const idsOf = (type) => [...form.querySelectorAll('input[type="checkbox"]:checked')]
            .filter((checkbox) => checkbox.dataset.type === type)
            .map((checkbox) => Number(checkbox.dataset.id))
            .filter((id) => Number.isSafeInteger(id) && id > 0);

        const courseModuleIds = idsOf('coursemodule');
        const sectionIds = idsOf('section');
        // The backend treats an empty list as "all nested sections", so signal "none" explicitly.
        if (form.querySelector('input[type="checkbox"][data-type="section"]') && sectionIds.length === 0) {
            sectionIds.push(0);
        }

        return {
            courseModuleIds,
            sectionIds,
            replaceSectionDetails: Boolean(form.querySelector('input[data-action="replace_section_details"]:checked')),
        };
    }
}
