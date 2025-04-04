import Sortable from '../../lib/sortablejs';
import ModalDeleteCancel from 'core/modal_delete_cancel';
import ModalSaveCancel from 'core/modal_save_cancel';
import ModalEvents from 'core/modal_events';
import {get_string, get_strings} from "core/str";
import Ajax from "core/ajax";
import Notification from "core/notification";

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
     * @type {Boolean}
     */
    #canAnonymizeUserdata = false;

    /**
     * @type {Boolean}
     */
    #showSharingCartBasket = false;

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
     * @param {Boolean} showSharingCartBasket
     */
    constructor(baseFactory, element, canBackupUserdata, canAnonymizeUserdata, showSharingCartBasket) {
        this.#baseFactory = baseFactory;
        this.#element = element;
        this.#canBackupUserdata = canBackupUserdata;
        this.#canAnonymizeUserdata = canAnonymizeUserdata;
        this.#showSharingCartBasket = showSharingCartBasket;
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

        this.#queue = this.#baseFactory.block().queue().element(this, queue);
    }

    async setupItems() {
        const items = this.#element.querySelectorAll('.sharing_cart_item');

        for (const element of items) {
            await this.setupItem(element);
        }

        this.#sortable = new Sortable(this.#element.querySelector('.sharing_cart_items'), {
            dataIdAttr: 'data-itemid',
            onUpdate: () => {
                Ajax.call([{
                    methodname: 'block_sharing_cart_reorder_sharing_cart_items',
                    args: {
                        item_ids: this.#sortable.toArray().filter((id) => !isNaN(id)),
                    },
                    fail: (data) => {
                        Notification.exception(data);
                    }
                }]);
            }
        });
    }

    setupDragAndDrop() {
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
        dropZone.addEventListener('drop', async (e) => {
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

        selectAllCheckbox.addEventListener('click', async () => {
            const itemCheckboxes = this.getItemCheckboxes();
            const allSelected = Array.from(itemCheckboxes).every(checkbox => checkbox.checked);
            itemCheckboxes.forEach(checkbox => {
                checkbox.checked = !allSelected;
            });
            itemCheckboxes.forEach(checkbox => checkbox.addEventListener('change', async () => {
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

        bulkDeleteButton.addEventListener('click', async () => {
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
            get_string('deselect_all', 'block_sharing_cart') :
            get_string('select_all', 'block_sharing_cart');
        strPromise.then((str) => {
            selectAllLabel.textContent = str;
        });

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
        }
    }

    /**
     * @param {ItemElement} item
     */
    deleteItem(item) {
        Ajax.call([{
            methodname: 'block_sharing_cart_delete_item_from_sharing_cart',
            args: {
                item_id: item.getItemId(),
            },
            done: async (deleted) => {
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
                item_ids: itemIds,
            },
            done: async (deletedItemIds) => {
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
     * @param {String} itemName
     * @param {CallableFunction} onSave
     * @return {Promise<Modal>}
     */
    async createBackupItemToSharingCartModal(itemName, onSave) {
        const strings = await get_strings([
            {
                key: 'backup_item',
                component: 'block_sharing_cart',
            },
            {
                key: 'into_sharing_cart',
                component: 'block_sharing_cart',
            },
            {
                key: 'backup',
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
                show_user_data_backup: this.#canBackupUserdata,
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

        const cms = this.#course.getSectionCourseModules(sectionId);

        if (cms.length === 0) {
            const strings = await get_strings([
                {
                    key: 'no_course_modules_in_section',
                    component: 'block_sharing_cart',
                },
                {
                    key: 'no_course_modules_in_section_description',
                    component: 'block_sharing_cart',
                },
            ]);

            await Notification.alert(strings[0], strings[1]);

            return;
        }

        const modal = await this.createBackupItemToSharingCartModal(sectionName, (settings) => {
            Ajax.call([{
                methodname: 'block_sharing_cart_backup_section_into_sharing_cart',
                args: {
                    section_id: sectionId,
                    settings: settings
                },
                done: async (data) => {
                    await this.renderItem(data);
                },
                fail: (data) => {
                    Notification.exception(data);
                }
            }]);
        });

        await modal.show();
    }

    /**
     * @param {Number} courseModuleId
     */
    async addCourseModuleBackupToSharingCart(courseModuleId) {
        const courseModuleName = this.#course.getCourseModuleName(courseModuleId);

        const modal = await this.createBackupItemToSharingCartModal(courseModuleName, (settings) => {
            Ajax.call([{
                methodname: 'block_sharing_cart_backup_course_module_into_sharing_cart',
                args: {
                    course_module_id: courseModuleId,
                    settings: settings
                },
                done: async (data) => {
                    await this.renderItem(data);
                },
                fail: (data) => {
                    Notification.exception(data);
                }
            }]);
        });
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
                1,
                {
                    item_id: item.id,
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
                old_instance_id: item.old_instance_id,
                status_awaiting: true,
                has_run_now: true,
                task_id: item.task_id ?? null,
                status_finished: false,
                status_failed: false,
                is_module: item.type !== 'section',
                is_section: item.type === 'section',
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
     */
    importItem(item, sectionId, modal) {
        this.#course.clearClipboard();

        const courseModuleIds = [];
        modal.querySelectorAll('input[type="checkbox"][data-type="coursemodule"]:checked').forEach((checkbox) => {
            courseModuleIds.push(checkbox.dataset.id);
        });

        if (item.isSection() && courseModuleIds.length === 0) {
            modal.querySelectorAll('.form-check-input').forEach(async (item) => {
                item.setCustomValidity(
                    await get_string('atleast_one_course_module_must_be_included', 'block_sharing_cart')
                );
                item.reportValidity();
            });
            return false;
        }

        if (item.isModule()) {
            courseModuleIds.push(item.getItemOldInstanceId());
        }

        Ajax.call([{
            methodname: 'block_sharing_cart_restore_item_from_sharing_cart_into_section',
            args: {
                item_id: item.getItemId(),
                section_id: sectionId,
                course_modules_to_include: courseModuleIds,
            },
            done: async (success) => {
                if (success) {
                    await this.#queue.loadQueue(true);
                }
            },
            fail: (data) => {
                Notification.exception(data);
            }
        }]);
    }

    /**
     * @param {ItemElement} item
     * @param {Number} sectionId
     * @param {Event} e
     */
    async confirmImportBackupFromSharingCart(item, sectionId, e) {
        e.preventDefault();
        e.stopPropagation();

        const strings = await get_strings([
            {
                key: 'copy_item',
                component: 'block_sharing_cart',
            },
            {
                key: 'into_section',
                component: 'block_sharing_cart',
            },
            {
                key: 'import',
                component: 'core',
            },
            {
                key: 'cancel',
                component: 'core',
            }
        ]);

        const sectionName = this.#course.getSectionName(sectionId);

        const {html, js} = await this.#baseFactory.moodle().template().renderFragment(
            'block_sharing_cart',
            'item_restore_form',
            1,
            {
                item_id: item.getItemId()
            }
        );

        const modal = await ModalSaveCancel.create({
            title: strings[0] + ': ' +
                '"' + item.getItemName().slice(0, 50).trim() + '"' +
                ' ' + strings[1] + ': ' +
                '"' + sectionName.slice(0, 50).trim() + '"',
            body: html,
            buttons: {
                save: strings[2],
                cancel: strings[3],
            },
            removeOnClose: true,
        });
        modal.getRoot().on(ModalEvents.shown, () => this.#baseFactory.moodle().template().runTemplateJS(js));
        modal.getRoot().on(ModalEvents.save, this.importItem.bind(this, item, sectionId, modal.getRoot()[0]));
        await modal.show();
    }

    /**
     * @param {Array<Number>} itemIds
     */
    async confirmDeleteItems(itemIds) {
        const strings = await get_strings([
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
                delete: strings[2],
                cancel: strings[3],
            },
            removeOnClose: true,
        });

        modal.getRoot().on(ModalEvents.delete, this.deleteItems.bind(this, itemIds));
        await modal.show();
    }
}
