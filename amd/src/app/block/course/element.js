// eslint-disable-next-line no-unused-vars
import BaseFactory from '../factory';
import Templates from "core/templates";

export default class CourseElement {
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
     * @type {HTMLElement|null}
     */
    #clipboard = null;

    constructor(baseFactory, blockElement, element) {
        this.#baseFactory = baseFactory;
        this.#blockElement = blockElement;
        this.#element = element;
    }

    async addBackupToSharingCartButtons() {
        let element = document.createElement('div');
        const {html, js} = await new Promise((resolve, reject) => {
            Templates.render('block_sharing_cart/block/course/add_to_sharing_cart_button', {})
                .then(async (html, js) => {
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

        const sectionTitles = this.#element.querySelectorAll('.course-section-header .inplaceeditable');
        sectionTitles.forEach((sectionTitle) => {
            const button = element.cloneNode(true);

            sectionTitle.after(button);
        });

        this.#element.querySelectorAll('.cm_action_menu').forEach((courseModuleActionMenu) => {
            const button = element.cloneNode(true);

            courseModuleActionMenu.append(button);
        });
    }

    async renderClipboard() {
        this.#clipboard = document.createElement('div');

        const {html, js} = await new Promise((resolve, reject) => {
            Templates.render('block_sharing_cart/block/course/clipboard', {})
                .then(async (html, js) => {
                    resolve({
                        html,
                        js
                    });
                }).fail(reject);
        });
        this.#clipboard = await Templates.replaceNode(
            this.#clipboard,
            html,
            js
        )[0];

        this.#element.prepend(this.#clipboard);
        this.#clipboard.querySelector('[data-action="clear-clipboard"]').addEventListener('click', this.clearClipboard.bind(this));
    }

    /**
     * @param {Event} e
     */
    clearClipboard(e) {
        e.preventDefault();
        e.stopPropagation();

        this.#clipboard.classList.add('d-none');
        this.clearClipboardTargets();

        this.#blockElement.clearClipboard();
    }

    /**
     * @param {ItemElement} item
     */
    updateClipboard(item) {
        this.#clipboard.classList.add('d-none');

        const clipboardItemInfo = this.#clipboard.querySelector('.info');
        clipboardItemInfo.innerHTML = item.getItemInfo().innerHTML;

        this.#clipboard.classList.remove('d-none');
    }

    /**
     * @param {ItemElement} item
     */
    async updateClipboardTargets(item) {
        let element = document.createElement('div');
        const {html, js} = await new Promise((resolve, reject) => {
            Templates.render('block_sharing_cart/block/course/clipboard_target', {})
                .then(async (html, js) => {
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

        this.#element.querySelectorAll('[data-for="cmlist"]').forEach((section) => {
            const clipboardTarget = section.querySelector('.clipboard_target') ?? element.cloneNode(true);

            section.prepend(clipboardTarget);
        });
    }

    getClipboardTargets() {
        return this.#element.querySelectorAll('.clipboard_target');
    }

    clearClipboardTargets() {
        this.getClipboardTargets().forEach((target) => {
            target.remove();
        });
    }

    /**
     * @param {ItemElement} item
     */
    async setClipboard(item) {
        if (!this.#clipboard) {
            await this.renderClipboard();
        }

        this.updateClipboard(item);
        await this.updateClipboardTargets(item);
    }
}