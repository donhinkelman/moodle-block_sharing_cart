// eslint-disable-next-line no-unused-vars
import BaseFactory from '../factory';

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

    constructor(baseFactory, blockElement, element) {
        this.#baseFactory = baseFactory;
        this.#blockElement = blockElement;
        this.#element = element;

        this.#element.querySelectorAll('.queue-item').forEach((item) => {
            
        });
    }
}