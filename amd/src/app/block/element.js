// eslint-disable-next-line no-unused-vars
import BaseFactory from '../factory';
import ModalFactory from 'core/modal_factory';
import ModalEvents from 'core/modal_events';
import {get_string, get_strings} from "core/str";
import Ajax from "core/ajax";
import {getCurrentCourseEditor} from "core_courseformat/courseeditor";

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
     * @type {ItemElement[]}
     */
    #items = [];

    /**
     * @type {ItemElement|NULL}
     */
    #clipboardItem = null;

    #canBackupUserdata = false;
    #canAnonymizeUserdata = false;

    constructor(baseFactory, element, canBackupUserdata, canAnonymizeUserdata) {
        this.#baseFactory = baseFactory;
        this.#element = element;
        this.#canBackupUserdata = canBackupUserdata;
        this.#canAnonymizeUserdata = canAnonymizeUserdata;
    }

    addEventListeners() {
        this.setupCourse();
        this.setupItems();
    }

    setupCourse() {
        const course = document.querySelector('.course-content');

        const courseElement = this.#baseFactory.block().course().element(this, course);
        courseElement.addBackupToSharingCartButtons();

        this.#course = courseElement;
    }

    setupItems() {
        const items = this.#element.querySelectorAll('.sharing_cart_item');

        items.forEach((element) => {
            this.setupItem(element);
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
                } else {
                    console.error('Failed to delete item');
                }
            },
            fail: (data) => {
                console.error(data);
            }
        }]);
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
                component: 'core',
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
                users: modalUserdataCheckbox.checked ?? false,
                anonymize: modalAnonymizeCheckbox.checked ?? false
            });
        });

        return modal;
    }

    /**
     * @param {Number} sectionId
     */
    async addSectionBackupToSharingCart(sectionId) {
        const sectionName = this.#course.getSectionName(sectionId);

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
                    console.error(data);
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
                    console.error(data);
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
                status_awaiting: true,
                status_finished: false,
                status_failed: false,
                is_module: item.type !== 'section' && item.type !== 'course',
                is_course: item.type === 'course',
                is_section: item.type === 'section',
                is_root: true,
            }
        );
        this.#element.querySelector('.sharing_cart_items').append(element);

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

        Ajax.call([{
            methodname: 'block_sharing_cart_restore_item_from_sharing_cart_into_section',
            args: {
                item_id: item.getItemId(),
                section_id: sectionId,
                course_modules_to_include: courseModuleIds,
            },
            done: async (success) => {
                if (success) {
                    const courseEditor = getCurrentCourseEditor();

                    setTimeout(() => {
                        courseEditor.dispatch('sectionState', [sectionId]);
                    }, 4000);
                }
            },
            fail: (data) => {
                console.error(data);
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
                strings[1] + ': ' +
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
}