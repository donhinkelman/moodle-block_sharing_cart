// eslint-disable-next-line no-unused-vars
import BaseFactory from '../factory';
import ModalFactory from 'core/modal_factory';
import ModalEvents from 'core/modal_events';
import { get_strings } from "core/str";

export default class ItemElement {
    /**
     * @type {BaseFactory}
     */
    #baseFactory;

    /**
     * @type {BlockElement}
     */
    #blockElement;

    /**
     * @type {HTMLElement}
     */
    #element;

    constructor(baseFactory, blockElement, element) {
        this.#baseFactory = baseFactory;
        this.#blockElement = blockElement;
        this.#element = element;
    }

    addEventListeners() {
        this.#element.addEventListener('click', this.toggleCollapseRecursively.bind(this));

        this.#element.querySelector('[data-action="delete"]')?.addEventListener('click', this.confirmDeleteItem.bind(this));
        this.#element.querySelector('[data-action="copy_to_course"]')?.addEventListener('click', this.copyItemToCourse.bind(this));
    }

    async copyItemToCourse(e) {
        e.preventDefault();
        e.stopPropagation();

        await this.#blockElement.setClipboard(this);
    }

    async confirmDeleteItem(e) {
        e.preventDefault();
        e.stopPropagation();

        const strings = await get_strings([
            {
                key: 'delete_item',
                component: 'block_sharing_cart',
            },
            {
                key: 'confirm_delete_item',
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
            title: strings[0] + ': "' + this.getItemName() + '"',
            body: strings[1],
            buttons: {
                delete: strings[2],
                cancel: strings[3],
            },
            removeOnClose: true,
        });
        modal.getRoot().on(ModalEvents.delete, this.#blockElement.deleteItem.bind(this.#blockElement, this));
        await modal.show();
    }

    /**
     * @return {NodeListOf<HTMLElement>}
     */
    getItemChildrenRecursively() {
        return this.#element.querySelectorAll('.sharing_cart_item');
    }

    /**
     * @return {String}
     */
    getItemName() {
        return this.#element.querySelector('.name').innerText;
    }

    /**
     * @return {Number}
     */
    getItemId() {
        return Number.parseInt(this.#element.dataset.itemid);
    }

    /**
     * @return {HTMLElement}
     */
    getItemInfo() {
        return this.#element.querySelector('.info');
    }

    /**
     * @param {HTMLElement} item
     * @param {Boolean|NULL} collapse
     */
    toggleCollapse(item, collapse = null) {
        if (item.dataset.type !== 'course' && item.dataset.type !== 'section') {
            return;
        }

        if (collapse !== null) {
            item.dataset.collapsed = collapse ? 'true' : 'false';
        } else {
            item.dataset.collapsed = item.dataset.collapsed === 'true' ? 'false' : 'true';
        }

        const iconElement = item.querySelector('.info > i');
        iconElement.classList.remove('fa-folder-o', 'fa-folder-open-o');
        iconElement.classList.add(item.dataset.collapsed === 'true' ? 'fa-folder-o' : 'fa-folder-open-o');
    }

    isModule() {
        return !this.isCourse() && !this.isSection();
    }

    isCourse() {
        return this.#element.dataset.type === 'course';
    }

    isSection() {
        return this.#element.dataset.type === 'section';
    }

    /**
     * @param {Event} e
     */
    toggleCollapseRecursively(e) {
        e.preventDefault();
        e.stopPropagation();

        if(this.isModule()) {
            return;
        }

        this.toggleCollapse(this.#element);
        this.getItemChildrenRecursively().forEach((item) => {
            this.toggleCollapse(item, this.#element.dataset.collapsed === 'true');
        });
    }

    remove() {
        this.#element.remove();
    }
}