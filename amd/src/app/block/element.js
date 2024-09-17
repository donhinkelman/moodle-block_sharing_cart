import Sortable from '../../lib/sortablejs';
import ModalFactory from 'core/modal_factory';
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

    setupItems() {
        const items = this.#element.querySelectorAll('.sharing_cart_item');

        items.forEach((element) => {
            this.setupItem(element);
        });

        this.#sortable = new Sortable(this.#element.querySelector('.sharing_cart_items'), {
            dataIdAttr: 'data-itemid',
            onUpdate: () => {
                Ajax.call([{
                    methodname: 'block_sharing_cart_reorder_sharing_cart_items',
                    args: {
                        item_ids: this.#sortable.toArray(),
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

        const checkboxSelector = '.sharing_cart_item input[data-action="bulk_select"][type="checkbox"]';

        enableBulkDeleteButton.addEventListener('click', () => {
            enableBulkDeleteButton.classList.add('d-none');
            disableBulkDeleteButton.classList.remove('d-none');
            bulkDeleteButton.classList.remove('d-none');

            this.#element.querySelectorAll(checkboxSelector).forEach((checkbox) => {
                checkbox.classList.remove('d-none');
                checkbox.checked = false;
            });
        });

        disableBulkDeleteButton.addEventListener('click', () => {
            disableBulkDeleteButton.classList.add('d-none');
            bulkDeleteButton.classList.add('d-none');
            bulkDeleteButton.disabled = true;
            enableBulkDeleteButton.classList.remove('d-none');

            this.#element.querySelectorAll(checkboxSelector).forEach((checkbox) => {
                checkbox.classList.add('d-none');
                checkbox.checked = false;
            });
        });

        bulkDeleteButton.addEventListener('click', async () => {
            if (bulkDeleteButton.disabled) {
                return;
            }

            const itemIds = [];
            this.#element.querySelectorAll(checkboxSelector + ':checked').forEach((checkbox) => {
                itemIds.push(checkbox.value);
            });

            await this.confirmDeleteItems(itemIds);
        });
    }

    /**
     * @param {HTMLElement} element
     */
    setupItem(element) {
        const itemElement = this.#baseFactory.block().item().element(this, element);

        this.#element.querySelector('.no-items')?.remove();

        this.#items.push(
            itemElement
        );
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
            this.#element.querySelector('.sharing_cart_items')
                .innerHTML = await get_string('no_items', 'block_sharing_cart');
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
        const modal = await ModalFactory.create({
            type: ModalFactory.types.SAVE_CANCEL,
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
        const oldElement = this.#element.querySelector('.sharing_cart_items .sharing_cart_item[data-itemid="' + item.id + '"]');
        if (existingItem && oldElement) {
            const element = await this.#baseFactory.moodle().template().createElementFromFragment(
                'block_sharing_cart',
                'item',
                1,
                {
                    item_id: item.id,
                }
            );

            this.#element.querySelector('.sharing_cart_items').replaceChild(element, oldElement);
            this.#items[existingItemIndex] = this.#baseFactory.block().item().element(this, element);

            element.querySelectorAll('.sharing_cart_item').forEach((subItem) => {
                this.setupItem(subItem);
            });

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

        this.setupItem(element);
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

        const modal = await ModalFactory.create({
            type: ModalFactory.types.SAVE_CANCEL,
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

        const modal = await ModalFactory.create({
            type: ModalFactory.types.DELETE_CANCEL,
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
