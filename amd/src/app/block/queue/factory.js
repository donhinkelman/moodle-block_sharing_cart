// eslint-disable-next-line no-unused-vars
import BaseFactory from '../factory';
import QueueElement from "./element";

export default class Factory {
    /**
     * @type {BaseFactory}
     */
    #baseFactory;

    /**
     * @param {BaseFactory} baseFactory
     */
    constructor(baseFactory) {
        this.#baseFactory = baseFactory;
    }

    /**
     * @param {BlockElement} blockElement
     * @param {HTMLElement} element
     * @returns {QueueElement}
     */
    element(blockElement, element) {
        return new QueueElement(this.#baseFactory, blockElement, element);
    }
}