import BlockFactory from "./block/factory";

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
    blockFactory() {
        return new BlockFactory(this);
    }
}
