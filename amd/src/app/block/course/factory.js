import CourseElement from "./element";

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
     * @returns {CourseElement}
     */
    element(blockElement, element) {
        return new CourseElement(this.#baseFactory, blockElement, element);
    }
}