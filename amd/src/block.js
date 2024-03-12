import BaseFactory from './app/factory';

export const init = () => {
    BaseFactory.make().blockFactory().eventHandler().onLoad();
};