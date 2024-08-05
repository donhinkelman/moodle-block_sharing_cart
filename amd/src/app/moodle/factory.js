// eslint-disable-next-line no-unused-vars
import BaseFactory from '../factory';
import Template from "./template";

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
     * @returns {Template}
     */
    template() {
        return new Template(this.#baseFactory);
    }
}