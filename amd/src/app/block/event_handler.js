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
     * @param {Boolean} showSharingCartBasket
     */
    onLoad(canBackupUserdata, canAnonymizeUserdata, showSharingCartBasket) {
        this.setupBlock(canBackupUserdata, canAnonymizeUserdata, showSharingCartBasket);
    }

    /**
     * @param {Boolean} canBackupUserdata
     * @param {Boolean} canAnonymizeUserdata
     * @param {Boolean} showSharingCartBasket
     */
    setupBlock(canBackupUserdata, canAnonymizeUserdata, showSharingCartBasket) {
        const block = document.querySelector('.block.block_sharing_cart');

        const blockElement = this.#baseFactory.block().element(
            block,
            canBackupUserdata,
            canAnonymizeUserdata,
            showSharingCartBasket
        );
        blockElement.addEventListeners();
    }
}
