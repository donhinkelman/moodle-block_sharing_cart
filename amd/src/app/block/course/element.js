import {getCurrentCourseEditor} from "core_courseformat/courseeditor";

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

    /**
     * @type {AbortController}
     */
    #clipboardTargetListenerAbortController = new AbortController();

    /**
     * @type {CourseEditor}
     */
    reactive;

    /**
     * @param {BaseFactory} baseFactory
     * @param {BlockElement} blockElement
     * @param {HTMLElement} element
     */
    constructor(baseFactory, blockElement, element) {
        this.#baseFactory = baseFactory;
        this.#blockElement = blockElement;
        this.#element = element;
        this.reactive = getCurrentCourseEditor();
    }

    async renderClipboard() {
        this.#clipboard = await this.#baseFactory.moodle().template().createElementFromTemplate(
            'block_sharing_cart/block/course/clipboard',
            {}
        );

        this.#element.prepend(this.#clipboard);

        const clearClipboardButton = this.#clipboard.querySelector('[data-action="clear-clipboard"]');
        clearClipboardButton.addEventListener(
            'click',
            this.onClearClipboard.bind(this)
        );
    }

    /**
     * @param {Event} e
     */
    onClearClipboard(e) {
        e.preventDefault();
        e.stopPropagation();

        this.clearClipboard();
    }

    clearClipboard() {
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
        const element = await this.#baseFactory.moodle().template().createElementFromTemplate(
            'block_sharing_cart/block/course/clipboard_target',
            {}
        );

        this.#clipboardTargetListenerAbortController.abort();
        this.#clipboardTargetListenerAbortController = new AbortController();

        this.#element.querySelectorAll('[data-for="cmlist"]').forEach((section) => {
            const clipboardTarget = section.querySelector('.clipboard_target') ?? element.cloneNode(true);

            section.prepend(clipboardTarget);

            const sectionId = section.closest('[data-for="section"]').dataset.id;

            clipboardTarget.classList.remove('hidden');
            clipboardTarget.parentElement.classList.remove('hidden');
            clipboardTarget.addEventListener(
                'click',
                this.#blockElement.confirmImportBackupFromSharingCart.bind(this.#blockElement, item, sectionId),
                {
                    signal: this.#clipboardTargetListenerAbortController.signal
                }
            );
        });
    }

    /**
     * @param {Number} sectionId
     * @returns {String}
     */
    getSectionName(sectionId) {
        const section = this.reactive.state.section.get(sectionId);

        return section.title ?? 'Unknown';
    }

    /**
     * @param {Number} sectionId
     * @returns {Array<string>}
     */
    getSectionCourseModules(sectionId) {
        return this.reactive.state.section.get(sectionId).cmlist;
    }

    /**
     * @param {String} courseModuleId
     * @returns {String}
     */
    getCourseModuleName(courseModuleId) {
        const courseModule = this.reactive.state.cm.get(courseModuleId);

        return courseModule.name ?? 'Unknown';
    }

    /**
     * @returns {NodeListOf<HTMLElement>}
     */
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
