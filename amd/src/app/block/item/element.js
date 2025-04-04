import ModalDeleteCancel from 'core/modal_delete_cancel';
import ModalEvents from 'core/modal_events';
import Notification from "core/notification";
import {get_strings} from "core/str";
import Ajax from "core/ajax";

const polls = [];

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

    #pollItem(currentTry = 0, retries = -1, uuid = null) {
        if (uuid === null) {
            uuid = crypto.randomUUID();

            if (polls[this.getItemId()]) {
                return;
            }

            polls[this.getItemId()] = uuid;
        } else if (polls[this.getItemId()] !== uuid) {
            return;
        }

        currentTry += 1;

        if (retries !== -1 && currentTry >= retries) {
            return;
        }

        Ajax.call([{
            methodname: 'block_sharing_cart_get_item_from_sharing_cart',
            args: {
                item_id: this.getItemId(),
            },
            done: async(item) => {
                if (item.status === 0) {
                    // Cap the timeout at 10 seconds
                    const timeOut = currentTry > 10 ? 10000 : currentTry * 1000;

                    setTimeout(() => {
                        this.#pollItem(currentTry, retries, uuid);
                    }, timeOut);
                    return;
                }

                // Remove the item from the polls array
                polls.splice(this.getItemId(), 1);

                await this.#blockElement.renderItem(item);
            },
            fail: (data) => {
                Notification.exception(data);
            }
        }]);
    }

    #addEventListeners() {
        this.#element.querySelector('.info').addEventListener('click', this.toggleCollapseRecursively.bind(this));

        const checkbox = this.#element.querySelector('input[data-action="bulk_select"][type="checkbox"]');
        checkbox?.addEventListener('click', (e) => {
            e.stopImmediatePropagation();

            this.#blockElement.updateSelectAllState();
            this.#blockElement.updateBulkDeleteButtonState();
        });

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
        e.stopImmediatePropagation();

        await this.#blockElement.setClipboard(this);
    }

    async runNow(e) {
        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();

        const currentTarget = e.currentTarget;
        currentTarget.disabled = true;

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
                Notification.exception(data);
                currentTarget.disabled = false;
            }
        }]);
    }

    async confirmDeleteItem(e) {
        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();

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

        const modal = await ModalDeleteCancel.create({
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

    getStatus() {
        return this.#element.dataset.status;
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
     * @return {Number}
     */
    getItemOldInstanceId() {
        return Number.parseInt(this.#element.dataset.oldinstanceid);
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
        if (
            !iconElement.classList.contains('fa-exclamation-triangle') &&
            !iconElement.classList.contains('fa-exclamation-circle')
        ) {
            iconElement.classList.remove('fa-folder-o', 'fa-folder-open-o');
            iconElement.classList.add(item.dataset.collapsed === 'true' ? 'fa-folder-o' : 'fa-folder-open-o');
        }
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
        e.stopImmediatePropagation();

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
