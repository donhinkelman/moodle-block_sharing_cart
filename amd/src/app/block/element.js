import Sortable from '../../lib/sortablejs';
import ModalSaveCancel from 'core/modal_save_cancel';
import ModalDeleteCancel from 'core/modal_delete_cancel';
import ModalEvents from 'core/modal_events';
import {get_string as getString, get_strings as getStrings} from 'core/str';
import Ajax from "core/ajax";
import Notification from "core/notification";

/**
 * @typedef BackupSettings
 * @property {boolean} users - Whether to backup user data.
 * @property {boolean} anonymize - Whether to anonymize user data.
 */

export default class BlockElement {
    /**
     * @type {BaseFactory}
     */
    #baseFactory;

    /**
     * @type {HTMLElement}
     */
    #element;

    /**
     * @type {CourseElement}
     */
    #course;

    /**
     * @type {QueueElement}
     */
    #queue;

    /**
     * @type {ItemElement[]}
     */
    #items = [];

    /**
     * @type {Sortable|NULL}
     */
    #sortable = null;

    /**
     * @type {ItemElement|NULL}
     */
    #clipboardItem = null;

    /**
     * @type {Boolean}
     */
    #canBackupUserdata = false;

    /**
     * @type {boolean}
     */
    #canBackup = false;

    /**
     * @type {Boolean}
     */
    #canAnonymizeUserdata = false;

    /**
     * @type {Boolean}
     */
    #showSharingCartBasket = false;

    /**
     * @type {Boolean}
     */
    #showCopiesQueuedSegmentWhenEmpty = true;

    /**
     * @type {Number|null}
     */
    #draggedCourseModuleId = null;

    /**
     * @type {Number|null}
     */
    #draggedSectionId = null;

    /**
     * @type {boolean}
     */
    #bulkDeleteEnabled = false;

    /**
     * @param {BaseFactory} baseFactory
     * @param {HTMLElement} element
     * @param {Boolean} canBackupUserdata
     * @param {Boolean} canAnonymizeUserdata
     * @param {Boolean} canBackup
     * @param {Boolean} showSharingCartBasket
     * @param {Boolean} showCopiesQueuedSegmentWhenEmpty
     */
    constructor(
        baseFactory,
        element,
        canBackupUserdata,
        canAnonymizeUserdata,
        canBackup,
        showSharingCartBasket,
        showCopiesQueuedSegmentWhenEmpty
    ) {
        this.#baseFactory = baseFactory;
        this.#element = element;
        this.#canBackupUserdata = canBackupUserdata;
        this.#canAnonymizeUserdata = canAnonymizeUserdata;
        this.#canBackup = canBackup;
        this.#showSharingCartBasket = showSharingCartBasket;
        this.#showCopiesQueuedSegmentWhenEmpty = showCopiesQueuedSegmentWhenEmpty;
    }

    /**
     * @return {{course: CourseElement, block: BlockElement, queue: QueueElement}}
     */
    addEventListeners() {
        this.setupCourse();
        this.setupQueue();
        this.setupItems();
        this.setupDragAndDrop();
        this.setupBulkDelete();

        return {course: this.#course, queue: this.#queue, block: this};
    }

    setupCourse() {
        const course = document.querySelector('.course-content');

        this.#course = this.#baseFactory.block().course().element(this, course);
    }

    setupQueue() {
        const queue = document.querySelector('.sharing_cart_queue');

        this.#queue = this.#baseFactory.block().queue().element(this, queue, this.#showCopiesQueuedSegmentWhenEmpty);
    }

    async setupItems() {
        const items = this.#element.querySelectorAll('.sharing_cart_item');

        for (const element of items) {
            await this.setupItem(element);
        }

        this.#sortable = new Sortable(this.#element.querySelector('.sharing_cart_items'), {
            dataIdAttr: 'data-itemid',
            draggable: '.sharing_cart_item',
            onUpdate: () => {
                Ajax.call([{
                    methodname: 'block_sharing_cart_reorder_sharing_cart_items',
                    args: {
                        itemids: this.#sortable.toArray().filter((id) => !isNaN(id)),
                    },
                    fail: (data) => {
                        Notification.exception(data);
                    }
                }]);
            }
        });
    }

    setupDragAndDrop() {
        if (!this.#canBackup) {
            return;
        }

        const dropZone = this.#element;

        dropZone.addEventListener('dragover', (e) => {
            if (!this.#draggedSectionId && !this.#draggedCourseModuleId) {
                return;
            }

            e.preventDefault();
            e.stopPropagation();
        });
        dropZone.addEventListener('dragleave', (e) => {
            if (!this.#draggedSectionId && !this.#draggedCourseModuleId) {
                return;
            }

            e.preventDefault();
            e.stopPropagation();
        });
        dropZone.addEventListener('drop', async(e) => {
            if (!this.#draggedSectionId && !this.#draggedCourseModuleId) {
                return;
            }

            e.preventDefault();
            e.stopPropagation();

            if (this.#draggedSectionId) {
                await this.addSectionBackupToSharingCart(this.#draggedSectionId);
            } else if (this.#draggedCourseModuleId) {
                await this.addCourseModuleBackupToSharingCart(this.#draggedCourseModuleId);
            }
        });
    }

    setupBulkDelete() {
        const enableBulkDeleteButton = this.#element.querySelector('#block_sharing_cart_bulk_delete');
        const disableBulkDeleteButton = this.#element.querySelector('#block_sharing_cart_cancel_bulk_delete');
        const bulkDeleteButton = this.#element.querySelector('#block_sharing_cart_bulk_delete_confirm');

        const selectAllContainer = this.#element.querySelector('#select_all_container');
        const selectAllCheckbox = this.#element.querySelector('#select_all_box');

        selectAllCheckbox.addEventListener('click', async() => {
            const itemCheckboxes = this.getItemCheckboxes();
            const allSelected = Array.from(itemCheckboxes).every(checkbox => checkbox.checked);
            itemCheckboxes.forEach(checkbox => {
                checkbox.checked = !allSelected;
            });
            itemCheckboxes.forEach(checkbox => checkbox.addEventListener('change', async() => {
                this.updateSelectAllState();
            }));

            this.updateSelectAllState();
            this.updateBulkDeleteButtonState();
        });

        enableBulkDeleteButton.addEventListener('click', () => {
            if (this.#items.length === 0) {
                return;
            }

            this.#bulkDeleteEnabled = true;

            enableBulkDeleteButton.classList.add('d-none');
            disableBulkDeleteButton.classList.remove('d-none');

            selectAllContainer.classList.remove('d-none');
            bulkDeleteButton.classList.remove('d-none');

            this.getItemCheckboxes().forEach((checkbox) => {
                checkbox.classList.remove('d-none');
            });
        });

        disableBulkDeleteButton.addEventListener('click', () => {
            disableBulkDeleteButton.classList.add('d-none');
            bulkDeleteButton.classList.add('d-none');
            bulkDeleteButton.disabled = true;
            enableBulkDeleteButton.classList.remove('d-none');
            selectAllContainer.classList.add('d-none');
            this.#bulkDeleteEnabled = false;

            this.getItemCheckboxes().forEach((checkbox) => {
                checkbox.classList.add('d-none');
                checkbox.checked = false;
            });
            this.updateSelectAllState();
        });

        bulkDeleteButton.addEventListener('click', async() => {
            if (bulkDeleteButton.disabled) {
                return;
            }

            const itemIds = [];
            this.getItemCheckboxes().forEach((checkbox) => {
                if (!checkbox.checked) {
                    return;
                }

                itemIds.push(checkbox.value);
            });

            await this.confirmDeleteItems(itemIds);
        });
    }

    /**
     * @param {HTMLElement} element
     */
    async setupItem(element) {
        const itemElement = this.#baseFactory.block().item().element(this, element);

        if (itemElement.getStatus() !== '0' && this.isBulkDeleteEnabled()) {
            const checkbox = element.querySelector('input[data-action="bulk_select"][type="checkbox"]');
            checkbox?.classList?.remove('d-none');
        }

        this.#element.querySelector('.no-items').classList.add('d-none');
        this.#element.querySelector('.has-items').classList.remove('d-none');

        const rootItems = this.#element.querySelectorAll('.sharing_cart_items > .sharing_cart_item');
        if (this.#bulkDeleteEnabled === false && rootItems.length > 1) {
            this.#element.querySelector('#block_sharing_cart_bulk_delete').classList.remove('d-none');
        }

        const existingItemIndex = this.#items.findIndex((i) => i.getItemId() === itemElement.getItemId());
        if (existingItemIndex !== -1) {
            this.#items[existingItemIndex] = itemElement;
        } else {
            this.#items.push(itemElement);
        }

        this.updateBulkDeleteButtonState();
        this.updateSelectAllState();
    }

    getItemCheckboxes() {
        const checkboxSelector = '.sharing_cart_item:not([data-status="0"]) input[data-action="bulk_select"][type="checkbox"]';
        return this.#element.querySelectorAll(checkboxSelector);
    }

    updateBulkDeleteButtonState() {
        const bulkDeleteButton = this.#element.querySelector('#block_sharing_cart_bulk_delete_confirm');
        bulkDeleteButton.disabled = !Array.from(this.getItemCheckboxes()).some(checkbox => checkbox.checked);
    }

    updateSelectAllState() {
        const selectAllCheckbox = this.#element.querySelector('#select_all_box');
        const selectAllLabel = this.#element.querySelector('#select_all_label');

        const itemCheckboxes = this.getItemCheckboxes();
        const allSelected = Array.from(itemCheckboxes).every(checkbox => checkbox.checked);
        const someSelected = Array.from(itemCheckboxes).some(checkbox => checkbox.checked);

        const strPromise = allSelected ?
            getString('deselect_all', 'block_sharing_cart') :
            getString('select_all', 'block_sharing_cart');
        strPromise.then((str) => {
            selectAllLabel.textContent = str;
            return str;
        }).catch(Notification.exception);

        selectAllCheckbox.checked = allSelected;
        selectAllCheckbox.indeterminate = !allSelected && someSelected;
    }

    isBulkDeleteEnabled() {
        return this.#bulkDeleteEnabled;
    }

    /**
     * @param {ItemElement} item
     */
    async setClipboard(item) {
        this.#clipboardItem = item;

        await this.#course.setClipboard(item);
    }

    clearClipboard() {
        this.#clipboardItem = null;
    }

    /**
     * @param {Number|null} id
     */
    setDraggedSectionId(id) {
        this.#draggedSectionId = id;
    }

    /**
     * @param {Number|null} id
     */
    setDraggedCourseModuleId(id) {
        this.#draggedCourseModuleId = id;
    }

    /**
     * @param {ItemElement} item
     */
    async removeItemElement(item) {
        const childItems = item.getItemChildrenRecursively();
        childItems.forEach((childItem) => {
            const index = this.#items.findIndex((i) => i.getItemId() === Number.parseInt(childItem.dataset.itemid));
            if (index === -1) {
                return;
            }

            if (this.#items[index].getItemId() === this.#clipboardItem?.getItemId()) {
                this.#course.clearClipboard();
            }

            this.#items.splice(index, 1);
            childItem.remove();
        });

        const index = this.#items.findIndex((i) => i.getItemId() === item.getItemId());
        if (this.#items[index].getItemId() === this.#clipboardItem?.getItemId()) {
            this.#course.clearClipboard();
        }

        this.#items.splice(index, 1);
        item.remove();

        if (this.#items.length === 0) {
            this.#element.querySelector('.no-items').classList.remove('d-none');
            this.#element.querySelector('.has-items').classList.add('d-none');
        }

        const rootItems = this.#element.querySelectorAll('.sharing_cart_items > .sharing_cart_item');
        if (rootItems.length < 2) {
            this.hideBulkDelete();
        }
    }

    hideBulkDelete() {
        this.#bulkDeleteEnabled = false;
        this.#element.querySelector('#block_sharing_cart_cancel_bulk_delete').classList.add('d-none');
        this.#element.querySelector('#block_sharing_cart_bulk_delete_confirm').classList.add('d-none');
        this.#element.querySelector('#block_sharing_cart_bulk_delete').classList.add('d-none');
        this.#element.querySelector('#select_all_container').classList.add('d-none');

        this.getItemCheckboxes().forEach((checkbox) => {
            checkbox.classList.add('d-none');
            checkbox.checked = false;
        });
        this.updateSelectAllState();
    }

    /**
     * @param {ItemElement} item
     */
    deleteItem(item) {
        Ajax.call([{
            methodname: 'block_sharing_cart_delete_item_from_sharing_cart',
            args: {
                itemid: item.getItemId(),
            },
            done: async(deleted) => {
                if (deleted) {
                    await this.removeItemElement(item);
                    this.updateSelectAllState();
                } else {
                    await Notification.alert('Failed to delete item');
                }
            },
            fail: (data) => {
                Notification.exception(data);
            }
        }]);
    }

    /**
     * @param {Array<Number>} itemIds
     */
    deleteItems(itemIds) {
        itemIds = itemIds.map((id) => Number.parseInt(id));

        Ajax.call([{
            methodname: 'block_sharing_cart_delete_items_from_sharing_cart',
            args: {
                itemids: itemIds,
            },
            done: async(deletedItemIds) => {
                const items = this.#items.filter((i) => itemIds.includes(i.getItemId()));
                for (const item of items) {
                    const deleted = deletedItemIds.includes(item.getItemId());
                    if (!deleted) {
                        Notification.alert('Failed to delete item: "' + item.getItemName() + '"');
                        continue;
                    }

                    await this.removeItemElement(item);
                }
                this.updateSelectAllState();

                document.getElementById('block_sharing_cart_bulk_delete_confirm').disabled = true;
            },
            fail: (data) => {
                Notification.exception(data);
            }
        }]);
    }

    getElement() {
        return this.#element;
    }

    /**
     * @param {number} courseModuleId
     * @param {string} name
     * @param {(BackupSettings) => void} onSave
     * @returns {Promise<Modal>}
     */
    async createBackupCourseModuleToSharingCartModal(courseModuleId, name, onSave) {
        return this.createBackupItemToSharingCartModal('module', courseModuleId, name, onSave);
    }

    /**
     * @param {number} sectionId
     * @param {string} name
     * @param {(BackupSettings) => void} onSave
     * @returns {Promise<Modal>}
     */
    async createBackupSectionToSharingCartModal(sectionId, name, onSave) {
        return this.createBackupItemToSharingCartModal('section', sectionId, name, onSave);
    }

    /**
     * @param {string} backupType
     * @param {number} itemId
     * @returns {boolean}
     */
    #hasQuiz(backupType, itemId) {
        if (backupType === 'section') {
            return this.#course.hasSectionCourseModuleType(itemId, 'quiz');
        }
        return this.#course.isCourseModuleTypeById(itemId, 'quiz');
    }

    /**
     * @param {string} backupType
     * @param {number} itemId
     * @param {string} itemName
     * @param {(BackupSettings) => void} onSave
     * @return {Promise<Modal>}
     */
    async createBackupItemToSharingCartModal(backupType, itemId, itemName, onSave) {

        const strings = await getStrings([
            {
                key: 'backup_item',
                component: 'block_sharing_cart',
            },
            {
                key: 'into_sharing_cart',
                component: 'block_sharing_cart',
            },
            {
                key: 'copy',
                component: 'block_sharing_cart',
            },
            {
                key: 'cancel',
                component: 'core',
            }
        ]);

        const {html, js} = await this.#baseFactory.moodle().template().renderTemplate(
            'block_sharing_cart/modal/backup_to_sharing_cart_modal_body',
            {
                // eslint-disable-next-line camelcase
                has_quiz: this.#hasQuiz(backupType, itemId),
                // eslint-disable-next-line camelcase
                show_user_data_backup: this.#canBackupUserdata,
                // eslint-disable-next-line camelcase
                show_anonymize_user_data: this.#canBackupUserdata && this.#canAnonymizeUserdata,
            }
        );

        /**
         * @type {Modal}
         */
        const modal = await ModalSaveCancel.create({
            title: strings[0] + ': "' + itemName.slice(0, 50).trim() + '" ' + strings[1],
            body: html,
            buttons: {
                save: strings[2],
                cancel: strings[3],
            },
            removeOnClose: true,
        });
        modal.getRoot().on(ModalEvents.shown, () => this.#baseFactory.moodle().template().runTemplateJS(js));
        modal.getRoot().on(ModalEvents.save, () => {
            const modalUserdataCheckbox = document.getElementById('modal-userdata-checkbox');
            const modalAnonymizeCheckbox = document.getElementById('modal-anonymize-checkbox');
            onSave({
                users: modalUserdataCheckbox?.checked ?? false,
                anonymize: modalAnonymizeCheckbox?.checked ?? false
            });
        });

        return modal;
    }

    /**
     * @param {Number} sectionId
     */
    async addSectionBackupToSharingCart(sectionId) {
        const sectionName = this.#course.getSectionName(sectionId);

        // Whether there is anything to copy is decided server side: a section without activities may still
        // hold populated subsections in a nesting course format.
        const saver = (settings) => {
            Ajax.call([{
                methodname: 'block_sharing_cart_backup_section_into_sharing_cart',
                args: {
                    sectionid: sectionId,
                    settings: settings
                },
                done: async(data) => {
                    await this.renderItem(data);
                },
                fail: (data) => {
                    Notification.exception(data);
                }
            }]);
        };

        const modal = await this.createBackupSectionToSharingCartModal(
            sectionId,
            sectionName,
            saver
        );
        await modal.show();
    }

    /**
     * @param {Number} courseModuleId
     */
    async addCourseModuleBackupToSharingCart(courseModuleId) {
        const courseModuleName = this.#course.getCourseModuleName(courseModuleId);
        const saver = (settings) => {
            Ajax.call([{
                methodname: 'block_sharing_cart_backup_course_module_into_sharing_cart',
                args: {
                    coursemoduleid: courseModuleId,
                    settings: settings
                },
                done: async(data) => {
                    await this.renderItem(data);
                },
                fail: (data) => {
                    Notification.exception(data);
                }
            }]);
        };
        const modal = await this.createBackupCourseModuleToSharingCartModal(
            courseModuleId,
            courseModuleName,
            saver
        );
        await modal.show();
    }

    /**
     * @param {Object} item
     */
    async renderItem(item) {
        const existingItemIndex = this.#items.findIndex((i) => i.getItemId() === item.id);
        const existingItem = this.#items[existingItemIndex] ?? false;
        const getOldElement = () => {
            return this.#element.querySelector('.sharing_cart_items .sharing_cart_item[data-itemid="' + item.id + '"]');
        };
        const oldElement = getOldElement();
        if (existingItem && oldElement) {
            const element = await this.#baseFactory.moodle().template().createElementFromFragment(
                'block_sharing_cart',
                'item',
                M.cfg.courseContextId,
                {
                    itemid: item.id,
                }
            );

            // Early exit if the element has been removed from the DOM in between rendering and checking earlier.
            if (getOldElement() !== oldElement) {
                return;
            }

            this.#element.querySelector('.sharing_cart_items').replaceChild(element, oldElement);
            this.#items[existingItemIndex] = this.#baseFactory.block().item().element(this, element);

            await this.setupItem(element);
            for (const subItem of element.querySelectorAll('.sharing_cart_item')) {
                await this.setupItem(subItem);
            }

            return;
        }

        const element = await this.#baseFactory.moodle().template().createElementFromTemplate(
            'block_sharing_cart/block/item',
            {
                id: item.id,
                name: item.name,
                type: item.type,
                status: 0,
                // eslint-disable-next-line camelcase
                old_instance_id: item.old_instance_id,
                // eslint-disable-next-line camelcase
                status_awaiting: true,
                // eslint-disable-next-line camelcase
                show_run_now: false,
                // eslint-disable-next-line camelcase
                can_copy_to_course: item.can_copy_to_course ?? false,
                taskid: item.taskid ?? null,
                // eslint-disable-next-line camelcase
                status_finished: false,
                // eslint-disable-next-line camelcase
                status_failed: false,
                // eslint-disable-next-line camelcase
                is_module: item.type !== 'section' && item.type !== 'mod_subsection',
                // eslint-disable-next-line camelcase
                is_subsection: item.type === 'mod_subsection',
                // eslint-disable-next-line camelcase
                is_section: item.type === 'section',
                // eslint-disable-next-line camelcase
                is_root: true,
            }
        );
        this.#element.querySelector('.sharing_cart_items').prepend(element);

        await this.setupItem(element);
    }

    /**
     * @param {ItemElement} item
     * @param {Number} sectionId
     * @param {HTMLElement} modal
     * @param {Object} options {asNewSection: Boolean, courseId: Number}
     */
    importItem(item, sectionId, modal, options = {}) {
        this.#course.clearClipboard();

        // A nested section that is unchecked drops everything beneath it, whatever the state of the child boxes.
        const isUnderUncheckedSection = (checkbox) => {
            let parent = checkbox.closest('.form-check')?.parentElement?.closest('.form-check');
            while (parent) {
                const parentCheckbox = parent.querySelector(':scope > label > input[type="checkbox"]');
                if (parentCheckbox && parentCheckbox.dataset.type === 'section' && !parentCheckbox.checked) {
                    return true;
                }
                parent = parent.parentElement?.closest('.form-check');
            }
            return false;
        };

        const replaceSectionDetails = Boolean(
            modal.querySelector('input[data-action="replace_section_details"]')?.checked
        );

        const courseModuleIds = [];
        const sectionIds = [];
        modal.querySelectorAll('input[type="checkbox"]:checked').forEach((checkbox) => {
            // Option boxes (e.g. replace section details) are not content selections.
            if (checkbox.dataset.type === 'option' || isUnderUncheckedSection(checkbox)) {
                return;
            }
            if (checkbox.dataset.type === 'section') {
                sectionIds.push(checkbox.dataset.id);
            } else {
                courseModuleIds.push(checkbox.dataset.id);
            }
        });

        // The backend treats an empty list as "all nested sections", so signal "none" explicitly.
        const hasNestedSections = modal.querySelector('input[type="checkbox"][data-type="section"]') !== null;
        if (hasNestedSections && sectionIds.length === 0) {
            sectionIds.push(0);
        }

        if (item.isSection() && courseModuleIds.length === 0 && (!hasNestedSections || sectionIds[0] === 0)) {
            modal.querySelectorAll('.form-check-input').forEach(async(item) => {
                item.setCustomValidity(
                    await getString('atleast_one_course_module_must_be_included', 'block_sharing_cart')
                );
                item.reportValidity();
            });
            return false;
        }

        if (item.isModule() && !item.isSubsection()) {
            courseModuleIds.push(item.getItemOldInstanceId());
        }

        Ajax.call([{
            methodname: 'block_sharing_cart_restore_item_from_sharing_cart_into_section',
            args: {
                itemid: item.getItemId(),
                sectionid: sectionId,
                coursemodulestoinclude: courseModuleIds,
                sectionstoinclude: sectionIds,
                insertasnewsection: Boolean(options.asNewSection),
                courseid: options.courseId ?? 0,
                replacesectiondetails: replaceSectionDetails,
            },
            done: async(success) => {
                if (success) {
                    await this.#queue.loadQueue(true);
                }
            },
            fail: (data) => {
                Notification.exception(data);
            }
        }]);
        return true;
    }

    /**
     * @param {ItemElement} item
     * @param {Number} sectionId target section, 0 = top level of the course (only with options.asNewSection)
     * @param {Object} options {asNewSection: Boolean, courseId: Number}
     * @param {Event} e
     */
    async confirmImportBackupFromSharingCart(item, sectionId, options, e) {
        e.preventDefault();
        e.stopPropagation();

        const strings = await getStrings([
            {
                key: 'copy_item',
                component: 'block_sharing_cart',
            },
            {
                key: 'into_section',
                component: 'block_sharing_cart',
            },
            {
                key: 'copy',
                component: 'block_sharing_cart',
            },
            {
                key: 'cancel',
                component: 'core',
            },
            {
                key: 'as_new_section_in',
                component: 'block_sharing_cart',
            },
            {
                key: 'the_course',
                component: 'block_sharing_cart',
            },
        ]);

        options = options ?? {};
        const asNewSection = Boolean(options.asNewSection);
        const sectionName = sectionId > 0 ? this.#course.getSectionName(sectionId) : strings[5];
        const intoText = asNewSection ? strings[4] : strings[1];
        const divElement = document.getElementById('block_sharing_cart');
        const pageContextId = divElement.getAttribute('data-contextid');

        const {html, js} = await this.#baseFactory.moodle().template().renderFragment(
            'block_sharing_cart',
            'item_restore_form',
            pageContextId,
            {
                itemid: item.getItemId(),
                clipboardtargetid: sectionId,
                asnewsection: asNewSection ? 1 : 0,
            }
        );

        const modal = await ModalSaveCancel.create({
            title: strings[0] + ': ' +
                '"' + item.getItemName().slice(0, 50).trim() + '"' +
                ' ' + intoText + ': ' +
                '"' + sectionName.slice(0, 50).trim() + '"',
            body: html,
            buttons: {
                save: strings[2],
                cancel: strings[3],
            },
            removeOnClose: true,
        });
        modal.getRoot().on(ModalEvents.shown, () => this.#baseFactory.moodle().template().runTemplateJS(js));
        modal.getRoot().on(ModalEvents.save, this.importItem.bind(this, item, sectionId, modal.getRoot()[0], options));
        await modal.show();
    }

    /**
     * @param {Array<Number>} itemIds
     */
    async confirmDeleteItems(itemIds) {
        const strings = await getStrings([
            {
                key: 'delete_items',
                component: 'block_sharing_cart',
            },
            {
                key: 'confirm_delete_items',
                component: 'block_sharing_cart',
            },
            {
                key: 'delete',
                component: 'core',
            },
            {
                key: 'cancel',
                component: 'core',
            }
        ]);

        const modal = await ModalDeleteCancel.create({
            title: strings[0],
            body: strings[1],
            buttons: {
                "delete": strings[2],
                cancel: strings[3],
            },
            removeOnClose: true,
        });

        modal.getRoot().on(ModalEvents.delete, this.deleteItems.bind(this, itemIds));
        await modal.show();
    }
}
