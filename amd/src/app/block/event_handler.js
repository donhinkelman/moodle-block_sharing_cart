// eslint-disable-next-line no-unused-vars
import BaseFactory from '../factory';

export default class EventHandler {
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

    onLoad() {
        this.setupBlock();
    }

    setupBlock() {
        const block = document.querySelector('.block.block_sharing_cart');

        this.#baseFactory.blockFactory().element(block).addEventListeners();
    }
}
