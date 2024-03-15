import BlockFactory from "./block/factory";
import MoodleFactory from "./moodle/factory";

export default class Factory {
    /**
     * @returns {Factory}
     */
    static make() {
        return new this();
    }

    /**
     * @returns {BlockFactory}
     */
    block() {
        return new BlockFactory(this);
    }

    /**
     * @returns {MoodleFactory}
     */
    moodle() {
        return new MoodleFactory(this);
    }
}
