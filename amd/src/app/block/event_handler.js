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

    /**
     * @param {Boolean} canBackupUserdata
     * @param {Boolean} canAnonymizeUserdata
     */
    onLoad(canBackupUserdata, canAnonymizeUserdata) {
        this.setupBlock(canBackupUserdata, canAnonymizeUserdata);
    }

    /**
     * @param {Boolean} canBackupUserdata
     * @param {Boolean} canAnonymizeUserdata
     */
    setupBlock(canBackupUserdata, canAnonymizeUserdata) {
        const block = document.querySelector('.block.block_sharing_cart');

        this.#baseFactory.block().element(block, canBackupUserdata, canAnonymizeUserdata).addEventListeners();
    }
}
