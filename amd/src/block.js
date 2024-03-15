import BaseFactory from './app/factory';

/**
 * @param {Boolean} canBackupUserdata
 * @param {Boolean} canAnonymizeUserdata
 */
export const init = (canBackupUserdata, canAnonymizeUserdata) => {
    BaseFactory.make().block().eventHandler().onLoad(canBackupUserdata, canAnonymizeUserdata);
};