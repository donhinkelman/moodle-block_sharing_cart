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
        return this.setupBlock(canBackupUserdata, canAnonymizeUserdata, showSharingCartBasket);
    }

    /**
     * @param {Boolean} canBackupUserdata
     * @param {Boolean} canAnonymizeUserdata
     * @param {Boolean} showSharingCartBasket
     * @returns {{course: CourseElement, block: BlockElement, queue: QueueElement}}
     */
    setupBlock(canBackupUserdata, canAnonymizeUserdata, showSharingCartBasket) {
        const block = document.querySelector('.block.block_sharing_cart');

        const blockElement = this.#baseFactory.block().element(
            block,
            canBackupUserdata,
            canAnonymizeUserdata,
            showSharingCartBasket
        );
        return blockElement.addEventListeners();
    }
}
