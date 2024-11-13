import {getCurrentCourseEditor} from "core_courseformat/courseeditor";
import Ajax from "core/ajax";
import * as Toast from 'core/toast';
import {get_string as getString} from "core/str";

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
     * @type {Promise<void>|null}
     */
    #loadQueuePromise = null;

    /**
     * @type {Object|null}
     */
    #loadQueueToken = null;

    /**
     * @type {boolean}
     */
    #preventReload = false;

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

        this.tryReloadQueue(true);

        setInterval(() => {
            this.tryReloadQueue();
        }, 4000);
    }

    /**
     * @return {NodeListOf<Element>}
     */
    getQueueItems() {
        return this.#element.querySelectorAll('.queue-item');
    }

    /**
     * @param {boolean} ignoreQueueItemsCount
     */
    tryReloadQueue(ignoreQueueItemsCount = false) {
        if (ignoreQueueItemsCount === false && this.getQueueItems().length === 0) {
            return;
        }

        if (this.#loadQueuePromise !== null) {
            this.#loadQueuePromise.then(() => {
                this.tryReloadQueue();
            }).catch(() => {
                this.tryReloadQueue();
            });
            return;
        }

        this.#loadQueueToken = {};
        this.#loadQueuePromise = this.loadQueue(false, this.#loadQueueToken);
        this.#loadQueuePromise.then(() => {
            this.#loadQueuePromise = null;
            this.#loadQueueToken = null;
        }).catch(() => {
            this.#loadQueueToken = null;
            this.#loadQueuePromise = null;
        });
    }

    /**
     * @param {Boolean} showSpinner
     * @param {Object} token
     * @return {Promise<void>}
     */
    async loadQueue(showSpinner = false, token = {}) {
        // eslint-disable-next-line no-async-promise-executor
        return new Promise(async (resolve, reject) => {
            token.abort = () => {
                reject();
            };

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

                    if (!sectionId) {
                        return;
                    }

                    if (sectionIds.indexOf(sectionId) !== -1) {
                        return;
                    }

                    sectionIds.push(sectionId);
                });

                if (sectionIds.length > 0) {
                    this.#reactive.dispatch('sectionState', sectionIds).then(() => {
                        Toast.add(getString('you_may_need_to_reload_the_course_warning', 'block_sharing_cart'), {
                            closeButton: true,
                            autohide: false,
                            type: 'warning'
                        });
                    });
                }
            }

            this.#element.innerHTML = '';

            queueItems.forEach((element) => {
                const runNowButton = element.querySelector('button.btn');

                if (!runNowButton) {
                    return;
                }

                runNowButton.addEventListener('click', () => {
                    const taskId = element.dataset.id;

                    runNowButton.disabled = true;

                    this.#preventReload = true;
                    if (this.#loadQueueToken !== null) {
                        this.#loadQueueToken.abort();
                    }

                    Ajax.call([{
                        methodname: 'block_sharing_cart_run_task_now',
                        args: {
                            task_id: taskId
                        }
                    }]);

                    setTimeout(() => {
                        this.#preventReload = false;
                        this.tryReloadQueue(true);
                    }, 2000);
                }, {once: true});
            });

            elements.forEach((element) => {
                this.#element.appendChild(element);
            });

            resolve();
        });
    }
}
