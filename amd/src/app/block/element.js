// eslint-disable-next-line no-unused-vars
import BaseFactory from '../factory';
import ModalFactory from 'core/modal_factory';
import ModalEvents from 'core/modal_events';
import { get_strings } from "core/str";
import Ajax from "core/ajax";
import Templates from "core/templates";

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
    constructor(baseFactory, element) {
        this.#baseFactory = baseFactory;
        this.#element = element;
    }

    addEventListeners() {
        this.setupCourse();
        this.setupItems();
    }
    setupCourse() {
        const course = document.querySelector('.course-content');

        const courseElement = this.#baseFactory.blockFactory().course().element(this, course);
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
        const itemElement = this.#baseFactory.blockFactory().item().element(this, element);
        itemElement.addEventListeners();

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
        // TODO: Do web service call to delete item
        console.log('Deleting item (id: '+item.getItemId()+') from sharing cart');

        const index = this.#items.findIndex((i) => i.getItemId() === item.getItemId());
        this.#items.splice(index, 1);
        item.remove();
    }

    /**
     * @param {Number} sectionId
     */
    addSectionBackupToSharingCart(sectionId) {
        console.log('Adding section (id: '+sectionId+') backup to sharing cart');

        Ajax.call([{
            methodname: 'block_sharing_cart_backup_section_into_sharing_cart',
            args: {
                section_id: sectionId,
            },
            done: async (data) => {
                await this.renderItem(data);
            },
            fail: (data) => {
                console.log(data);
            }
        }]);
    }

    /**
     * @param {Number} courseModuleId
     */
    addCourseModuleBackupToSharingCart(courseModuleId) {
        console.log('Adding course module (id: '+courseModuleId+') backup to sharing cart');

        Ajax.call([{
            methodname: 'block_sharing_cart_backup_course_module_into_sharing_cart',
            args: {
                course_module_id: courseModuleId,
            },
            done: async (data) => {
                await this.renderItem(data);
            },
            fail: (data) => {
                console.log(data);
            }
        }]);
    }

    /**
     * @param {Object} item
     */
    async renderItem(item) {
        let element = document.createElement('div');
        const {html, js} = await new Promise((resolve, reject) => {
            Templates.render('block_sharing_cart/block/item', {
                id: item.id,
                name: item.name,
                type: item.type,
                status: 0,
                is_module: item.type !== 'section' && item.type !== 'course',
                is_course: item.type === 'course',
                is_section: item.type === 'section',
                is_root: true,
            }).then(async (html, js) => {
                resolve({
                    html,
                    js
                });
            }).fail(reject);
        });
        element = await Templates.replaceNode(
            element,
            html,
            js
        )[0];

        this.#element.querySelector('.sharing_cart_items').append(element);

        this.setupItem(element);
    }

    /**
     * @param {ItemElement} item
     * @param {Number} sectionId
     */
    importItem(item, sectionId) {
        // TODO: Do web service call to delete item

        console.log('Importing item (id: '+item.getItemId()+') from sharing cart to section (id: '+sectionId+')');
        this.#course.clearClipboard();
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
                key: 'confirm_copy_item',
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

        const modal = await ModalFactory.create({
            type: ModalFactory.types.SAVE_CANCEL,
            title: strings[0] + ': "' + item.getItemName() + '" ' + strings[1] + ': "' + sectionName + '"',
            body: strings[2],
            buttons: {
                save: strings[3],
                cancel: strings[4],
            },
            removeOnClose: true,
        });
        modal.getRoot().on(ModalEvents.save, this.importItem.bind(this, item, sectionId));
        await modal.show();
    }
}