import BaseFactory from './app/factory';

/**
 * @param {Boolean} canBackupUserdata
 * @param {Boolean} canAnonymizeUserdata
 * @param {Boolean} showSharingCartBasket
 */
export const init = (canBackupUserdata, canAnonymizeUserdata, showSharingCartBasket) => {
    BaseFactory.make().block().eventHandler().onLoad(canBackupUserdata, canAnonymizeUserdata, showSharingCartBasket);
};