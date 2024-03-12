// eslint-disable-next-line no-unused-vars
import BaseFactory from '../factory';

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
            const itemElement = this.#baseFactory.blockFactory().item().element(this, element);
            itemElement.addEventListeners();

            this.#items.push(
                itemElement
            );
        });
    }

    /**
     * @param {ItemElement} item
     */
    deleteItem(item) {
        // TODO: Do web service call to delete item

        const index = this.#items.findIndex((i) => i.getItemId() === item.getItemId());
        this.#items.splice(index, 1);
        item.remove();
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
}