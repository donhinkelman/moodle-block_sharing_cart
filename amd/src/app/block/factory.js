import EventHandler from "./event_handler";
import CourseFactory from "./course/factory";
import QueueFactory from "./queue/factory";
import ItemFactory from "./item/factory";
import BlockElement from "./element";

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
     * @returns {EventHandler}
     */
    eventHandler() {
        return new EventHandler(this.#baseFactory);
    }

    /**
     * @returns {CourseFactory}
     */
    course() {
        return new CourseFactory(this.#baseFactory);
    }

    /**
     * @returns {QueueFactory}
     */
    queue() {
        return new QueueFactory(this.#baseFactory);
    }

    /**
     * @returns {ItemFactory}
     */
    item() {
        return new ItemFactory(this.#baseFactory);
    }


    /**
     * @param {HTMLElement} element
     * @param {Boolean} canBackupUserdata
     * @param {Boolean} canAnonymizeUserdata
     * @param {Boolean} canBackup
     * @param {Boolean} showSharingCartBasket
     */
    element(element, canBackupUserdata, canAnonymizeUserdata, canBackup, showSharingCartBasket) {
        return new BlockElement(
            this.#baseFactory,
            element,
            canBackupUserdata,
            canAnonymizeUserdata,
            canBackup,
            showSharingCartBasket
        );
    }
}
