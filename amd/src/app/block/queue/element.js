import {getCurrentCourseEditor} from "core_courseformat/courseeditor";
import Ajax from "core/ajax";

export default class QueueElement {
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
     * @type {CourseEditor}
     */
    #reactive;

    /**
     *
     * @param {BaseFactory} baseFactory
     * @param {BlockElement} blockElement
     * @param {HTMLElement} element
     */
    constructor(baseFactory, blockElement, element) {
        this.#baseFactory = baseFactory;
        this.#blockElement = blockElement;
        this.#element = element;
        this.#reactive = getCurrentCourseEditor();

        this.loadQueue();

        setInterval(() => {
            if (this.getQueueItems().length === 0) {
                return;
            }

            this.loadQueue();
        }, 4000);
    }

    /**
     * @return {NodeListOf<Element>}
     */
    getQueueItems() {
        return this.#element.querySelectorAll('.queue-item');
    }

    async loadQueue(showSpinner = false) {
        const oldChildren = this.#element.children;
        const oldQueueItemsCount = this.getQueueItems().length;

        if (showSpinner) {
            this.#element.innerHTML = '<i class="fa fa-spinner"></i>';
        }

        const elements = await this.#baseFactory.moodle().template().createElementsFromFragment(
            'block_sharing_cart',
            'item_queue',
            M.cfg.contextid,
            {}
        );

        const queueItems = elements.filter((element) => {
            if (!(element instanceof Element)) {
                return false;
            }

            return element.classList.contains('queue-item');
        });

        if (oldQueueItemsCount > queueItems.length) {
            const removedElements = Array.from(oldChildren).filter((element) => {
                const correspondingElement = queueItems.find((el) => el.dataset.id === element.dataset.id);

                return correspondingElement === undefined;
            });

            const sectionIds = [];
            removedElements.forEach((element) => {
                const sectionId = element.dataset.toSectionId;
                if (sectionIds.indexOf(sectionId) !== -1) {
                    return;
                }

                sectionIds.push(sectionId);
            });

            if (sectionIds.length > 0) {
                this.#reactive.dispatch('sectionState', sectionIds);
            }
        }

        this.#element.innerHTML = '';

        queueItems.forEach((element) => {
            element.querySelector('.btn')?.addEventListener('click', () => {
                const taskId = element.dataset.id;

                Ajax.call([{
                    methodname: 'block_sharing_cart_run_task_now',
                    args: {
                        task_id: taskId
                    }
                }]);

                this.loadQueue();
            });
        });

        elements.forEach((element) => {
            this.#element.appendChild(element);
        });
    }
}