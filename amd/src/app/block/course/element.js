import {getCurrentCourseEditor} from "core_courseformat/courseeditor";

/**
 * @typedef {import("core_courseformat/factory").BaseFactory} BaseFactory
 * @typedef {import("core_courseformat/local/courseeditor/courseeditor").CourseEditor} CourseEditor
 */

/**
 * @typedef Section
 * @property {number} id
 * @property {number} section
 * @property {number} number
 * @property {string} title
 * @property {boolean} hassummary
 * @property {string} rawtitle
 * @property {string[]} cmlist
 * @property {boolean} visible
 * @property {string} sectionurl
 * @property {boolean} current
 * @property {boolean} indexcollapsed
 * @property {boolean} contentcollapsed
 * @property {boolean} hasrestrictions
 * @property {boolean} bulkeditable
 * @property {string|null} component
 * @property {number|null} itemid
 */

/**
 * @typedef CourseModule
 * @property {number} id
 * @property {string} anchor
 * @property {string} name
 * @property {boolean} visible
 * @property {boolean} stealth
 * @property {string} sectionid
 * @property {number} sectionnumber
 * @property {boolean} uservisible
 * @property {boolean} hascmrestrictions
 * @property {string} modname
 * @property {number} indent
 * @property {number} groupmode
 * @property {string} module
 * @property {string} plugin
 * @property {boolean} delegatesection
 * @property {boolean} accessvisible
 * @property {string} url
 * @property {boolean} istrackeduser
 * @property {boolean} allowstealth
 */

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

    async reRenderClipboard() {
        this.clearClipboard();
        await this.renderClipboard();
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

        this.#element.querySelectorAll('[data-for="cmlist"],[data-for="course_sectionlist"]').forEach((section) => {

            let clipboardTarget = null;
            let sectionId = null;

            if (section.matches('[data-for="course_sectionlist"]')) {

                const courseSectionsElementList = section.querySelectorAll('.section-item ul[data-for="cmlist"]');

                if (!courseSectionsElementList) {
                    return;
                }

                courseSectionsElementList.forEach((courseSectionElement) => {

                    if (!this.isSectionClipboardTargetEligible(courseSectionElement, item)) {
                        return;
                    }

                    clipboardTarget = courseSectionElement.querySelector('.clipboard_target') ?? element.cloneNode(true);
                    courseSectionElement.prepend(clipboardTarget);
                    sectionId = courseSectionElement.closest('[data-for="section"]').dataset.id;

                    clipboardTarget.classList.remove('hidden');
                    clipboardTarget.parentElement.classList.remove('hidden');
                    clipboardTarget.addEventListener(
                        'click',
                        this.#blockElement.confirmImportBackupFromSharingCart.bind(this.#blockElement, item, sectionId, {}),
                        {
                            signal: this.#clipboardTargetListenerAbortController.signal
                        }
                    );
                });

                return;
            }

            if (!this.isSectionClipboardTargetEligible(section, item)) {
                return;
            }

            clipboardTarget = section.querySelector('.clipboard_target') ?? element.cloneNode(true);
            section.prepend(clipboardTarget);
            sectionId = section.closest('[data-for="section"]').dataset.id;

            clipboardTarget.classList.remove('hidden');
            clipboardTarget.parentElement.classList.remove('hidden');
            clipboardTarget.addEventListener(
                'click',
                this.#blockElement.confirmImportBackupFromSharingCart.bind(this.#blockElement, item, sectionId, {}),
                {
                    signal: this.#clipboardTargetListenerAbortController.signal
                }
            );
        });

        this.updateCourseLevelClipboardTargets(item, element);
    }

    /**
     * Course level targets rendered by the course format (for example the front page of a nesting format):
     * the copied section is created as a new section under data-parent-section-id (0 = top level of data-course-id).
     *
     * @param {ItemElement} item
     * @param {Element} element clipboard target template
     */
    updateCourseLevelClipboardTargets(item, element) {
        if (!item.isSection()) {
            return;
        }

        this.#element.querySelectorAll('[data-region="sharing-cart-course-target"]').forEach((placeholder) => {
            const clipboardTarget = placeholder.querySelector('.clipboard_target') ?? element.cloneNode(true);
            placeholder.appendChild(clipboardTarget);
            clipboardTarget.classList.remove('hidden');

            const parentSectionId = parseInt(placeholder.dataset.parentSectionId ?? '0', 10) || 0;
            const courseId = parseInt(placeholder.dataset.courseId ?? '0', 10) || 0;

            clipboardTarget.addEventListener(
                'click',
                this.#blockElement.confirmImportBackupFromSharingCart.bind(
                    this.#blockElement,
                    item,
                    parentSectionId,
                    {asNewSection: true, courseId: courseId}
                ),
                {
                    signal: this.#clipboardTargetListenerAbortController.signal
                }
            );
        });
    }

    /**
     * Checks whether the section is allowed to have a clipboardTarget button prepended.
     * For example, subsections are not eligible as a clipboard target if the clipboard is a subsection.
     * Subsections cannot nest subsections.
     * @param {Element} section
     * @param {ItemElement} item
     * @returns {boolean}
     */
    isSectionClipboardTargetEligible(section, item) {

        // Has already been made a clipboardTarget.
        if (section.querySelector('.clipboard_target')) {
            return false;
        }

        // If the item is nested under a subsection in the clipboard.
        // And the clipboardTarget is a subsection. (That is not allowed)
        if (item.isNestedUnderSubsection() && section.closest(".subsection")) {
            return false;
        }

        // Activities can always be inserted into any clipboardTarget.
        if (!item.isSubsection() && !item.isSection()) {
            return true;
        }

        // No section nor subsection can be inserted into an activity clipboardTarget.
        if (section.closest('[data-region="activity-card"]')) {
            return false;
        }

        return true;
    }

    /**
     * @param {Number} sectionId
     * @returns {String}
     */
    getSectionName(sectionId) {
        const section = this.reactive.state.section.get(sectionId);

        if (section.title) {
            return section.title;
        }

        return 'Unknown';
    }

    /**
     * @param {Number} sectionId
     * @returns {Array<string>}
     */
    getSectionCourseModules(sectionId) {
        return this.reactive.state.section.get(sectionId).cmlist;
    }

    /**
     * @param {number|string} courseModuleId
     * @returns {CourseModule|null}
     */
    getCourseModule(courseModuleId) {
        return this.reactive.state.cm.get(courseModuleId);
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
     * @param {number|string} sectionId
     * @param {string} type
     * @returns {boolean}
     */
    hasSectionCourseModuleType(sectionId, type) {
        let cms = this.getSectionCourseModules(sectionId);
        if (!cms) {
            return false;
        }
        for (const id of cms) {
            if (this.isCourseModuleTypeById(id, type)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param {number|string} courseModuleId
     * @param {string} type
     * @returns {boolean}
     */
    isCourseModuleTypeById(courseModuleId, type) {
        const courseModule = this.getCourseModule(courseModuleId);
        if (!courseModule) {
            return false;
        }
        return courseModule?.module === type;
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
        } else {
            await this.reRenderClipboard();
        }

        this.updateClipboard(item);
        await this.updateClipboardTargets(item);
    }
}
