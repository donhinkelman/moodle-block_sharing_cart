import ModalFactory from 'core/modal_factory';
import ModalEvents from 'core/modal_events';
import {get_strings} from "core/str";
import Ajax from "core/ajax";

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

    /**
     * @param {BaseFactory} baseFactory
     * @param {BlockElement} blockElement
     * @param {HTMLElement} element
     */
    constructor(baseFactory, blockElement, element) {
        this.#baseFactory = baseFactory;
        this.#blockElement = blockElement;
        this.#element = element;

        if (this.#element.dataset.status === '0') {
            this.#pollItem();
        }

        this.#addEventListeners();
    }

    #pollItem(currentTry = 0, retries = 10) {
        currentTry += 1;

        if (currentTry >= retries) {
            console.error("Item not finished after " + retries + " retries, giving up.");
            return;
        }

        Ajax.call([{
            methodname: 'block_sharing_cart_get_item_from_sharing_cart',
            args: {
                item_id: this.getItemId(),
            },
            done: async (item) => {
                if (item.status === 0) {
                    new Promise(
                        (resolve) => {
                            setTimeout(resolve, currentTry * 1000);
                        }
                    ).then(
                        () => {
                            this.#pollItem(currentTry, retries);
                        }
                    );

                    return;
                }

                await this.#blockElement.renderItem(item);
            },
            fail: (data) => {
                console.error(data);
            }
        }]);
    }

    #addEventListeners() {
        this.#element.addEventListener('click', this.toggleCollapseRecursively.bind(this));

        const actionsContainer = this.#element.querySelector(':scope > .item-body .sharing_cart_item_actions');

        actionsContainer?.querySelector('[data-action="delete"]')?.addEventListener(
            'click',
            this.confirmDeleteItem.bind(this)
        );
        actionsContainer?.querySelector('[data-action="copy_to_course"]')?.addEventListener(
            'click',
            this.copyItemToCourse.bind(this)
        );
        actionsContainer?.querySelector('[data-action="run_now"]')?.addEventListener(
            'click',
            this.runNow.bind(this)
        );
    }

    async copyItemToCourse(e) {
        e.preventDefault();
        e.stopPropagation();

        await this.#blockElement.setClipboard(this);
    }

    async runNow(e) {
        e.preventDefault();
        e.stopPropagation();

        const currentTarget = e.currentTarget;

        Ajax.call([{
            methodname: 'block_sharing_cart_run_task_now',
            args: {
                task_id: currentTarget?.dataset?.taskId ?? null,
            },
            done: async () => {
                currentTarget.remove();
                this.#pollItem();
            },
            fail: (data) => {
                console.error(data);
            }
        }]);
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
            title: strings[0] + ': "' + this.getItemName().slice(0, 50).trim() + '"',
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
     * @return {HTMLElement}
     */
    getItemElement() {
        return this.#element;
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
        if (item.dataset.type !== 'section' &&
            item.dataset.status !== '0' &&
            item.dataset.status !== '2') {
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
        return !this.isSection();
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

        if (this.isModule() || this.#element.dataset.status !== '1') {
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